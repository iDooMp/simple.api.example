<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Service\Sale;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\UserPhoneAuthTable;

use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\Manager as PaymentManager;
use SP\Tools\Services\BasketService;
use SP\Tools\Services\ErrorService;
use SP\Tools\Services\FormatService;
use SP\Tools\Services\HelperService;
use TwoQuick\Api\Service\Base\Service;

Loader::includeModule('sale');

class OrderService extends Service
{

    private Order $order;
    private int $userId;
    private array $result = [];

    private int $deliveryId = 2;
    private int $paymentId = 2;

    public function getData(): array
    {
        return $this->result;
    }

    public function init($request): void
    {
        if ($GLOBALS['USER']->IsAuthorized()) {
            $this->userId = (int)$GLOBALS['USER']->GetId();
        } else {
            $this->registerUser($request->get('PHONE'), $request->get('EMAIL'), $request->get('NAME'));
        }
        $this->order = Order::create(Context::getCurrent()->getSite(), $this->userId);
    }

    private function registerUser($phone, $email, $name): void
    {
        $this->userId = \CSaleUser::GetAnonymousUserID();
        $formatedPhone = NormalizePhone($phone);
        $PhoneAuthTable = UserPhoneAuthTable::getList(
            array(
                'filter' => array('PHONE_NUMBER' => $formatedPhone),
            ));
        if ($arUser = $PhoneAuthTable->fetch()) {
            $this->userId = $arUser['USER_ID'];
        } else {
            $pass = randString(7);
            $validData = [
                'NAME' => $name,
                'PHONE_NUMBER' => $formatedPhone,
                'PERSONAL_PHONE' => $phone,
                'EMAIL' => $email,
                'LOGIN' => $phone,
                'PASSWORD' => $pass,
                'CONFIRM_PASSWORD' => $pass,
            ];
            $user = new \CUser;
            $def_group = \COption::GetOptionString("main", "new_user_registration_def_group", "");
            if ($def_group != "") {
                $validData["GROUP_ID"] = explode(",", $def_group);
            }
            $ID = $user->Add($validData);
            if (intval($ID) > 0) {
                $GLOBALS['USER']->Authorize($ID);
                $this->userId = $ID;
            } else {
                throw new \Exception($user->LAST_ERROR);
            }
        }
    }

    private function getProperties($personType): array
    {
        $properties = [];
        $db_props = \CSaleOrderProps::GetList(array("SORT" => "ASC"),
            array("PERSON_TYPE_ID" => $personType, 'ACTIVE' => 'Y'));
        while ($prop = $db_props->GetNext()) {
            $properties[$prop['CODE']] = $prop;
        }
        return $properties;
    }

    public function setLocation($cityCode): void
    {
        $propertyCollection = $this->order->getPropertyCollection();
        if ($cityCode) {
            if (!empty($propertyCollection->getDeliveryLocation())) {
                $propertyCollection->getDeliveryLocation()->setValue($cityCode);
            }
            if ($zip = \CSaleLocation::GetLocationZIP(\CSaleLocation::getLocationIDbyCODE($cityCode))->fetch()) {
                if (!empty($propertyCollection->getDeliveryLocationZip())) {
                    $propertyCollection->getDeliveryLocationZip()->setValue($zip['ZIP']);
                }
            }
        }
    }

