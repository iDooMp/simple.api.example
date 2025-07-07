<?php
declare(strict_types=1);

namespace TwoQuick\Api\Exception;

use Throwable;

class PageNotFoundException extends \Exception{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = $message ?: 'HTTP/1.0 404 Not Found';
        $code = $code ?: 404;
        parent::__construct($message, $code, $previous);
    }
}
