<?php
declare(strict_types=1);

namespace TwoQuick\Api\Service\Base;

abstract class Service implements ServiceInterface
{
    public $params = [];
    protected $data = [];
}
