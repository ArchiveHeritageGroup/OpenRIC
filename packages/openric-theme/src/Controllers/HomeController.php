<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class HomeController extends Controller
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function index(): View
    {
        $fusekiOnline = false;
        $tripleCount = 0;

        try {
            $health = $this->triplestore->health();
            $fusekiOnline = $health['online'] ?? false;
            $tripleCount = $fusekiOnline ? $this->triplestore->countTriples() : 0;
        } catch (\Exception) {
            // Fuseki may not be reachable — show page anyway
        }

        return view('theme::home', [
            'fusekiOnline' => $fusekiOnline,
            'tripleCount' => $tripleCount,
        ]);
    }
}
