<?php

/*
|--------------------------------------------------------------------------
| SolarTools - Client API Routes
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers\SolarAIController;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers\WebhookController;

// ── Solar AI ─────────────────────────────────────────
Route::post('/ai/analyze', [SolarAIController::class, 'analyze'])->name('solartools.ai.analyze');

// ── Webhook Management ───────────────────────────────
Route::get('/webhook/{server_uuid}', [WebhookController::class, 'show'])->name('solartools.webhook.show');
Route::post('/webhook/{server_uuid}', [WebhookController::class, 'store'])->name('solartools.webhook.store');
Route::post('/webhook/{server_uuid}/test', [WebhookController::class, 'test'])->name('solartools.webhook.test');
Route::post('/webhook/{server_uuid}/notify', [WebhookController::class, 'notify'])->name('solartools.webhook.notify');


/*
|--------------------------------------------------------------------------
| SolarTools - RequestHandled Webhook Listener
|--------------------------------------------------------------------------
| We use RequestHandled because it is universally dispatched by Laravel
| at the end of every request. By placing it in the route file, we bypass
| the Blueprint limitation of not loading extension ServiceProviders.
|--------------------------------------------------------------------------
*/

Event::listen(RequestHandled::class, function (RequestHandled $event) {
    try {
        $request = $event->request;
        $response = $event->response;

        // Only process successful POST requests
        if (!$request->isMethod('POST') || !$response->isSuccessful()) {
            return;
        }

        $path = $request->path();

        // Match power actions
        if (strpos($path, '/power') !== false) {
            
            // Extract the server UUID
            $serverModel = $request->route('server');
            
            if (!$serverModel || is_string($serverModel)) {
                $serverUuid = is_string($serverModel) ? $serverModel : null;
                if (!$serverUuid && preg_match('#/servers/([a-zA-Z0-9-]+)/power#', $path, $matches)) {
                    $serverUuid = $matches[1];
                }
                if ($serverUuid) {
                    $serverModel = Server::where('uuid', $serverUuid)->orWhere('uuidShort', $serverUuid)->first();
                }
            }

            if ($serverModel instanceof Server) {
                
                $webhookUrl = $serverModel->discord_webhook ?? null;
                
                if (empty($webhookUrl)) {
                    return;
                }

                $signal = $request->input('signal');
                if (empty($signal) && $request->getContent()) {
                    $json = json_decode($request->getContent(), true);
                    $signal = $json['signal'] ?? 'unknown';
                }

                if (empty($signal) || $signal === 'unknown') {
                    return;
                }

                $map = [
                    'start'   => ['status' => '⏳ Iniciando...',            'color' => 0xF1C40F],
                    'stop'    => ['status' => '🛑 Deteniéndose...',          'color' => 0xE67E22],
                    'restart' => ['status' => '🔄 Reiniciando...',           'color' => 0x3498DB],
                    'kill'    => ['status' => '💀 Detenido Forzosamente',    'color' => 0xE74C3C],
                ];

                if (!isset($map[$signal])) {
                    return;
                }

                $embeds = [[
                    'title'       => $map[$signal]['status'],
                    'description' => "Se ha ejecutado la acción `{$signal}` en el servidor **{$serverModel->name}**.",
                    'color'       => $map[$signal]['color'],
                    'timestamp'   => now()->toIso8601String(),
                    'footer'      => ['text' => 'SolarCloud Panel'],
                ]];

                Http::timeout(5)->post($webhookUrl, ['embeds' => $embeds]);
                Log::info("[SolarTools] Webhook enviado para {$serverModel->name} (acción: {$signal})");
            }
        }
    } catch (\Throwable $e) {
        Log::error('[SolarTools] RequestHandled listener error: ' . $e->getMessage());
    }
});
