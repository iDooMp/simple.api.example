<?php
declare(strict_types=1);

namespace TwoQuick\Api\Repository\Base;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Web\Uri;
use TwoQuick\Api\Entity\Pagination;
use TwoQuick\Api\Entity\SeoInfo;
use TwoQuick\Api\Exception\BitrixOrmException;
use TwoQuick\Api\Exception\Repository\IblockCodeEmptyException;
use TwoQuick\Api\Helper\IblockHelper;
use TwoQuick\Api\Helper\Page;
use TwoQuick\Api\Repository\SeoRepository;

abstract class IblockRepository implements RepositoryInterface
{
    const SECTION = 'SECTION';
    const ELEMENT = 'ELEMENT';

    protected $iblockCode = '';
    protected $iblockApiCode = '';
    protected $iblockId = 0;
    protected $cacheTtl = 3600000;
    protected $order = [];
    protected $filter = [];
    protected $select = [];
    protected $offset = 0;
    protected $limit = 10;
    protected $count = 0;
    protected $page = 1;
    protected $maxLevelQueryLimit = 1000;
    /** @var Query */
    protected $query;
    protected $entity;

    protected ?SeoInfo $seoInfo = null;
    protected $breadcrumbs;
    protected string $pageUrl;

    abstract protected function prepareQuery();

    abstract protected function initEntity();

    public function __construct()
    {
        if (!$this->iblockApiCode) {
            throw new IblockCodeEmptyException('Iblock API code is empty in ' . static::class);
        }

        $this->iblockId = $this->getIblockId();
        $this->initEntity();
    }

    public function getIblockId(): int
    {
        if ($this->iblockCode) {
            return IblockHelper::getInstance()->getIblockId($this->iblockCode);
        } else {
            return IblockHelper::getInstance()->getIblockIdByApiCode($this->iblockApiCode);
        }
    }

    /**
     * @param array $filter
     */
    public function setFilter(array $filter): void
    {
        $this->query->setFilter($filter);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function addFilter($key, $value): void
    {
        $this->query->addFilter($key, $value);
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->query->getSelect();
    }

    /**
     * @param array $select
     */
    public function setSelect(array $select): void
    {
        $this->query->setSelect($select);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->query->getOrder();
    }

    /**
     * @param array $order
     */
    public function setOrder(array $order): void
    {
        $this->query->setOrder($order);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getEntity()
    {
        return $this->query->getEntity();
    }

    /**
     * устанавливает параметры для выборки данных и пагинации, заполняет поля объекта
     * @param array $params
     */
    public function setParams(array &$params)
    {
        if (array_key_exists('limit', $params) && $params['limit'] >= 1) {
            if ((int)$params['limit'] > $this->maxLevelQueryLimit) {
                $params['limit'] = $this->maxLevelQueryLimit;
            }
            $this->limit = (int)$params['limit'];
            unset($params['limit']);
        }
        if (array_key_exists('page', $params) && $params['page'] >= 1) {
            $this->page = (int)$params['page'];
            $this->offset = ($this->page - 1) * $this->limit;
            unset($params['page']);
        }
    }

    /**
     * Получаем пагинацию
     * @return Pagination
     */
    public function getPagination(): Pagination
    {
        if (!$this->count && $this->query instanceof Query) {
            $this->query->countTotal(true);
            $this->count = (int)$this->query->queryCountTotal();
        }

        return new Pagination(
            $this->page,
            (int)ceil($this->count / $this->limit),
            $this->limit,
            $this->count,
        );
    }

    public function getSeoInfo(): SeoInfo
    {
        return $this->seoInfo ?? new SeoInfo();
    }

    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }

    /**
     * Подготовка полей для блока сео
     *
     * @param int $id
     * @param string $entityType
     * @return void
     * @throws BitrixOrmException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function prepareSeoInfo(int $id, string $entityType)
    {
        $customSeoInfo = $this->getSeoInfoHL();
        if ($entityType === self::ELEMENT) {
            $ipropValues = (new \Bitrix\Iblock\InheritedProperty\ElementValues($this->getIblockId(), $id))->getValues();
        } elseif ($entityType === self::SECTION) {
            $ipropValues = (new \Bitrix\Iblock\InheritedProperty\SectionValues($this->getIblockId(), $id))->getValues();
        }

        $this->seoInfo = new SeoInfo();

        if (is_array($ipropValues)) {
            $this->seoInfo->title = !empty($customSeoInfo->title) ? $customSeoInfo->title : (string)$ipropValues["{$entityType}_META_TITLE"];
            $this->seoInfo->description = !empty($customSeoInfo->description) ? $customSeoInfo->description : (string)$ipropValues["{$entityType}_META_DESCRIPTION"];
            $this->seoInfo->keywords = !empty($customSeoInfo->keywords) ? $customSeoInfo->keywords : (string)$ipropValues["{$entityType}_META_KEYWORDS"];
            $this->seoInfo->canonical = (string)$this->getCanonicalPageUrl();
        }
    }

    protected function getCanonicalPageUrl()
    {
        $iblock = IblockTable::query()
            ->where('API_CODE', $this->iblockApiCode)
            ->addSelect('CANONICAL_PAGE_URL')
            ->fetch();

        return $iblock['CANONICAL_PAGE_URL'];
    }
    public function getIblockInfo()
    {

        return IblockTable::query()
            ->where('API_CODE', $this->iblockApiCode)
            ->addSelect('NAME')
            ->addSelect('DESCRIPTION')
            ->addSelect('PICTURE')
            ->fetch();
    }

    /**
     * возвращает объект SeoInfo сформированного из таблицы HL Блока Seo по урлу получаемому из фронтенда или если нет
     * урла, по uri метода апи
     * @return SeoInfo
     * @throws BitrixOrmException
     */
    protected function getSeoInfoHL(): SeoInfo
    {
        $uri = new Uri(Page::getFrontendUri());

        if (!$uri->getPath()) {
            return (new SeoRepository())->getSeoInfoByApiMethod(Page::getApiMethod());
        }

        return (new SeoRepository())->getSeoInfoByUrl($uri->getPath());
    }

    protected function preparePageUrl(string $url): void
    {
        $this->pageUrl = $url;
    }

}
