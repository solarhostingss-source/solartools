<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  SolarTools Webhook Middleware                     ║
 * ║  Intercepts power actions and sends Discord        ║
 * ║  webhook notifications after the response is sent. ║
 * ╚═══════════════════════════════════════════════════╝
 *
 * This is a TerminableMiddleware — the `terminate()` method runs
 * AFTER the response has already been sent to the browser,
 * so it does not slow down the panel for the user.
 */
class WebhookMiddleware
{
    /**
     * Handle an incoming request — just pass it through.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * This runs AFTER the response has been sent to the user.
     * We check if this was a power action and send a Discord webhook.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            // Only process successful POST requests
            if (!$request->isMethod('POST')) {
                return;
            }

            // Only process 2xx responses (204 No Content for power actions)
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return;
            }

            $path = $request->path();

            // Match the power endpoint: api/client/servers/{uuid}/power
            if (!preg_match('#api/client/servers/([a-zA-Z0-9\-]+)/power$#', $path, $matches)) {
                return;
            }

            $serverIdentifier = $matches[1];

            Log::debug('[SolarTools] Power action detected', [
                'path' => $path,
                'identifier' => $serverIdentifier,
                'status' => $response->getStatusCode(),
            ]);

            // Try to get the Server model from route binding first
            $serverModel = $request->route('server');

            if (!$serverModel || !($serverModel instanceof Server)) {
                // Fallback: look up by UUID or short UUID
                $serverModel = Server::where('uuidShort', $serverIdentifier)
                    ->orWhere('uuid', $serverIdentifier)
                    ->first();
            }

            if (!$serverModel) {
                Log::debug('[SolarTools] Server not found for webhook', ['id' => $serverIdentifier]);
                return;
            }

            $webhookUrl = $serverModel->discord_webhook ?? null;

            if (empty($webhookUrl)) {
                Log::debug('[SolarTools] No webhook configured for server', ['name' => $serverModel->name]);
                return;
            }

            // Get the power signal from the request body
            $signal = $request->input('signal');
            if (empty($signal)) {
                // Some versions might use 'action' instead of 'signal'
                $signal = $request->input('action');
            }

            if (empty($signal)) {
                Log::debug('[SolarTools] No signal found in power request', [
                    'body' => $request->all(),
                    'content' => $request->getContent(),
                ]);
                return;
            }

            // Map signals to human-readable status and colors
            $map = [
                'start'   => ['status' => '⏳ Iniciando...',            'color' => 0xF1C40F],
                'stop'    => ['status' => '🛑 Deteniéndose...',          'color' => 0xE67E22],
                'restart' => ['status' => '🔄 Reiniciando...',           'color' => 0x3498DB],
                'kill'    => ['status' => '💀 Detenido Forzosamente',    'color' => 0xE74C3C],
            ];

            if (!isset($map[$signal])) {
                Log::debug('[SolarTools] Unknown signal', ['signal' => $signal]);
                return;
            }

            $embeds = [[
                'title'       => $map[$signal]['status'],
                'description' => "Se ha ejecutado la acción `{$signal}` en el servidor **{$serverModel->name}**.",
                'color'       => $map[$signal]['color'],
                'timestamp'   => now()->toIso8601String(),
                'footer'      => ['text' => 'SolarCloud Panel'],
            ]];

            $webhookResponse = Http::timeout(5)->post($webhookUrl, ['embeds' => $embeds]);

            Log::info("[SolarTools] ✅ Webhook enviado para {$serverModel->name} (acción: {$signal})", [
                'discord_status' => $webhookResponse->status(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[SolarTools] ❌ Error en WebhookMiddleware::terminate', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
