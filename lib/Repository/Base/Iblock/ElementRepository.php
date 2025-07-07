<?php
declare(strict_types=1);

namespace TwoQuick\Api\Repository\Base\Iblock;

use Bitrix\Iblock\IblockTable;
use Ecmu\Api\Entity\SeoInfo;
use TwoQuick\Api\Exception\BitrixOrmException;
use TwoQuick\Api\Exception\PageNotFoundException;
use TwoQuick\Api\Repository\Base\IblockRepository;

abstract class ElementRepository extends IblockRepository
{
    protected $filter = [];
    protected $select = [];
    protected $order = [];

    protected function initEntity()
    {
        $this->entity = IblockTable::compileEntity($this->iblockApiCode);
    }

    protected function prepareQuery()
    {
        try {
            $this->query = $this->entity->getDataClass()::query()
                ->setOrder($this->order)
                ->setFilter($this->filter)
                ->setSelect($this->select)
                ->setOffset($this->offset)
                ->setLimit($this->limit);

        } catch (\Exception $ex) {
            throw new BitrixOrmException($ex->getMessage(), $ex->getCode());
        }
    }

    protected function getSubsections(string $categoryCode)
    {
        try {
            $section = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->iblockApiCode)::query()
                ->setLimit(1)
                ->addFilter('=CODE', $categoryCode)
                ->setSelect(['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'])
                ->fetchObject();

            if (!$section) {
                throw new PageNotFoundException("Раздел {$categoryCode} не найден");
            }

            $subSections = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->iblockApiCode)::query()
                ->setLimit(0)
                ->addFilter('>=LEFT_MARGIN', $section->getLeftMargin())
                ->addFilter('<=RIGHT_MARGIN', $section->getRightMargin())
                ->setSelect(['ID'])
                ->fetchCollection();

            return $subSections;
        } catch (\Exception $ex) {
            throw new BitrixOrmException($ex->getMessage(), $ex->getCode());
        }
    }

    protected function getSections(array $sectionsId)
    {
        return \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->iblockApiCode)::query()
            ->setLimit(0)
            ->addFilter('ID', $sectionsId)
            ->setSelect(['ID', 'NAME', 'CODE',])
            ->fetchCollection();

    }

    /**
     * Получение Сео по коду секции
     * @param $code
     * @return SeoInfo
     * @throws \Ecmu\Api\Exception\BitrixOrmException
     */
    public function getSeoElement($code): \TwoQuick\Api\Entity\SeoInfo
    {
        try {
            $this->prepareQuery();
            $this->addFilter('CODE', $code);
            $section = $this->query->fetchObject();
            $this->prepareSeoInfo($section->getId(), self::ELEMENT);
            return $this->getSeoInfo();
        } catch (\Exception $ex) {
            throw new BitrixOrmException($ex->getMessage());
        }
    }

}
