<?php
declare(strict_types=1);

namespace TwoQuick\Api\Repository\Base\Iblock;

use TwoQuick\Api\Exception\BitrixOrmException;
use TwoQuick\Api\Repository\Base\IblockRepository;

abstract class SectionRepository extends IblockRepository
{
    protected function initEntity()
    {
        $this->entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->iblockApiCode);
    }

    protected function prepareQuery()
    {
        try {
            $this->query = new \Bitrix\Main\Entity\Query($this->entity);

            $this->query
                ->setOrder($this->order)
                ->setFilter($this->filter)
                ->setSelect($this->select)
                ->setOffset($this->offset)
                ->setLimit($this->limit);

        } catch (\Exception $ex) {
            throw new BitrixOrmException($ex->getMessage());
        }

    }

    public function getSeoSection($code): \TwoQuick\Api\Entity\SeoInfo
    {
        try {
            $this->prepareQuery();
            $this->addFilter('CODE', $code);
            $section = $this->query->fetchObject();
            $this->prepareSeoInfo($section->getId(), self::SECTION);
            return $this->getSeoInfo();
        } catch (\Exception $ex) {
            throw new BitrixOrmException($ex->getMessage());
        }
    }

}
