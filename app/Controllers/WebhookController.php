<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebhookController extends ClientApiController
{
    /**
     * Get the webhook URL for a specific server.
     *
     * GET /api/client/extensions/solartools/webhook/{server_uuid}
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

        if ($webhookUrl && !$this->isValidDiscordWebhook($webhookUrl)) {
            return response()->json([
                'success' => false,
                'error'   => 'La URL proporcionada no es un webhook válido de Discord.',
            ], 422);
        }

        $serverModel->discord_webhook = $webhookUrl;
        $serverModel->save();

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
     */
    public function test(Request $request, string $server_uuid): JsonResponse
    {
        $serverModel = Server::where('uuidShort', $server_uuid)
            ->orWhere('uuid', $server_uuid)
            ->firstOrFail();

        $webhookUrl = $serverModel->discord_webhook;

        if (!$webhookUrl) {
            return response()->json(['success' => false, 'error' => 'No hay un webhook configurado para este servidor.'], 400);
        }

        $embed = [
            'title'       => '✅ Prueba de Webhook - SolarTools',
            'description' => "¡La integración de Discord para el servidor **{$serverModel->name}** funciona correctamente!",
            'color'       => hexdec('00D4AA'),
            'timestamp'   => now()->toIso8601String(),
            'footer'      => ['text' => 'SolarCloud Panel']
        ];

        try {
            Http::post($webhookUrl, ['embeds' => [$embed]]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error al enviar el webhook a Discord.'], 500);
        }
    }

    /**
     * Send a status notification to the configured webhook.
     *
     * POST /api/client/extensions/solartools/webhook/{server_uuid}/notify
     */
    public function notify(Request $request, string $server_uuid): JsonResponse
    {
        $status = $request->input('status');
        
        $serverModel = Server::where('uuidShort', $server_uuid)
            ->orWhere('uuid', $server_uuid)
            ->firstOrFail();

        $webhookUrl = $serverModel->discord_webhook;

        if (!$webhookUrl || !$status) {
            return response()->json(['success' => false], 400);
        }

        $embeds = [];
        
        if ($status === 'starting') {
            $embeds[] = [
                'title' => '⏳ Servidor Iniciando',
                'description' => "El servidor **{$serverModel->name}** está arrancando.",
                'color' => hexdec('F1C40F'),
            ];
        } elseif ($status === 'running') {
            $embeds[] = [
                'title' => '✅ Servidor En Línea',
                'description' => "El servidor **{$serverModel->name}** ya está en línea.",
                'color' => hexdec('2ECC71'),
            ];
        } elseif ($status === 'stopping') {
            $embeds[] = [
                'title' => '🛑 Servidor Deteniéndose',
                'description' => "El servidor **{$serverModel->name}** se está apagando.",
                'color' => hexdec('E67E22'),
            ];
        } elseif ($status === 'offline') {
            $embeds[] = [
                'title' => '❌ Servidor Fuera de Línea',
                'description' => "El servidor **{$serverModel->name}** se ha apagado.",
                'color' => hexdec('E74C3C'),
            ];
        } else {
            return response()->json(['success' => true, 'ignored' => true]);
        }

        $embeds[0]['timestamp'] = now()->toIso8601String();
        $embeds[0]['footer'] = ['text' => 'SolarCloud Panel'];

        try {
            Http::post($webhookUrl, ['embeds' => $embeds]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error al enviar webhook.'], 500);
        }
    }

    /**
     * Validate Discord webhook URL structure.
     */
    private function isValidDiscordWebhook(string $url): bool
    {
        return preg_match('/^https:\/\/(canary\.|ptb\.)?discord(app)?\.com\/api\/webhooks\/[0-9]+\/[a-zA-Z0-9_-]+$/i', $url) === 1;
    }
}
