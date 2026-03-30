<?php

use App\Providers\AppServiceProvider;
use OpenRic\AccessRequest\Providers\AccessRequestServiceProvider;
use OpenRic\Accession\Providers\AccessionServiceProvider;
use OpenRic\Auth\Providers\OpenRiCAuthServiceProvider;
use OpenRic\Cart\Providers\CartServiceProvider;
use OpenRic\Api\Providers\ApiServiceProvider;
use OpenRiC\Triplestore\Providers\TriplestoreServiceProvider;
use OpenRiC\AiGovernance\Providers\AiGovernanceServiceProvider;
use OpenRicRic\Providers\OpenRicRicServiceProvider;

return [
    AppServiceProvider::class,
    AccessRequestServiceProvider::class,
    AccessionServiceProvider::class,
    OpenRiCAuthServiceProvider::class,
    CartServiceProvider::class,
    ApiServiceProvider::class,
    TriplestoreServiceProvider::class,
    AiGovernanceServiceProvider::class,
    OpenRicRicServiceProvider::class,
];
