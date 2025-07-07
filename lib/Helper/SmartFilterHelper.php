<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Helper;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;


final class SmartFilterHelper
{
    private int $iblockId;
    private ?array $sectionsId;

    public function __construct(int $iblockId, ?array $arSectionsId = null)
    {
        $this->iblockId = $iblockId;
        $this->sectionsId = $arSectionsId;
    }

    public function getFilter(): array
    {
        $filter = [
            'IBLOCK_ID' => $this->iblockId,
            'ACTIVE' => 'Y',
        ];

        if (!empty($this->sectionsId)) {
            //$filter['SUBSECTION'] = $this->sectionsId;
            $filter['SECTION_ID'] = $this->sectionsId;
            $filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }

        return $filter;
    }

    public function getSectionList(): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $sections = [];
        $result = SectionTable::getList([
            'filter' => [
                'DEPTH_LEVEL' => 1,
                'IBLOCK_ID' => $this->iblockId,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME'],
            'order' => ['LEFT_MARGIN' => 'ASC'],
        ]);

        while ($section = $result->fetch()) {
            $sections[] = [
                'ID' => (int)$section['ID'],
                'NAME' => $section['NAME'],
                'CHECKED' => in_array($section['ID'], $this->sectionsId ?: []),
            ];
        }

        return $sections;
    }
}