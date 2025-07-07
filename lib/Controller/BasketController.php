<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Controller;

use TwoQuick\Api\Controller\Base\Controller;

class BasketController extends Controller
{
    protected string $service = 'twoquick.api.sale.basket';

    public function addAction(): array
    {
        $service = $this->getService();
        $service->add($this->request->get('id'), $this->request->get('quantity'));
        return $service->getData();
    }

    public function updateAction()
    {
        $service = $this->getService();
        $service->update($this->request->get('id'), $this->request->get('quantity'));
        return $service->getData();
    }

    public function deleteAction()
    {
        $service = $this->getService();
        $service->delete($this->request->get('id'));
        return $service->getData();
    }

    public function shareAction(){
        $service = $this->getService();
        $service->share();
        return $service->getData();
    }

    public function clearAction(){
        $service = $this->getService();
        $service->clear();
        return $service->getData();
    }
    public function countAction(){
        $service = $this->getService();
        $service->getCount();
        return $service->getData();
    }

}