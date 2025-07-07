<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Controller;

use TwoQuick\Api\Controller\Base\Controller;

class SearchController extends Controller
{
    protected string $service = 'twoquick.api.search';

    public function searchAction($q): array
    {
        $service = $this->getService();
        $service->search($q);
        return $service->getData();
    }

}
