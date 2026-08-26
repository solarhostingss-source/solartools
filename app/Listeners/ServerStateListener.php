<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Pterodactyl\Events\Server\Updated as ServerUpdated;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║  ServerStateListener - Discord Notifications  ║
 * ║  Listens for server power state changes and   ║
 * ║  sends alerts to configured Discord webhooks. ║
 * ╚═══════════════════════════════════════════════╝
 *
 * Register this listener in a service provider or via Blueprint's
 * event system. It listens for server state changes and dispatches
 * Discord webhook notifications.
 */
class ServerStateListener
{
    /**
     * Map of power states to Discord embed colors and emojis.
     */
    private const STATE_MAP = [
        'starting'  => ['emoji' => '🔄', 'color' => 0xFFA500, 'label' => 'Iniciando'],
        'running'   => ['emoji' => '✅', 'color' => 0x00D4AA, 'label' => 'En línea'],
        'stopping'  => ['emoji' => '⏳', 'color' => 0xFFD700, 'label' => 'Deteniéndose'],
        'offline'   => ['emoji' => '🔴', 'color' => 0xFF4444, 'label' => 'Fuera de línea'],
        'killing'   => ['emoji' => '💀', 'color' => 0xFF0000, 'label' => 'Detenido forzosamente'],
        'installing'=> ['emoji' => '📦', 'color' => 0x7289DA, 'label' => 'Instalando'],
    ];

    /**
     * Handle the event.
     *
     * This method is designed to be called when a server's power state
     * changes. You can hook it to Pterodactyl events or call it from
     * a scheduled command that monitors daemon status.
     *
     * @param string $serverUuid  The server UUID
     * @param string $powerState  The new power state
     * @return void
     */
    public function handle(string $serverUuid, string $powerState): void
    {
        try {
            $server = Server::where('uuid', $serverUuid)
                ->orWhere('uuidShort', $serverUuid)
                ->first();

            if (!$server || empty($server->discord_webhook)) {
                return;
            }

            $this->sendDiscordNotification($server, $powerState);

        } catch (\Exception $e) {
            Log::error('[SolarTools] ServerStateListener error', [
                'message' => $e->getMessage(),
                'server'  => $serverUuid,
            ]);
        }
    }

    /**
     * Handle a Laravel event (for compatibility with event dispatching).
     *
     * @param mixed $event
     * @return void
     */
    public function handleEvent($event): void
    {
        if (isset($event->server)) {
            $server = $event->server;
            $state  = $event->powerState ?? $event->status ?? 'unknown';

            if (!empty($server->discord_webhook)) {
                $this->sendDiscordNotification($server, $state);
            }
        }
    }

    /**
     * Send a Discord webhook notification for a power state change.
     *
     * @param Server $server
     * @param string $state
     * @return void
     */
    private function sendDiscordNotification(Server $server, string $state): void
    {
        $stateInfo = self::STATE_MAP[$state] ?? [
            'emoji' => '❓',
            'color' => 0x808080,
            'label' => ucfirst($state),
        ];

        $payload = [
            'embeds' => [
                [
                    'title'       => "{$stateInfo['emoji']} Estado del Servidor Actualizado",
                    'description' => "El servidor **{$server->name}** ha cambiado de estado.",
                    'color'       => $stateInfo['color'],
                    'fields'      => [
                        [
                            'name'   => '📋 Servidor',
                            'value'  => $server->name,
                            'inline' => true,
                        ],
                        [
                            'name'   => '🔌 Estado',
                            'value'  => $stateInfo['label'],
                            'inline' => true,
                        ],
                        [
                            'name'   => '🆔 UUID',
                            'value'  => "`{$server->uuidShort}`",
                            'inline' => true,
                        ],
                        [
                            'name'   => '🖥️ Nodo',
                            'value'  => $server->node->name ?? 'Desconocido',
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'SolarTools by SolarCloud • Notificaciones de Estado',
                    ],
                    'timestamp' => now()->toISOString(),
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)->post($server->discord_webhook, $payload);

            if ($response->failed()) {
                Log::warning('[SolarTools] Discord webhook delivery failed', [
                    'server' => $server->name,
                    'status' => $response->status(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[SolarTools] Discord webhook connection error', [
                'server'  => $server->name,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
