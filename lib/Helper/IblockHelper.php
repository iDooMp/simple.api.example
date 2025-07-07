<?php
declare(strict_types=1);

namespace TwoQuick\Api\Helper;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Property;
use Bitrix\Main\Loader;
use TwoQuick\Api\Exception\NotFoundIblockException;
use TwoQuick\Api\Exception\NotFoundModuleException;
use TwoQuick\Api\Singleton;

class IblockHelper extends Singleton
{

    protected function __construct()
    {
        if (!Loader::includeModule('iblock')) {
            throw new NotFoundModuleException('iblock');
        }
    }

    /**
     * @param string $code
     * @return int
     * @throws NotFoundIblockException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getIblockId(string $code): int
    {
        $iblock = IblockTable::getList([
            'filter' => ['=CODE' => $code],
            'select' => ['ID'],
            'limit' => 1,
            'cache' => ['ttl' => 86400]
        ])->fetch();

        if (!$iblock || !is_array($iblock)) {
            throw new NotFoundIblockException('Инфоблок с кодом ' . $code . ' не найден');
        }
        return (int)$iblock['ID'];
    }

    /**
     * @param string $iblockApiCode
     * @return int
     * @throws NotFoundIblockException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getIblockIdByApiCode(string $iblockApiCode): int
    {
        $iblock = IblockTable::getList([
            'filter' => ['=API_CODE' => $iblockApiCode],
            'select' => ['ID'],
            'limit' => 1,
            'cache' => ['ttl' => 86400]
        ])->fetch();

        if (!$iblock || !is_array($iblock)) {
            throw new NotFoundIblockException('Инфоблок с кодом ' . $iblockApiCode . ' не найден');
        }
        return (int)$iblock['ID'];
    }
}
