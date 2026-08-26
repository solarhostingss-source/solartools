<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Middleware\WebhookMiddleware;

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║ SolarToolsServiceProvider                         ║
 * ╚═══════════════════════════════════════════════════╝
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

        // ── Register the Webhook Middleware globally ───
        // This pushes our TerminableMiddleware into Laravel's global
        // HTTP middleware stack. The `terminate()` method fires AFTER
        // every response is sent to the client, which is when we
        // check if it was a power action and send the Discord webhook.
        //
        // This is the same approach used by production webhook
        // extensions (PteroHook, etc.) and is guaranteed to fire
        // for all API routes including /api/client/servers/{server}/power.
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(WebhookMiddleware::class);
    }
}
