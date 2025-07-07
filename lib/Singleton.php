<?php
declare(strict_types=1);

namespace TwoQuick\Api;

class Singleton
{
    private static $instances = [];

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }
}
