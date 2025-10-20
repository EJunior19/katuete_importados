<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeminiAsk extends Command
{
    // podés pasar corto (gemini-1.5-pro-latest) o completo (models/gemini-1.5-pro-latest)
    protected $signature = 'gemini:ask {prompt* : Texto a enviar} {--model=gemini-1.5-pro-latest}';
    protected $description = 'Enviar un prompt a Google Gemini y mostrar la respuesta';

    public function handle(): int
    {
        $prompt = implode(' ', $this->argument('prompt'));
        $inputModel = $this->option('model');

        // Armar candidatos: el ingresado + variantes comunes
        $base = ltrim($inputModel);
        $variants = [$base];

        // si no termina en -latest/-001, agregamos variantes
        if (!preg_match('/-(latest|001)$/', $base)) {
            $variants[] = $base . '-latest';
            $variants[] = $base . '-001';
        }

        // normalizar a "models/..."
        $candidates = array_map(function ($m) {
            return str_starts_with($m, 'models/') ? $m : "models/{$m}";
        }, array_unique($variants));

        $key = env('GEMINI_API_KEY');
        if (!$key) {
            $this->error('⚠️ Falta GEMINI_API_KEY en .env');
            return self::FAILURE;
        }

        // Probar primero v1, luego v1beta
        $versions = ['v1', 'v1beta'];

        foreach ($versions as $ver) {
            foreach ($candidates as $model) {
                $url = "https://generativelanguage.googleapis.com/{$ver}/{$model}:generateContent?key={$key}";

                try {
                    $res = Http::withOptions(['verify' => false])
                        ->post($url, [
                            'contents' => [
                                ['parts' => [['text' => $prompt]]]
                            ]
                        ]);

                    if ($res->ok()) {
                        $data = $res->json();
                        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '(sin texto)';
                        $this->line("✅ Versión: {$ver} | Modelo: {$model}\n");
                        $this->info($text);
                        return self::SUCCESS;
                    }

                    if ($res->status() === 404) {
                        $this->warn("404 con {$ver} / {$model}, probando siguiente…");
                        continue;
                    }

                    // Otros errores: mostrar y salir
                    $this->error("❌ HTTP {$res->status()} en {$ver} / {$model}: " . $res->body());
                    return self::FAILURE;

                } catch (\Throwable $e) {
                    $this->error("💥 Excepción en {$ver} / {$model}: " . $e->getMessage());
                    // probamos siguiente candidato
                }
            }
        }

        $this->error('No se encontró un modelo compatible. Probá con --model=models/gemini-1.5-pro-latest (nombre completo) o activá billing/listá modelos.');
        return self::FAILURE;
    }
}
