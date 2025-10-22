<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Client;

class ClientsImportFromSheet extends Command
{
    protected $signature = 'clients:import-sheet
        {--url= : URL del Google Sheet (vista o export)}
        {--sheet=Clientes : Nombre de pestaña del Sheet}
        {--path= : Ruta a CSV local (alternativa a --url)}
        {--update : Actualizar si el phone ya existe}
        {--dry : Simulación (no guarda en DB)}';

    protected $description = 'Importa clientes (Nombre, Apellido, Teléfono) y genera ruc/email/address; evita duplicados y guarda en clients.';

    public function handle(): int
    {
        $url   = $this->option('url');
        $sheet = $this->option('sheet') ?? 'Clientes';
        $path  = $this->option('path');
        $doUpd = (bool)$this->option('update');
        $dry   = (bool)$this->option('dry');

        // 1) Obtener CSV
        $csv = null;
        if ($url) {
            $csvUrl = $this->toCsvUrl($url, $sheet);
            $this->info("Descargando CSV: {$csvUrl}");
            $resp = Http::timeout(25)->get($csvUrl);
            if (!$resp->ok()) {
                $this->error('No pude descargar el CSV (status '.$resp->status().').');
                return self::FAILURE;
            }
            $csv = $resp->body();
        } elseif ($path) {
            if (!is_readable($path)) { $this->error("No leo archivo: {$path}"); return self::FAILURE; }
            $csv = file_get_contents($path);
        } else {
            $this->error('Debe indicar --url (Google Sheet) o --path (CSV local).');
            return self::FAILURE;
        }

        // 2) Parseo CSV (detecta separador , o ;)
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));
        if (!$lines || count($lines) < 2) { $this->error('CSV vacío.'); return self::FAILURE; }

        $delimiter = (substr_count($lines[0], ';') > substr_count($lines[0], ',')) ? ';' : ',';
        $headers = $this->parseCsvLine(array_shift($lines), $delimiter);
        $headers = array_map(fn($h)=>Str::of($h)->lower()->trim()->toString(), $headers);

        // Mapeo de columnas (acepta variantes)
        $idx = fn(array $alts)=> collect($alts)->map(fn($a)=>array_search($a,$headers))->first(fn($i)=>$i!==false, null);
        $iNombre   = $idx(['nombre','name']);
        $iApellido = $idx(['apellido','apellidos','last name','lastname']);
        $iTelefono = $idx(['teléfono','telefono','phone','tel']);

        if ($iNombre === null || $iTelefono === null) {
            $this->error('Se requieren columnas: Nombre y Teléfono (opcional Apellido).');
            return self::FAILURE;
        }

        // 3) Import con deduplicación
        $seen = []; // teléfonos ya vistos en este CSV
        $stats = ['total'=>0,'created'=>0,'updated'=>0,'skipped'=>0,'errors'=>0];

        foreach ($lines as $rowIdx => $line) {
            if (trim($line) === '') continue;
            $stats['total']++;

            $cols = $this->parseCsvLine($line, $delimiter);
            $nombre   = $cols[$iNombre]   ?? '';
            $apellido = $iApellido !== null ? ($cols[$iApellido] ?? '') : '';
            $telefono = $cols[$iTelefono] ?? '';

            $name  = Str::of(trim($nombre.' '.$apellido))->squish()->title()->toString();
            $phone = $this->normalizePhone($telefono);

            if ($name === '' || $phone === '') { $stats['skipped']++; continue; }

            // Dedupe en archivo
            if (isset($seen[$phone])) { $stats['skipped']++; continue; }
            $seen[$phone] = true;

            // Placeholders únicos y estables
            $ruc  = 'TEST-RUC-' . strtoupper(substr(sha1($phone), 0, 10));
            $slug = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/','-')->trim('-');
            $tail = substr($phone, -6) ?: substr(sha1($name.$phone), 0, 6);
            $emailBase = $slug !== '' ? $slug : 'cliente';
            $email = "{$emailBase}.{$tail}@example.com";
            $address = 'Pendiente de registrar';

            $payload = [
                'name'    => $name,
                'phone'   => $phone,
                'ruc'     => $ruc,
                'email'   => $email,
                'address' => $address,
                'is_test' => 1,
                'active'  => 1,
                'user_id' => 1, // ✅ Asigna tu usuario fijo automáticamente
            ];


            if ($dry) { $this->line('[DRY] '.json_encode($payload, JSON_UNESCAPED_UNICODE)); continue; }

            try {
                DB::beginTransaction();

                $existing = Client::where('phone', $phone)->first();
                if ($existing) {
                    if ($doUpd) {
                        $existing->fill($payload)->save();
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++; DB::rollBack(); continue;
                    }
                } else {
                    Client::create($payload);
                    $stats['created']++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack(); $stats['errors']++;
                $this->error('Fila '.($rowIdx+2).': '.$e->getMessage());
            }
        }

        $this->info('Resultado: '.json_encode($stats, JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }

    /** Convierte URL de vista de Google Sheet a export CSV de una pestaña */
    private function toCsvUrl(string $url, string $sheetName): string
    {
        if (preg_match('~/spreadsheets/d/([^/]+)/~', $url, $m)) {
            $docId = $m[1];
            return "https://docs.google.com/spreadsheets/d/{$docId}/gviz/tq?tqx=out:csv&sheet=" . rawurlencode($sheetName);
        }
        return $url; // ya sería una URL CSV
    }

    /** Normaliza teléfono a formato canónico (PY-friendly): 595XXXXXXXXX */
    private function normalizePhone(?string $raw): string
    {
        $digits = preg_replace('/\D+/', '', (string)$raw);
        if ($digits === '') return '';
        if (str_starts_with($digits, '595')) return $digits;
        if (str_starts_with($digits, '0'))   return '595' . substr($digits, 1);
        // 9–11 dígitos → asumimos PY
        if (strlen($digits) >= 9 && strlen($digits) <= 11) {
            return '595' . ltrim($digits, '0');
        }
        return $digits;
    }

    /** CSV robusto para una línea (respeta comillas) */
    private function parseCsvLine(string $line, string $delimiter): array
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $line); rewind($fh);
        $row = fgetcsv($fh, 0, $delimiter);
        fclose($fh);
        return $row ?? [];
    }
}
