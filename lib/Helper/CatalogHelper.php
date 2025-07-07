<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Helper;

use Bitrix\Sale;

final class CatalogHelper
{

    protected array $arBasketProductsId = [];

    public function __construct(){
       $this->getInBasketProductsId();
    }

    public function getInBasketProductsId():void
    {
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        $orderBasket = $basket->getBasketItems();
        foreach ($orderBasket as $item) {
            $this->arBasketProductsId[$item->getProductId()] = $item->getProductId();
        }
    }

    public function checkProductInBasket(int $productId): string
    {
        $result = 'в корзину';
        if (!empty($arBasketProductsId) && in_array($productId, $this->arBasketProductsId)) {
            $result = 'в корзине';
        }
        return $result;
    }
}