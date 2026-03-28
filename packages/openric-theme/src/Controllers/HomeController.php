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
        $health = $this->triplestore->health();
        $tripleCount = $health['online'] ? $this->triplestore->countTriples() : 0;

        return view('theme::home', [
            'fusekiOnline' => $health['online'],
            'tripleCount' => $tripleCount,
        ]);
    }
}
