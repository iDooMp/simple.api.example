<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Service;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;
use CPHPCache;
use Bitrix\Sale\PriceMaths;

final class HelperService
{
    protected static int $featuresHLBLockId = 3;

    /**
     * @param $intProductId
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getProductWithCache($intProductId)
    {
        $arProduct = [];
        $cache_id = md5(sprintf('product_%s_info', $intProductId));
        $cache_dir = "/serptop/products";

        $obCache = new CPHPCache;
        if ($obCache->InitCache(36000, $cache_id, $cache_dir)) {
            $arProduct = $obCache->GetVars();
        } elseif (Loader::IncludeModule("iblock") && Loader::IncludeModule("catalog") && $obCache->StartDataCache()) {
            $filter = ['ID' => $intProductId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
            $rsElements = \CIBlockElement::GetList(['sort' => 'asc'],
                $filter,
                false,
                false,
                ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'PROPERTY_*']);

            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cache_dir);
            if ($ob = $rsElements->GetNextElement()) {
                $fields = $ob->GetFields();
                $fields['PROPERTIES'] = $ob->GetProperties();
                if (!empty($fields['PREVIEW_PICTURE'])) {
                    $fields['PREVIEW_PICTURE'] = \CFile::GetFileArray($fields['PREVIEW_PICTURE']);
                }
                $fields['PRICE'] = \CCatalogProduct::GetOptimalPrice($fields['ID']);
                if (!empty($fields['PRICE'])) {
                    $fields['PRICE']['RESULT_PRICE']['DISCOUNT_PRICE_FORMATED'] = \CCurrencyLang::CurrencyFormat(
                        $fields['PRICE']['RESULT_PRICE']['DISCOUNT_PRICE'],
                        $fields['PRICE']['RESULT_PRICE']['CURRENCY']
                    );
                }
                $CACHE_MANAGER->RegisterTag("iblock_id_".$fields["IBLOCK_ID"]);
                $arProduct = $fields;
            }
            $CACHE_MANAGER->RegisterTag("iblock_id_new");
            $CACHE_MANAGER->EndTagCache();

            $obCache->EndDataCache($arProduct);
        }
        return $arProduct;
    }

    /**
     * @param string $detailPageUrl
     * @param float|int $price
     * @param float|int $weight
     * @param string $currency
     * @param bool $current
     *
     * @return array
     */
    public static function formatOtherProduct(
        string $detailPageUrl,
        float|int $price,
        float|int $weight,
        string $currency,
        bool $current = false
    ): array {
        $price = PriceMaths::roundPrecision(($price / $weight) * 100);
        return [
            'CURRENT' => $current,
            'WEIGHT' => $weight,
            'PRICE' => $price,
            'DETAIL_PAGE_URL' => $detailPageUrl,
            'PRICE_FORMATED' => sprintf(
                '%s/100г',
                \CCurrencyLang::CurrencyFormat(
                    $price,
                    $currency
                )
            ),
            'WEIGHT_FORMATED' => sprintf('%s кг', $weight / 1000),
        ];
    }

    /**
     * @param $arProducts
     *
     * @return void
     */
    public static function calcProfits(&$arProducts): void
    {
        $max_price = max(array_column($arProducts, 'PRICE'));

        foreach ($arProducts as &$item) {
            $profit = $max_price - $item['PRICE'];
            $profit_percent = floor((($max_price - $item['PRICE']) / $max_price) * 100);
            $item['PROFIT'] = $profit;
            $item['PROFIT_PERCENT'] = $profit_percent;
            $item['PROFIT_PERCENT_FORMATED'] = sprintf('Выгода %s%%', $profit_percent);
        }
        unset($item);
        usort($arProducts, function ($a, $b) {
            return $a['WEIGHT'] <=> $b['WEIGHT'];
        });
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMainFeatures(): array
    {
        $result = [];
        $hlblock = HL\HighloadBlockTable::getById(self::$featuresHLBLockId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
            "order" => array("UF_SORT" => "ASC"),
            "filter" => array(),
        ));

        while ($arData = $rsData->Fetch()) {
            if (!empty($arData['UF_ICON'])) {
                $arData['ICON'] = \CFile::GetFileArray($arData['UF_ICON']);
            }
            $result[] = $arData;
        }
        return $result;
    }

    /**
     * @param $arMainFeatures
     * @param $arProduct
     *
     * @return array
     */
    public static function getProductMainFeatures($arMainFeatures, $arProduct): array
    {
        $result = [];
        foreach ($arMainFeatures as $feature) {
            $propValue = $arProduct['PROPERTIES'][$feature['UF_XML_ID']]['VALUE'];
            if (!empty($propValue)) {
                $feature['UF_VALUE'] = $propValue;
                $result[] = $feature;
            }
        }
        return $result;
    }

