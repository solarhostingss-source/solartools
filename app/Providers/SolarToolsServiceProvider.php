<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║ SolarToolsServiceProvider                     ║
 * ╚═══════════════════════════════════════════════╝
 */
class SolarToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // ── Blueprint specific views ───────────────────
        $this->loadViewsFrom(__DIR__ . '/../../admin', 'blueprint');

        // ── Intercept Server Power Commands ────────────
        // Since Pterodactyl doesn't have a reliable native PHP event for power changes,
        // we intercept the actual HTTP API request that the panel UI sends to Wings.
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            // Check if it's the client power route and it was successful
            if ($event->request->is('api/client/servers/*/power') && in_array($event->response->getStatusCode(), [200, 204, 202])) {
                try {
                    $serverParam = $event->request->route('server');
                    
                    // Route Model Binding check
                    if ($serverParam instanceof Server) {
                        $serverModel = $serverParam;
                    } else {
                        $serverModel = Server::where('uuidShort', $serverParam)
                            ->orWhere('uuid', $serverParam)
                            ->first();
                    }

                    if (!$serverModel || empty($serverModel->discord_webhook)) {
                        return; // No webhook configured
                    }

                    $signal = $event->request->input('signal');
                    $statusStr = '';
                    $color = 0;

                    if ($signal === 'start') {
                        $statusStr = '⏳ Iniciando...';
                        $color = hexdec('F1C40F');
                    } elseif ($signal === 'stop') {
                        $statusStr = '🛑 Deteniéndose...';
                        $color = hexdec('E67E22');
                    } elseif ($signal === 'restart') {
                        $statusStr = '🔄 Reiniciando...';
                        $color = hexdec('3498DB');
                    } elseif ($signal === 'kill') {
                        $statusStr = '💀 Detenido Forzosamente';
                        $color = hexdec('E74C3C');
                    } else {
                        return;
                    }

                    $embeds = [[
                        'title' => $statusStr,
                        'description' => "Se ha enviado el comando `{$signal}` al servidor **{$serverModel->name}**.",
                        'color' => $color,
                        'timestamp' => now()->toIso8601String(),
                        'footer' => ['text' => 'SolarCloud Panel']
                    ]];

                    // Send the webhook asynchronously
                    Http::post($serverModel->discord_webhook, ['embeds' => $embeds]);
                    
                    Log::info("[SolarTools] Webhook enviado para servidor {$serverModel->name} (Comando: {$signal})");
                } catch (\Exception $e) {
                    Log::error('[SolarTools] Error en el Listener de Webhook: ' . $e->getMessage());
                }
            }
        });
    }
}
