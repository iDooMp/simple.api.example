<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Service;

use Bitrix\Main\Loader;
use TwoQuick\Api\Service\Base\Service;

Loader::includeModule('iblock');
Loader::includeModule('search');

class SearchService extends Service
{

    /**
     * @var array
     */
    private array $result;

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->result;
    }

    /**
     * @param $q
     *
     * @return void
     */
    public function search($q): void
    {
        $arResult["CATEGORIES"]['PRODUCTS'] = ['NAME' => '-'];
        $module_id = "iblock";
        $obSearch = new \CSearch();
        $obSearch->Search([
            "QUERY" => $q,
            "SITE_ID" => LANG,
            "MODULE_ID" => $module_id,
            "PARAM2" => [IBLOCK_SHORT_RENT_CATALOG_ID, IBLOCK_LONG_RENT_CATALOG_ID, IBLOCK_SELL_CATALOG_ID],
        ]);

        while ($item = $obSearch->GetNext()) {
            $arResult["CATEGORIES"]['PRODUCTS']['ITEMS'][] = $item;
        }

        $arSectionsId = $arIblocksId = [];

        foreach ($arResult["CATEGORIES"] as &$arCategory) {
            foreach ($arCategory["ITEMS"] as &$arItem) {
                $arItem['NAME'] = strip_tags($arItem['TITLE']);
                if ($arItem['MODULE_ID'] == 'iblock' && !str_contains($arItem['PARAM2'], 'S')) {
                    $arIblocksId[$arItem['PARAM2']] = $arItem['PARAM2'];
                }
                if (str_contains($arItem['ITEM_ID'], 'S')) {
                    $arItem['SECTION_ID'] = str_replace('S', '', $arItem['ITEM_ID']);
                    $arSectionsId[$arItem['PARAM2']][] = $arItem['SECTION_ID'];
                }
            }
        }

        $arResult['IBLOCK_INFO'] = $this->getIblockInfo($arIblocksId);
        $arResult['SECTIONS_INFO'] = $this->getSectionsInfo($arSectionsId);

        $brandsItems = $itemsToUnset = [];

        foreach ($arResult["CATEGORIES"] as $category_id => &$arCategory) {
            foreach ($arCategory["ITEMS"] as $i => &$arItem) {
                if ($iblockInfo = $arResult['IBLOCK_INFO'][$arItem['PARAM2']] ?? null) {
                    $arItem['IBLOCK_NAME'] = $iblockInfo['NAME'];
                }
                if (!empty($arItem['SECTION_ID']) && ($sectionInfo = $arResult['SECTIONS_INFO'][$arItem['PARAM2']][$arItem['SECTION_ID']] ?? null)) {
                    $arItem['PICTURE'] = $sectionInfo['PICTURE'] ?? null;
                    if ($sectionInfo['UF_SHOW_AS_BRAND'] == 1) {
                        $brandsItems[] = $arItem;
                        $itemsToUnset[] = [$category_id, $i];
                    }
                }
            }
        }

        if ($brandsItems) {
            $arResult["CATEGORIES"]['BRANDS'] = [
                'TITLE' => 'Бренды',
                'ITEMS' => $brandsItems,
            ];
        }

        foreach ($itemsToUnset as [$category_id, $i]) {
            unset($arResult["CATEGORIES"][$category_id]['ITEMS'][$i]);
        }

        $this->result = [
            'html' => $this->generateHtml($arResult["CATEGORIES"]),
        ];
    }

    /**
     * @param $arIblocksId
     *
     * @return array
     */
    private function getIblockInfo($arIblocksId): array
    {
        $iblockInfo = [];
        if ($arIblocksId) {
            $res = \CIBlock::GetList([], ['SITE_ID' => SITE_ID, 'ID' => $arIblocksId, 'ACTIVE' => 'Y']);
            while ($ar_res = $res->Fetch()) {
                $iblockInfo[$ar_res['ID']] = $ar_res;
            }
        }
        return $iblockInfo;
    }

    /**
     * @param $arSectionsId
     *
     * @return array
     */
    private function getSectionsInfo($arSectionsId): array
    {
        $sectionsInfo = [];
        foreach ($arSectionsId as $iblockId => $sectionIds) {
            $sect = \CIBlockSection::GetList(['sort' => 'asc'], ['IBLOCK_ID' => $iblockId, 'ID' => $sectionIds], false, ['ID', 'IBLOCK_ID', 'PICTURE', 'UF_SHOW_AS_BRAND']);
            while ($section = $sect->GetNext()) {
                if ($section['PICTURE']) {
                    $section['PICTURE'] = \CFile::GetFileArray($section['PICTURE'])['SRC'];
                }
                $sectionsInfo[$iblockId][$section['ID']] = $section;
            }
        }
        return $sectionsInfo;
    }

    /**
     * @param $categories
     * @param $type
     *
     * @return string
     */
    private function generateHtml($categories): string
    {
        $html = '';
        foreach ($categories as $category) {
            if (!empty($category["TITLE"]) && $category['TITLE'] != '-') {
                $html .= sprintf('<div class="text-lg text-black font-semibold p-[10px] border-t border-gray-20">%s</div>', $category["TITLE"]);
            }
            foreach ($category["ITEMS"] as $item) {
                if ($item['TYPE'] === 'all') {
                    continue;
                }

                $html .= '<div class="flex flex-col gap-2.5 p-[10px] radius hover:bg-gray-100 cursor-pointer">';

                $picture = '';
                if (!empty($item['PICTURE'])) {
                    $picture = sprintf('<img src="%s" alt="%s" class="h-[18px] object-contain max-w-[100px] shrink-0">', $item['PICTURE'], $item['NAME']);
                }
                $html .= sprintf('<div class="flex justify-between gap-5 items-center md:flex-col md:items-start"><span>%s</span> %s', $item["IBLOCK_NAME"],$picture);

                $html .= sprintf('</div><a href="%s" class="text-sm text-gray-30 flex items-center gap-2.5">%s<svg width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.3536 4.35355C12.5488 4.15829 12.5488 3.84171 12.3536 3.64645L9.17157 0.464466C8.97631 0.269204 8.65973 0.269204 8.46447 0.464466C8.2692 0.659728 8.2692 0.976311 8.46447 1.17157L11.2929 4L8.46447 6.82843C8.2692 7.02369 8.2692 7.34027 8.46447 7.53553C8.65973 7.7308 8.97631 7.7308 9.17157 7.53553L12.3536 4.35355ZM0 4.5H12V3.5H0V4.5Z" fill="#666666"></path></svg></a></div>', $item["URL"], $item['NAME']);

                $html .= '</div>';
            }
        }
        return $html;
    }
}
