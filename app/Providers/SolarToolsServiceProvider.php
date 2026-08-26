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

        // ── Intercept Pterodactyl API Power Requests ───
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $request = $event->request;
            $response = $event->response;

            // Only care about successful responses (204 No Content for power actions)
            if (!$response->isSuccessful()) {
                return;
            }

            // Target route: /api/client/servers/{server}/power
            $path = $request->path();
            if ($request->isMethod('POST') && preg_match('#^api/client/servers/[a-zA-Z0-9\-]+/power$#', $path)) {
                
                try {
                    // In Pterodactyl client routes, 'server' is bound to the Server model instance
                    $serverModel = $request->route('server');
                    
                    if (!$serverModel || !($serverModel instanceof Server)) {
                        // If it's a string (e.g. UUID), fallback to fetching it
                        if (is_string($serverModel)) {
                            $serverModel = Server::where('uuidShort', $serverModel)
                                ->orWhere('uuid', $serverModel)
                                ->first();
                        } else {
                            return;
                        }
                    }

                    if (!$serverModel || empty($serverModel->discord_webhook)) {
                        return;
                    }

                    $signal = $request->input('signal');
                    if (!$signal) {
                        return;
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
                        'description' => "Se ha ejecutado la acción `{$signal}` en el servidor **{$serverModel->name}**.",
                        'color' => $color,
                        'timestamp' => now()->toIso8601String(),
                        'footer' => ['text' => 'SolarCloud Panel']
                    ]];

                    // Send webhook asynchronously so we don't block the panel
                    Http::timeout(5)->post($serverModel->discord_webhook, ['embeds' => $embeds]);
                    Log::info("[SolarTools] Webhook enviado para servidor {$serverModel->name} (Acción: {$signal})");

                } catch (\Exception $e) {
                    Log::error('[SolarTools] Error en RequestHandled interceptor: ' . $e->getMessage());
                }
            }
        });
    }
}
