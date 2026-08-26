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
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $path = $event->request->path();
            
            // Check if the request is for the power API (matches api/client/servers/{server}/power)
            if (str_contains($path, '/power') && str_contains($path, 'api/client/servers/')) {
                try {
                    $status = $event->response->getStatusCode();
                    if (!in_array($status, [200, 204, 202])) {
                        return; // Not a successful power command
                    }

                    $serverParam = $event->request->route('server');
                    
                    if ($serverParam instanceof Server) {
                        $serverModel = $serverParam;
                    } else {
                        $serverModel = Server::where('uuidShort', $serverParam)
                            ->orWhere('uuid', $serverParam)
                            ->first();
                    }

                    if (!$serverModel || empty($serverModel->discord_webhook)) {
                        return; 
                    }

                    $signal = $event->request->input('signal');
                    if (!$signal) {
                        // Sometimes the payload is a raw string or under a different key in older versions
                        $content = $event->request->getContent();
                        if (str_contains($content, 'start')) $signal = 'start';
                        elseif (str_contains($content, 'stop')) $signal = 'stop';
                        elseif (str_contains($content, 'restart')) $signal = 'restart';
                        elseif (str_contains($content, 'kill')) $signal = 'kill';
                    }

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

                    Http::post($serverModel->discord_webhook, ['embeds' => $embeds]);
                    Log::info("[SolarTools] Webhook enviado para servidor {$serverModel->name} (Señal: {$signal})");
                } catch (\Exception $e) {
                    Log::error('[SolarTools] Error en el Listener de Webhook: ' . $e->getMessage());
                }
            }
        });
    }
}
