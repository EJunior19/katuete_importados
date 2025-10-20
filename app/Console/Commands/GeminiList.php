<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeminiList extends Command
{
    protected $signature = 'gemini:list';
    protected $description = 'Lista los modelos disponibles para tu API key de Google Gemini';

    public function handle(): int
    {
        $key = env('GEMINI_API_KEY');
        if (!$key) {
            $this->error('⚠️ Falta GEMINI_API_KEY en .env');
            return self::FAILURE;
        }

        try {
            // Endpoint oficial de listado de modelos (v1)
            $res = Http::withOptions(['verify' => false])
                ->get('https://generativelanguage.googleapis.com/v1/models', [
                    'key' => $key,
                ]);

            if (!$res->ok()) {
                $this->error('❌ HTTP '.$res->status().' -> '.$res->body());
                return self::FAILURE;
            }

            $models = collect($res->json('models') ?? [])->pluck('name')->all();

            if (empty($models)) {
                $this->warn('No se listaron modelos. Verificá billing y permisos de la API key.');
                return self::SUCCESS;
            }

            $this->line("✅ Modelos disponibles:");
            foreach ($models as $m) {
                $this->line(' - '.$m);
            }
            $this->line("\nUsá uno de esos nombres con: php artisan gemini:ask --model=\"<nombre>\" \"tu prompt\"");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('💥 Excepción: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
