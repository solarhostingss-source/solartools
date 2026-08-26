<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
