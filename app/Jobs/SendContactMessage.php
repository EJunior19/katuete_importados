<?php
// app/Jobs/SendContactMessage.php
namespace App\Jobs;

use App\Models\Client;
use App\Models\ContactLog;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use App\Services\ChannelRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendContactMessage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public ContactLog $log) {}

    public function handle(): void
    {
        $log = $this->log->fresh();
        $client = Client::findOrFail($log->client_id);

        try {
            $sentAt = now();
            match ($log->channel) {
                'telegram' => app(TelegramService::class)->sendMessage($log->to_ref, $log->message),
                'whatsapp' => function() use ($log, $client) {
                    // Estrategia: guardar link en meta y marcar como "sent" (usuario hace click)
                    $link = app(WhatsAppService::class)->buildLink($log->to_ref, $log->message);
                    $meta = $log->meta ?: [];
                    $meta['wa_link'] = $link;
                    $log->meta = $meta;
                    $log->save();
                },
                'email'    => function() use ($log, $client) {
                    // Implementar Mailable; por ahora simulamos OK
                },
                'sms'      => function() use ($log, $client) {
                    // Implementar proveedor SMS
                },
                default    => throw new \RuntimeException('Canal no soportado'),
            };

            $log->status  = 'sent';
            $log->sent_at = $sentAt;
            $log->save();
        } catch (\Throwable $e) {
            $log->status = 'fail';
            $meta = $log->meta ?: [];
            $meta['error'] = $e->getMessage();
            $log->meta = $meta;
            $log->save();
            report($e);
        }
    }
}