    /**
     * @param array $arResult
     * @param $resultKey
     * @param int $iblockId
     * @param int $catalogIblockId
     *
     * @return void
     * @throws \Bitrix\Main\LoaderException
     */
    public static function collectPriceData(array &$arResult, $resultKey, int $iblockId, int $catalogIblockId)
    {
        $productInBasketService = new \TwoQuick\Api\Service\ProductInBasketService();
        $arResult[$resultKey]['IBLOCK_INFO'] = \CIBlock::GetByID($iblockId)?->fetch();
        if (Loader::IncludeModule('asd.iblock')) {
            $arResult[$resultKey]['IBLOCK_INFO']['PROPERTIES'] = \CASDiblockTools::GetIBUF($iblockId);
        }

        $arProducts = [];
        $arProductsIds = [];
        $filter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'];
        $sect = \CIBlockSection::GetList(['sort' => 'asc', 'id' => 'desc'],
            $filter,
            false,
            ['ID', 'NAME', 'DESCRIPTION']);
        while ($section = $sect->GetNext()) {
            $arResult[$resultKey]['SECTIONS'][$section['ID']] = $section;
        }
        $select = ['ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_SECTION_ID', 'PROPERTY_*'];
        $filter = array('IBLOCK_ID' => $iblockId, 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
        $res = \CIBlockElement::GetList(['sort' => 'asc', 'ID' => 'ASC'], $filter, false, false, $select);
        while ($ob = $res->GetNextElement()) {
            $fields = $ob->GetFields();
            $fields ['PROPERTIES'] = $ob->GetProperties();
            $arResult[$resultKey]['SECTIONS'][$fields['IBLOCK_SECTION_ID']]['ITEMS'][$fields['ID']] = $fields;
            if (!empty($fields['PROPERTIES']['PRODUCT']['VALUE'])) {
                $arProductsIds[$fields['PROPERTIES']['PRODUCT']['VALUE']] = $fields['PROPERTIES']['PRODUCT']['VALUE'];
            }
        }

        if (!empty($arProductsIds)) {
            $obCache = new CPHPCache();
            if ($obCache->InitCache(36000, serialize([
                $arProductsIds,
                $iblockId,
                $catalogIblockId,
            ]), "/iblock/catalog"))
            {
                $arProducts = $obCache->GetVars();
            }
            elseif ($obCache->StartDataCache())
            {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache("/iblock/catalog");

                $arMainFeatures = HelperService::getMainFeatures();
                $select = [
                    'ID',
                    'IBLOCK_ID',
                    'NAME',
                    'DETAIL_PAGE_URL',
                    'PREVIEW_PICTURE',
                    'PREVIEW_TEXT',
                    'PROPERTY_*',
                ];
                $filter = array(
                    'IBLOCK_ID' => $catalogIblockId,
                    'ID' => $arProductsIds,
                    'ACTIVE_DATE' => 'Y',
                    'ACTIVE' => 'Y',
                );

                $res = \CIBlockElement::GetList([], $filter, false, false, $select);
                while ($ob = $res->GetNextElement()) {
                    $fields = $ob->GetFields();
                    $fields ['PROPERTIES'] = $ob->GetProperties();
                    $arFileTmp = \CFile::ResizeImageGet(
                        $fields['PREVIEW_PICTURE'],
                        ['width' => 410, 'height' => 220],
                        BX_RESIZE_IMAGE_PROPORTIONAL
                    );
                    $fields['PREVIEW_PICTURE'] = $arFileTmp['src'];
                    $fields['MAIN_FEATURES'] = self::getProductMainFeatures($arMainFeatures, $fields);
                    $fields['PRICE'] = \CCatalogProduct::GetOptimalPrice($fields['ID']);
                    $fields['TO_BASKET_BUTTON'] = $productInBasketService->checkProductInBasket((int)$fields['ID']);
                    $arProducts[$fields['ID']] = $fields;
                    $CACHE_MANAGER->RegisterTag("iblock_id_" . $fields['IBLOCK_ID']);
                }

                $CACHE_MANAGER->EndTagCache();

                $obCache->EndDataCache($arProducts);
            }

            if (!empty($arProducts)) {
                foreach ($arResult[$resultKey]['SECTIONS'] as $key => &$section) {
                    foreach ($section['ITEMS'] as &$item) {
                        if (!empty($item['PROPERTIES']['PRODUCT']['VALUE'])) {
                            $item['PRODUCT'] = $arProducts[$item['PROPERTIES']['PRODUCT']['VALUE']];
                        }
                    }
                    if (empty($section['ITEMS'])) {
                        unset($arResult[$resultKey]['SECTIONS'][$key]);
                    }
                }
                unset($section, $item);
            }
        }
    }

    public static function truncateString($string, $maxLength)
    {
        if (mb_strlen($string) > $maxLength) {
            return mb_substr($string, 0, $maxLength).'...';
        }
        return $string;
    }

    public static function getFrequentlySearchedForSection($iblockId, $intSectionId)
    {
        $result = [];
        if (defined('IBLOCK_FREQUENTLY_SEARCHED_ID')) {
            $cache_id = md5(sprintf('frequently_%s_%s', $iblockId, $intSectionId));
            $cache_dir = "/serptop/frequently";

            $obCache = new CPHPCache;
            if ($obCache->InitCache(36000, $cache_id, $cache_dir)) {
                $result = $obCache->GetVars();
            } elseif (Loader::IncludeModule("iblock") && $obCache->StartDataCache()) {
                $rsElements = \CIBlockElement::GetList(
                    ['sort' => 'asc'],
                    [
                        'IBLOCK_ID' => IBLOCK_FREQUENTLY_SEARCHED_ID,
                        'ACTIVE' => 'Y',
                        'ACTIVE_DATE' => 'Y',
                        'PROPERTY_SECTIONS_'.$iblockId => $intSectionId,
                    ],
                    false,
                    false,
                    ['ID', 'NAME', 'CODE']
                );

                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache($cache_dir);
                while ($fields = $rsElements->Fetch()) {
                    $CACHE_MANAGER->RegisterTag("iblock_id_".$fields["IBLOCK_ID"]);
                    $result[] = $fields;
                }
                $CACHE_MANAGER->RegisterTag("iblock_id_new");
                $CACHE_MANAGER->EndTagCache();

                $obCache->EndDataCache($result);
            }
        }

        return $result;
    }

    public static function fillSectionXData(&$xdata,$arSections){
        foreach ($arSections as $arSection){
            $xdata[$arSection['MOBILE_ID']] = false;
            if(!empty($arSection['CHILD'])){
                self::fillSectionXData($xdata,$arSection['CHILD']);
            }
        }
    }
}
