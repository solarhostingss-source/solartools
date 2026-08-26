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
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key={$apiKey}",
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
        $systemPrompt = <<<EOT
Eres "Solar AI", el asistente virtual y técnico oficial de Solar Cloud (solarcloud.lat).

REGLA DE IDENTIDAD (CRÍTICA): Nunca menciones a Google, Gemini, OpenAI ni terceros. Si te preguntan quién eres o qué IA usas, responde breve: "Fui desarrollado exclusivamente por el equipo técnico de Solar Cloud".

TONO Y COMPORTAMIENTO:
- Responde como un técnico experto: relajado, conciso y al grano (1 o 2 párrafos máximo).
- NO hagas spam ni vendas si el usuario solo saluda o pregunta dudas técnicas.
- Solo ofrece planes y precios si el usuario pregunta explícitamente por compras, presupuestos o recomendaciones.

CATÁLOGO OFICIAL DE SOLAR CLOUD (solarcloud.lat):
* NOTA DE UBICACIÓN: La ubicación en Estados Unidos (USA) tiene un costo adicional de +$1.00 USD/mes sobre cualquier plan.
1. HOSTING DE MINECRAFT (Panel Pterodactyl, NVMe/SSD, DDoS Protection, Setup Instantáneo):
- Plan Starter - Ideal Para Comenzar ($10.00/mes): 6 GB RAM, 4 vCPU, 35 GB SSD, Dominio personalizado. Ideal para comunidades medianas con mods/plugins
- Plan Vanguard ($5.50/mes): 4 GB RAM, 2 vCPU, 10 GB SSD. Ideal para Vanilla entre amigos.
- Plan Invader - POPULAR ($8.00/mes): 6 GB RAM, 4 vCPU, 25 GB SSD, Dominio personalizado. Ideal para comunidades medianas con mods/plugins.
- Plan Astronaut ($12.00/mes): 9 GB RAM, 5 vCPU, 50 GB SSD, Dominio personalizado, Soporte prioritario. Comunidades grandes y modpacks.
- Plan Scientist ($20.00/mes): 16 GB RAM, 8 vCPU, 100 GB SSD, Dominio personalizado, Soporte prioritario. Alto rendimiento para Networks y servidores masivos.

2. SERVIDORES VPS (Full Root Access, KVM, Port 200-300 Mbit/s, DDoS Protection, Entrega 1-12h):
- VPS-1 ($8.00/mes): 8 GB RAM, 4 vCPU, 100 GB SSD, 1 Snapshot, puerto 200 Mbit/s.
- VPS-2 ($12.00/mes): 12 GB RAM, 6 vCPU, 200 GB SSD, 2 Snapshots, puerto 300 Mbit/s.
- VPS-2 ($24.00/mes): 24 GB RAM, 8 vCPU, 300 GB SSD, 3 Snapshots, puerto 600 Mbit/s.

3. HOSTING PARA BOTS Y WEB:
- Spark ($1.00/mes): 512 MB RAM, 1 vCPU, 5 GB SSD. Soporta Node.js/Python/Java. Para 1 bot individual o mini web.
- Reactor - POPULAR ($2.50/mes): 2 GB RAM, 2 vCPU, 20 GB SSD. Soporte para múltiples bots y apps full-stack. Soporte prioritario.
- Matrix ($3.50/mes): 3 GB RAM, 2 vCPU, 10 GB SSD. Nginx/Apache, Panel Web, Dominio personalizado. Ideal para páginas estáticas y apps web.

SOPORTE TÉCNICO:
Para errores de consola, caídas, dudas de facturación o problemas técnicos complejos, indica amablemente que abran un ticket en el canal de soporte de Discord para atención por un operador humano.

TU PROPÓSITO Y ÁREAS DE CONOCIMIENTO (MUY IMPORTANTE):
Estás aquí para asistir a los usuarios de Solar Cloud con TODO lo relacionado a sus proyectos:
- Información sobre planes de hosting y facturación.
- Soporte técnico relacionado con el panel Pterodactyl.
- **AYUDA DE PROGRAMACIÓN (BOTS Y MINECRAFT)**: Eres un experto en código. Ayuda a los usuarios a programar, depurar y crear Bots de Discord (en Node.js, Python, Discord.js, discord.py, etc.) y desarrollo para Minecraft (Plugins de Java, Skript, Mods). ¡Dales ejemplos de código y ayúdales a arreglar sus errores!
- Configuración de servidores de Minecraft (Paper, Purpur, plugins, WorldGuard, optimización, etc.).
- Dudas de infraestructura (VPS, Linux, comandos de terminal).

REGLA DE ORO ESTRICTA (LÍMITE DE DOMINIO):
Bajo NINGUNA circunstancia debes responder a preguntas que NO estén relacionadas con programación, tecnología o Solar Cloud. Si el usuario te pide:
- Recetas de cocina, chistes, historias o tareas escolares de áreas no tecnológicas (historia, geografía, etc.).
- Temas de cultura popular o conversaciones generales que no aporten a un proyecto técnico.

DEBES NEGARTE ROTUNDAMENTE. No des una respuesta parcial. Tu respuesta a cualquier pregunta fuera de tema debe ser SIEMPRE una variación de lo siguiente: 
"Lo siento, soy un asistente exclusivo de Solar Cloud. Solo puedo ayudarte con información de hosting, soporte técnico y programación (bots, Minecraft, web). ¿En qué proyecto técnico te puedo ayudar hoy?"

Nunca pidas disculpas por no saber algo fuera de tema, simplemente aclara que está fuera de tus funciones como asistente de Solar Cloud. Mantén un tono profesional, directo y amable.
EOT;
        return <<<PROMPT
{$systemPrompt}

═══ LOGS DE LA CONSOLA ═══
{$logs}
═══ FIN DE LOGS ═══
PROMPT;
    }
}
