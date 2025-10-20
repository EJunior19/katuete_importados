<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // nada especial aquí
    }

    public function boot(): void
    {
        // 🔒 Forzar HTTPS si viene por Heroku
        if (request()->headers->get('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
        // 🔒 Forzar HTTPS si viene por Cloudflare
        if (request()->headers->get('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // 💰 Directiva Blade personalizada (mantener)
        Blade::directive('money', function ($expr) {
            return "<?php echo money_py($expr); ?>";
        });
    }
}
