<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║  SolarAIController - Gemini Log Analyzer      ║
 * ║  Sends server console logs to Google Gemini   ║
 * ║  for intelligent error analysis.              ║
 * ╚═══════════════════════════════════════════════╝
 */
class SolarAIController extends ClientApiController
{
    /**
     * Analyze server console logs using Google Gemini AI.
     *
     * POST /api/client/extensions/solartools/ai/analyze
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(Request $request): JsonResponse
    {
        // ── Validate input ─────────────────────────────
        $request->validate([
            'logs'      => 'required|string|max:50000',
            'server_id' => 'required|string',
        ]);

        $logs     = $request->input('logs');
        $serverId = $request->input('server_id');
        $apiKey   = env('GEMINI_API_KEY');

        // ── Check API key ──────────────────────────────
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'error'   => 'GEMINI_API_KEY no está configurada. Añádela al archivo .env del panel.',
            ], 500);
        }

        // ── Build the prompt ───────────────────────────
        $prompt = $this->buildPrompt($logs);

        try {
            // ── Call Google Gemini API ──────────────────
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.7,
                            'topP'            => 0.95,
                            'maxOutputTokens' => 2048,
                        ],
                    ]
                );

            if ($response->failed()) {
                Log::error('[SolarTools] Gemini API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'error'   => 'Error al comunicarse con Gemini AI. Código: ' . $response->status(),
                ], 502);
            }

            $data = $response->json();

            // ── Extract response text ──────────────────
            $analysisText = $data['candidates'][0]['content']['parts'][0]['text']
                ?? 'No se pudo obtener una respuesta de Gemini.';

            return response()->json([
                'success'  => true,
                'analysis' => $analysisText,
                'server'   => $serverId,
            ]);

        } catch (\Exception $e) {
            Log::error('[SolarTools] Exception during AI analysis', [
                'message' => $e->getMessage(),
                'server'  => $serverId,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Error interno al analizar los logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build the analysis prompt for Gemini.
     *
     * @param string $logs
     * @return string
     */
    private function buildPrompt(string $logs): string
    {
        return <<<PROMPT
Eres un experto en administración de servidores de Minecraft y el panel Pterodactyl. 
Analiza los siguientes logs de la consola del servidor e identifica:

1. **Errores críticos**: Cualquier error que impida el funcionamiento del servidor.
2. **Advertencias importantes**: Warnings que podrían causar problemas.
3. **Problemas de rendimiento**: Indicadores de lag, memory leaks, o alto uso de recursos.
4. **Soluciones sugeridas**: Para cada problema encontrado, proporciona una solución específica y accionable.
5. **Estado general**: Un resumen del estado de salud del servidor.

Responde en español. Usa formato Markdown para organizar tu respuesta con encabezados, listas y bloques de código cuando sea necesario.

Si no encuentras errores, indica que el servidor parece funcionar correctamente y sugiere buenas prácticas.

═══ LOGS DE LA CONSOLA ═══
{$logs}
═══ FIN DE LOGS ═══
PROMPT;
    }
}
