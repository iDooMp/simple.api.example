<?php
declare(strict_types=1);

namespace TwoQuick\Api\Exception;

class JsonParseException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
