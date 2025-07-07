<?php

use TwoQuick\Api\Service\FavService;
use TwoQuick\Api\Service\Sale\BasketService;
use TwoQuick\Api\Service\SearchService;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\TwoQuick\\Api\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            'twoquick.api.sale.basket' => [
                'className' => BasketService::class,
            ],
            'twoquick.api.fav' => [
                'className' => FavService::class,
            ],
            'twoquick.api.search' => [
                'className' => SearchService::class,
            ],
        ],
        'readonly' => false,
    ],
    'headers' => [
        'value' => [
            'allowOrigin' => '*',
            'allowCredentials' => 'true',
            'allowMethods' => 'GET, POST, OPTIONS, HEAD',
            'allowHeaders' => 'Content-Type, Origin, Authorization, x-frontend-uri-path',
        ],
        'readonly' => true,
    ],
];