    /**
     * @param $request
     * @param BasketBase $basket
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function create($request, BasketBase $basket)
    {
        $personType = !empty($request['payer']) ? $request['payer'] : 1;
        $properties = $this->getProperties($personType);

        $this->deliveryId = intval($request['payment']);
        $this->paymentId = intval($request['delivery']);
        $arResult['ERROR'] = [];

        $data = $request->getPostList();

        foreach ($properties as $key => $property) {
            if ($property['REQUIED'] == 'Y' && empty($data[$property['CODE']])) {
                $arResult['ERROR'][] = 'Заполните поле "' . $property['NAME'] . '" <br>';
            }
        }

        if (empty($this->deliveryId)) {
            $arResult['ERROR'][] = "Выберите Доставку <br>";
        }
        if (empty($this->paymentId)) {
            $arResult['ERROR'][] = "Выберите систему оплаты <br>";
        }

        if (!empty($arResult['ERROR'])) {
            throw new \Exception(implode("\r\n", $arResult['ERROR']));
        }

        if (!$GLOBALS['USER']->IsAuthorized()) {
            $this->registerUser($data['PHONE'], $data['EMAIL'], $data['NAME']);
        }


        $this->init($request);
        $this->setBasket($basket);

        $this->order->setPersonTypeId(!empty($personType) ? $personType : 1);

        if (!empty($data['LOCATION'])) {
            $this->setLocation($data['LOCATION']);
        }

        $shipmentCollection = $this->order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();

        if (!empty($this->deliveryId)) {
            $service = DeliveryManager::getById($this->deliveryId);
        } else {
            $service = DeliveryManager::getById(EmptyDeliveryService::getEmptyDeliveryServiceId());
        }

        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
            'CURRENCY' => $this->order->getCurrency(),
        ));

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($this->order->getBasket() as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }

        if (!empty($this->paymentId)) {
            $paymentCollection = $this->order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $paySystemService = PaymentManager::getObjectById($this->paymentId);

            $payment->setFields(array(
                'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                'SUM' => $this->order->getPrice(),
            ));
        }

        if ($request['message']) {
            $this->order->setField('USER_DESCRIPTION', $request['message']);
        }

        $propertyCollection = $this->order->getPropertyCollection();
        foreach ($properties as $key => $property) {
            $propertyCollection->getItemByOrderPropertyId($property['ID'])->setValue($data[$key]);
        }

        $this->order->doFinalAction(true);
        $result = $this->order->save();
        $orderId = $this->order->getId();

        if ($orderId > 0) {
            if ($data['SUBSCRIBE'] == 'Y') {
                if(Loader::IncludeModule('subscribe'))
                {
                    $subscr = new \CSubscription;
                    $subscr->Add([
                        'RUB_ID',
                        'SEND_CONFIRM' => 'N',
                        'ALL_STIES' => 'Y',
                        'USER_ID' => $this->userId,
                        'EMAIL' => $data['EMAIL'],
                    ]);
                }
            }
            $_SESSION['SALE_ORDER_ID'][] = $orderId;
            $this->result = ['id' => $orderId];
        } else {
            throw new \Exception(implode(PHP_EOL, $result->getErrorMessages()));
        }
    }

    public function setBasket(BasketBase $basket): void
    {
        $this->order->setBasket($basket);
    }

    public function oneClick($request, BasketBase $basket): void
    {
        $personType = 1;

        $name = $request->get('NAME');
        $phone = $request->get('PHONE');
        $email = $request->get('EMAIL');

        $arResult['ERROR'] = [];

        if (empty($name)) {
            $arResult['ERROR'][] = 'Не заполнено поле "ФИО"<br>';
        }
        if (empty($phone)) {
            $arResult['ERROR'][] = 'Не заполнено поле "Телефон"<br>';
        }
        if (empty($email)) {
            $arResult['ERROR'][] = 'Не заполнено поле "E-Mail"<br>';
        }

        if (!empty($arResult['ERROR'])) {
            throw new \Exception(implode("\r\n", $arResult['ERROR']));
        }

        $this->init($request);
        $this->setBasket($basket);

        $this->order->setPersonTypeId($personType);

        $shipmentCollection = $this->order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();

        $service = DeliveryManager::getById(EmptyDeliveryService::getEmptyDeliveryServiceId());

        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
            'CURRENCY' => $this->order->getCurrency(),
        ));

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($this->order->getBasket() as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }

        if (!empty($this->paymentId)) {
            $paymentCollection = $this->order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $paySystemService = PaymentManager::getObjectById($this->paymentId);

            $payment->setFields(array(
                'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                'SUM' => $this->order->getPrice(),
            ));
        }

        $propertyCollection = $this->order->getPropertyCollection();

        $propertyCollection->getItemByOrderPropertyCode('NAME')->setValue($name);
        $propertyCollection->getItemByOrderPropertyCode('PHONE')->setValue($phone);
        $propertyCollection->getItemByOrderPropertyCode('EMAIL')->setValue($email);

        $this->order->doFinalAction(true);
        $result = $this->order->save();
        $orderId = $this->order->getId();

        if ($orderId > 0) {
            $_SESSION['SALE_ORDER_ID'][] = $orderId;
            $this->result = ['id' => $orderId];
        } else {
            throw new \Exception(implode(PHP_EOL, $result->getErrorMessages()));
        }
    }

    /**
     * @param $city
     * @param $deliveryId
     * @param $paymentId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPrice($city = null, $deliveryId = null, $paymentId = null)
    {
        if (!empty($city)) {
            $this->setLocation($city);
        }

        $this->order->setPersonTypeId(1);
        $this->order->getShipmentCollection()->clearCollection();
        $shipmentCollection = $this->order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();

        if (!empty($deliveryId)) {
            $service = DeliveryManager::getById($deliveryId);
        } else {
            $service = DeliveryManager::getById(EmptyDeliveryService::getEmptyDeliveryServiceId());
        }

        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
            'CURRENCY' => $this->order->getCurrency(),
        ));

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $extraPrice = 0;
        foreach ($this->order->getBasket() as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
            if ($basketItem->getPrice() > $basketItem->getBasePrice()) {
                $extraPrice += ($basketItem->getPrice() - $basketItem->getBasePrice()) * $basketItem->getQuantitY();
            }
        }

        if (!empty($paymentId)) {
            $paymentCollection = $this->order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $paySystemService = PaymentManager::getObjectById($paymentId);

            $payment->setFields(array(
                'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                'SUM' => $this->order->getPrice(),
            ));


            $margins = HelperService::getSettingsByCodes([
                'ONLINE_PAY_SYSTEM_ID',
                'SBP_PAY_SYSTEM_ID',

                'ONLINE_PAY_SYSTEM_MARGIN',
                'SBP_PAY_SYSTEM_MARGIN',
            ]);


            $markup = false;
            if ($payment->getPaymentSystemId() == $margins['ONLINE_PAY_SYSTEM_ID']) {
                $markup = $margins['ONLINE_PAY_SYSTEM_MARGIN'];
            }

            if ($payment->getPaymentSystemId() == $margins['SBP_PAY_SYSTEM_ID']) {
                $markup = $margins['SBP_PAY_SYSTEM_MARGIN'];
            }

            if (!empty($markup)) {
                $orderPrice = $this->order->getPrice();
                $newPrice = $orderPrice + ($orderPrice * $markup / 100);

                $payment->setField('SUM', $newPrice);
                $this->order->setFieldNoDemand('PRICE', $newPrice);
            }

        }
        $this->order->doFinalAction(true);
        return [
            'order_total_price' => (!empty($newPrice)) ? $newPrice : $this->order->getPrice(),
            'discount_price' => $extraPrice,
            'delivery_price' => $this->order->getDeliveryPrice(),
        ];
    }


}