<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Service\Sale;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Context;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use TwoQuick\Api\Service\Base\Service;
use TwoQuick\Api\Service\HelperService;

Loader::includeModule('sale');
Loader::includeModule("highloadblock");

class BasketService extends Service
{

    private array $result;

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->result;
    }

    /**
     * @param $productId
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     */
    protected function isSameIblockId($productId): array
    {
        $result = ['status' => true];
        $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();
        foreach ($basketItems as $item) {
            $arProductsId[] = $item->getProductId();
        }
        if (!empty($arProductsId)) {
            $res = \CIBlockElement::GetByID($productId);
            if ($ar_res = $res->Fetch()) {
                $select = ['ID', 'IBLOCK_ID'];
                $filter = array('ID' => $arProductsId, 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
                $res = \CIBlockElement::GetList([], $filter, false, false, $select);
                while ($fields = $res->Fetch()) {
                    if ($fields['IBLOCK_ID'] !== $ar_res['IBLOCK_ID']) {
                        $result = [
                            'status' => false,
                            'new_iblock_id' => $ar_res['IBLOCK_ID'],
                            'old_iblock_id' => $fields['IBLOCK_ID'],
                        ];
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @param $quantity
     * @param $properties
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function add($id = 0, $quantity = 1, $properties = []): void
    {
        $isSameResult = $this->isSameIblockId($id);
        if (!$isSameResult['status']) {
            $arNewIblock = \CASDiblockTools::GetIBUF($isSameResult['new_iblock_id']);
            $arOldIblock = \CASDiblockTools::GetIBUF($isSameResult['old_iblock_id']);
            $this->result = [
                'type' => 'error',
                'message' => sprintf(
                    'У вас в корзине добавлены товары для %s, чтобы добавить товары для %s необходимо очистить корзину.',
                    $arOldIblock['UF_BASKET_ADD_ERROR_TEXT'],
                    $arNewIblock['UF_BASKET_ADD_ERROR_TEXT']
                ),
            ];
        } else {
            $arProduct = HelperService::getProductWithCache($id);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
            if ($item = $basket->getExistsItem('catalog', $id)) {
                $item->setField('QUANTITY', $item->getQuantity() + $quantity);
            } else {
                $item = $basket->createItem('catalog', $id);
                $itemFields = array(
                    'QUANTITY' => $quantity,
                    'CURRENCY' => CurrencyManager::getBaseCurrency(),
                    'LID' => Context::getCurrent()->getSite(),
                    'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                );
                if (!empty($properties)) {
                    $basketPropertyCollection = $item->getPropertyCollection();
                    $arParams = array("replace_space" => "-", "replace_other" => "-");
                    if (is_array($properties)) {
                        foreach ($properties as $name => $property) {
                            $basketPropertyCollection->setProperty(array(
                                array(
                                    'NAME' => $name,
                                    'CODE' => \Cutil::translit($name, "ru", $arParams),
                                    'VALUE' => $property,
                                    'SORT' => 100,
                                ),
                            ));
                        }
                    } else {
                        $basketPropertyCollection->setProperty(array(
                            array(
                                'NAME' => $properties['name'],
                                'CODE' => \Cutil::translit($properties['name'], "ru", $arParams),
                                'VALUE' => $properties['value'],
                                'SORT' => 100,
                            ),
                        ));
                    }
                }
                $item->setFields($itemFields);
            }
            $basket->save();
            $this->result = [
                'type' => 'success',
                'product' => [
                    'name' => $arProduct['NAME'],
                    'picture' => $arProduct['PREVIEW_PICTURE']['SRC'],
                ],
            ];
        }
    }

    /**
     * @param $id
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function delete($ids): void
    {
        $declension = new Declension('товар', 'товара', 'товаров');
        $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
        foreach ($ids as $id) {
            if (!empty($id)) {
                $item = $basket->getItemById($id);
                $this->result['products'][] = [
                    'id' => $item->getId(),
                    'product_id' => $item->getProductId(),
                    'quantity' => 0,
                    'quantity_formated' => sprintf('%s %s', 0, $declension->get(0)),
                ];
                $item->delete();
            }
        }
        $basket->save();
    }

    /**
     * @param $id
     * @param $quantity
     * @param $properties
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     */
    public function update($id, $quantity, $properties = []): void
    {
        $declension = new Declension('товар', 'товара', 'товаров');
        if ($quantity <= 0) {
            $quantity = 1;
        }
        $arFields = array(
            "QUANTITY" => intval($quantity),
        );
        if (!empty($properties)) {
            $arParams = array("replace_space" => "-", "replace_other" => "-");
            foreach ($properties as $key => $property) {
                $arFields['PROPS'][] = [
                    'NAME' => $key,
                    'CODE' => \Cutil::translit($key, "ru", $arParams),
                    'VALUE' => $property,
                    'SORT' => 100,
                ];
            }
        }
        if (\CSaleBasket::Update(intval($id), $arFields)) {
            $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
            $item = $basket->getItemById($id);
            $this->result['product'] = [
                'id' => $item->getId(),
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'quantity_formated' => sprintf(
                    '%s %s',
                    $item->getQuantity(),
                    $declension->get($item->getQuantity())
                ),
            ];
        } else {
            throw new \Exception('Произошла ошибка,обратитесь к администратору');
        }
    }

    protected function getSharedEntity()
    {
        $hlblock = HL\HighloadBlockTable::getById(HL_SHARED_LINKS_ID)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }

    /**
     * @param $code
     *
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getSharedBasket($code)
    {
        $entity_data_class = $this->getSharedEntity();
        if ($entity_data_class) {
            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array("UF_CODE" => $code),
            ));
            return $rsData->Fetch();
        }
        return false;
    }

    /**
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     */
    public function share(): void
    {
        $products = [];
        $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();
        foreach ($basketItems as $item) {
            $products[$item->getProductId()] = [
                'PRODUCT_ID' => $item->getProductId(),
                'QUANTITY' => $item->getQuantity(),
            ];
        }
        if (!empty($products)) {
            $code = md5(base64_encode(serialize($products)));

            if ($arData = $this->getSharedBasket($code)) {
                $this->result['code'] = $arData['UF_CODE'];
            } else {
                $entity_data_class = $this->getSharedEntity();
                if ($entity_data_class) {
                    $newLink = $entity_data_class::add([
                        'UF_CODE' => $code,
                        'UF_DATA' => json_encode($products),
                    ]);
                    if ($newLink->isSuccess()) {
                        $this->result['code'] = $code;
                    } else {
                        throw new \Exception(
                            'Сожалеем, но произошла ошибка формирования ссылки, обратитесь к администратору'
                        );
                    }
                } else {
                    throw new \Exception(
                        'Произошла внутренняя ошибка, обратитесь к администратору'
                    );
                }
            }
        } else {
            throw new \Exception('Ваша корзина пуста');
        }
    }

    /**
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function checkShared(): void
    {
        $request = Context::getCurrent()->getRequest();
        if (!empty($basketRef = $request->get('basket_ref'))) {
            $data = $this->getSharedBasket($basketRef);
            if (!empty($data['UF_DATA'])) {
                $products = json_decode($data['UF_DATA'], true);
                foreach ($products as $product) {
                    $this->add($product['PRODUCT_ID'], $product['QUANTITY']);
                }
                LocalRedirect(SITE_DIR.'cart/');
            }
        }
    }

    public function clear(): void
    {
        if (Loader::IncludeModule("sale")) {
            \CSaleBasket::DeleteAll(\CSaleBasket::GetBasketUserID());
            $this->result['status'] = true;
        }
    }

    public function getCount(): int
    {
        $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
        return $basket->count();
    }

}
