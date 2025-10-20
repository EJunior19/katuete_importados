<?php
// app/Listeners/PrepareReferenceAutomation.php
namespace App\Listeners;

use App\Events\ClientReferenceCreated;
use App\Services\ContactOnboardingService;

class PrepareReferenceAutomation
{
    public function handle(ClientReferenceCreated $event): void
    {
        $ref = $event->reference; // <- ahora existe

        app(ContactOnboardingService::class)->ensureDeepLink($ref);
        app(ContactOnboardingService::class)->sendIntro($ref);
    }
}
