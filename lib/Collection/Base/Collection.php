<?php
declare(strict_types=1);

namespace TwoQuick\Api\Collection\Base;

use TwoQuick\Api\Entity\Base\EntityInterface;

abstract class Collection extends \ArrayIterator implements CollectionInterface
{
    public function toArray(): array
    {
        return (array)$this;
    }

    /**
     * @param $entity
     * @throws \Exception
     */
    public function add($entity): void
    {
        if (!($entity instanceof EntityInterface)) {
            throw new \Exception('Входящий объект не относится к интерфейсу EntityInterface', 500);
        }

        parent::append($entity);
    }

    /** @Deprecated */
    public function append($value)
    {
        throw new \Exception('Данный метод запрещён, используйте ' . static::class . '::add()', 500);
    }

    public function getArrayCopy()
    {
        throw new \Exception('Данный метод запрещён, используйте ' . static::class . '::toArray()', 500);
    }
}
