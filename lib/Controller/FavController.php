<?php

declare(strict_types=1);

namespace TwoQuick\Api\Controller;

use TwoQuick\Api\Controller\Base\Controller;

class FavController extends Controller
{
    protected string $service = 'twoquick.api.fav';

    public function setAction(): array
    {
        $service = $this->getService();
        try {
            $service->set((int)$this->request->get('id'));
            return $service->getData();
        } catch (\Exception $ex) {
            $this->addError(new \Bitrix\Main\Error($ex->getMessage(), 401));
        }
    }

    public function clearAction():array{
        $service = $this->getService();
        try {
            $service->clear();
            return $service->getData();
        } catch (\Exception $ex) {
            $this->addError(new \Bitrix\Main\Error($ex->getMessage(), 401));
        }
    }

}
