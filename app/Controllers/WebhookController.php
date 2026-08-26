<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║  WebhookController - Discord Webhook Manager  ║
 * ║  CRUD operations for per-server Discord       ║
 * ║  webhook URLs.                                ║
 * ╚═══════════════════════════════════════════════╝
 */
class WebhookController extends ClientApiController
{
    /**
     * Get the webhook URL for a specific server.
     *
     * GET /api/client/extensions/solartools/webhook/{server_uuid}
     *
     * @param Request $request
     * @param string  $server_uuid
     * @return JsonResponse
     */
    public function show(Request $request, string $server_uuid): JsonResponse
    {
        $serverModel = Server::where('uuidShort', $server_uuid)
            ->orWhere('uuid', $server_uuid)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'webhook' => $serverModel->discord_webhook ?? '',
            'server'  => $serverModel->name,
        ]);
    }

    /**
     * Save/update the webhook URL for a specific server.
     *
     * POST /api/client/extensions/solartools/webhook/{server_uuid}
     *
     * @param Request $request
     * @param string  $server_uuid
     * @return JsonResponse
     */
    public function store(Request $request, string $server_uuid): JsonResponse
    {
        $request->validate([
            'webhook_url' => 'nullable|url|max:500',
        ]);

        $serverModel = Server::where('uuidShort', $server_uuid)
            ->orWhere('uuid', $server_uuid)
            ->firstOrFail();

        $webhookUrl = $request->input('webhook_url');

        // ── Validate Discord webhook URL format ────────
        if ($webhookUrl && !$this->isValidDiscordWebhook($webhookUrl)) {
            return response()->json([
                'success' => false,
                'error'   => 'La URL proporcionada no es un webhook válido de Discord.',
            ], 422);
        }

        // ── Update the server record ───────────────────
        $serverModel->discord_webhook = $webhookUrl;
        $serverModel->save();

        Log::info('[SolarTools] Webhook updated', [
            'server'  => $serverModel->name,
            'webhook' => $webhookUrl ? 'configured' : 'removed',
        ]);

        return response()->json([
            'success' => true,
            'message' => $webhookUrl
                ? 'Webhook de Discord guardado correctamente.'
                : 'Webhook de Discord eliminado.',
        ]);
    }

    /**
     * Send a test notification to the configured webhook.
     *
     * POST /api/client/extensions/solartools/webhook/{server_uuid}/test
     *
     * @param Request $request
     * @param string  $server_uuid
     * @return JsonResponse
     */
    public function test(Request $request, string $server_uuid): JsonResponse
    {
        $serverModel = Server::where('uuidShort', $server_uuid)
            ->orWhere('uuid', $server_uuid)
            ->firstOrFail();

        $webhookUrl = $serverModel->discord_webhook;

        if (empty($webhookUrl)) {
            return response()->json([
                'success' => false,
                'error'   => 'No hay un webhook configurado para este servidor.',
            ], 422);
        }

        try {
            $payload = [
                'embeds' => [
                    [
                        'title'       => '🧪 SolarTools - Test de Webhook',
                        'description' => "Este es un mensaje de prueba desde **SolarTools**.\n\nServidor: **{$serverModel->name}**\nEstado: ✅ Webhook funcionando correctamente",
                        'color'       => 0x00D4AA, // Solar green
                        'footer'      => [
                            'text' => 'SolarTools by SolarCloud',
                        ],
                        'timestamp'   => now()->toISOString(),
                    ],
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notificación de prueba enviada correctamente.',
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Error al enviar la notificación. Código: ' . $response->status(),
            ], 502);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error de conexión: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate that the URL is a Discord webhook URL.
     *
     * @param string $url
     * @return bool
     */
    private function isValidDiscordWebhook(string $url): bool
    {
        return (bool) preg_match(
            '/^https:\/\/(discord\.com|discordapp\.com)\/api\/webhooks\/\d+\/[\w-]+$/',
            $url
        );
    }
}
