<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Listeners\ServerStateListener;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║  SolarToolsServiceProvider                    ║
 * ║  Registers event listeners and bootstraps     ║
 * ║  the SolarTools extension.                    ║
 * ╚═══════════════════════════════════════════════╝
 */
class SolarToolsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Listen for any server-related events that might indicate
        // a power state change. Pterodactyl fires various events
        // depending on the version; we listen broadly.
        $serverEvents = [
            'Pterodactyl\Events\Server\Updated',
            'Pterodactyl\Events\Server\Installed',
            'Pterodactyl\Events\Server\Deleted',
        ];

        $listener = new ServerStateListener();

        foreach ($serverEvents as $event) {
            if (class_exists($event)) {
                Event::listen($event, [$listener, 'handleEvent']);
            }
        }
    }
}
