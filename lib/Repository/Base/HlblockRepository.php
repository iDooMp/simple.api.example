<?php
declare(strict_types=1);

namespace TwoQuick\Api\Repository\Base;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use TwoQuick\Api\Entity\Pagination;
use TwoQuick\Api\Exception\BitrixOrmException;
use Bitrix\Highloadblock as HL;
use TwoQuick\Api\Exception\NotFoundModuleException;
use TwoQuick\Api\Exception\Repository\HLblockCodeEmptyException;

abstract class HlblockRepository implements RepositoryInterface {
    protected $hlBlockCode = null;
    protected $cacheTtl = 3600000;
    protected $order = [];
    protected $filter = [];
    protected $select = [];
    protected $offset = 0;
    protected $limit = 10;
    protected $count = 0;
    protected $page = 1;
    protected $maxLevelQueryLimit = 100;

    /**
     * @var Query
     */
    protected $query;

    public function __construct(){
        if (!$this->hlBlockCode){
            throw new HLblockCodeEmptyException('Highload block API code is empty in ' . static::class);
        }
        if(!Loader::includeModule('highloadblock')){
            throw new NotFoundModuleException('highloadblock');
        }
        $this->prepareQuery();
    }


    /**
     * устанавливает параметры для выборки данных и пагинации, заполняет поля объекта
     * @param array $params
     */
    public function setParams(array $params){
        if(array_key_exists('limit', $params) && $params['limit'] >= 1){
            if((int) $params['limit'] > $this->maxLevelQueryLimit){
                $params['limit'] = $this->maxLevelQueryLimit;
            }
            $this->limit = (int) $params['limit'];
        }
        if(array_key_exists('page', $params) && $params['page'] >= 1){
            $this->page = (int) $params['page'];
            $this->offset = ($this->page - 1) * $this->limit;
        }
    }
    /**
     * @return HL\EO_HighloadBlock_Entity
     * @throws \Exception
     */

    protected function prepareQuery(){
        try {
            $this->query = HL\HighloadBlockTable::compileEntity($this->hlBlockCode)->getDataClass()::query()
                ->setOrder($this->order)
                ->setFilter($this->filter)
                ->setSelect($this->select)
                ->setOffset($this->offset)
                ->setLimit($this->limit)
            ;
        }catch (\Exception $ex){
            throw new BitrixOrmException($ex->getMessage(), $ex->getCode());
        }
    }

    protected function getElement(){
        try {
            return $this->query->fetchObject();
        }catch (\Exception $ex){
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * @return HL\EO_HighloadBlock_Collection
     * @throws \Exception
     */
    protected function getElements(){
        try {
            return $this->query->fetchCollection();
        }catch (\Exception $ex){
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * @return array
     */
    protected function getFilter() : array
    {
        return $this->query->getFilter();
    }

    /**
     * @param array $filter
     */
    protected function setFilter(array $filter): void
    {
        $this->query->setFilter($filter);
    }

    /**
     * @return array
     */
    protected function getSelect() : array
    {
        return $this->query->getSelect();
    }

    /**
     * @param array $select
     */
    protected function setSelect(array $select): void
    {
        $this->query->setSelect($select);
    }

    /**
     * @return array
     */
    protected function getOrder() : array
    {
        return $this->query->getOrder();
    }

    /**
     * @param array $order
     */
    protected function setOrder(array $order): void
    {
        $this->query->setOrder($order);
    }

    /**
     * @return Query
     */
    protected function getQuery() : Query
    {
        return $this->query;
    }

    /**
     * @return \Bitrix\Highloadblock\EO_HighloadBlock_Entity
     */
    protected function getEntity(): \Bitrix\Highloadblock\EO_HighloadBlock_Entity
    {
        return $this->query->getEntity();
    }

    /**
     * Получаем пагинацию
     * @return Pagination
     */
    public function getPagination() : Pagination
    {
        return new Pagination(
            $this->count,
            $this->page,
            $this->offset,
            $this->limit
        );
    }
}
