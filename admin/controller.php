<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\solartools;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;

/**
 * SolarTools Admin Extension Controller.
 *
 * Blueprint calls index() for GET /admin/extensions/solartools
 * and update() for PATCH requests.
 */
class solartoolsExtensionController extends Controller
{
    /**
     * Display the admin extension page.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return view('admin.extensions.solartools.index');
    }

    /**
     * Handle PATCH updates from admin panel.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        return redirect()->route('admin.extensions.solartools.index')
            ->with('success', 'Configuración actualizada.');
    }
}
