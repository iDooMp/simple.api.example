<?php
declare(strict_types=1);

namespace TwoQuick\Api;

use Bitrix\Main\Routing\Options;

class RouterOptions extends Options
{

    public function getMiddlewareClassList()
    {
        foreach ($this->middleware as &$middleware) {
            if (class_exists($middleware)) {
                $middleware = new $middleware;
            }
        }
        unset($middleware);
        return $this->middleware;
    }
}
