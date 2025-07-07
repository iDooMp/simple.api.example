<?php
declare(strict_types=1);

namespace TwoQuick\Api\Helper;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\HttpResponse;

/**
 * Хелпер для работы с сервером
 */
class Server
{
    public const DEFAULT_ALLOW_ORIGIN = '*';
    public const DEFAULT_ALLOW_CREDENTIALS = 'false';
    public const DEFAULT_ALLOW_METHODS = [
        'GET',
        'POST',
        'DELETE',
        'PUT',
        'PATCH',
        'OPTIONS',
        'HEAD',
    ];

    public const DEFAULT_ALLOW_HEADERS = [
        'Content-Type',
        'Origin',
        'Authorization',
        'x-frontend-uri-path',
    ];

    public const DEFAULT_MAX_AGE = 5;

    public const SEPARATOR_HEADER_VALUES = ', ';

    /**
     * Возвращает url для печати, пример: http://localhost
     * Используем в консоли, где нет серверных переменных
     * @return string
     * @throws ArgumentNullException
     */
    public static function getUrl(): string
    {
        $serverName = Option::get("main", "server_name", Context::getCurrent()->getServer()->getServerName());

        return self::getUrlProtocol() . "://" . $serverName;
    }

    /**
     * Протокол URL-a. Используем в консоли, где нет SERVER
     *
     * @return string
     * @throws ArgumentNullException
     */
    public static function getUrlProtocol(): string
    {
        return Option::get("main", "mail_link_protocol", 'http');
    }

    public static function getSanitizedString(string $str): string
    {
        $str = trim($str);
        $str = stripslashes($str);
        $str = strip_tags($str);
        return $str;
    }

    /**
     * Возвращает имя сервера из серверной переменной
     *
     * @return string
     */
    public static function getName(): string
    {
        return Context::getCurrent()->getServer()->getServerName();
    }

    /**
     * добавляет заголовки Access-Control для предотвращения ошибки CORS
     * @param HttpResponse $response
     * @return void
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public static function setAccessControlHeaders(HttpResponse $response) : void
    {
        $headers = Configuration::getInstance('twoquick.api')->get('headers');
        $response->addHeader(
            'Access-Control-Allow-Origin',
            $headers['allowOrigin'] ?? self::DEFAULT_ALLOW_ORIGIN
        );
        $response->addHeader(
            'Access-Control-Allow-Credentials',
            $headers['allowCredentials'] ?? self::DEFAULT_ALLOW_CREDENTIALS
        );
        $response->addHeader(
            'Access-Control-Allow-Methods',
            $headers['allowMethods'] ?? implode(self::SEPARATOR_HEADER_VALUES, self::DEFAULT_ALLOW_METHODS)
        );
        $response->addHeader(
            'Access-Control-Allow-Headers',
            $headers['allowHeaders'] ?? implode(self::SEPARATOR_HEADER_VALUES, self::DEFAULT_ALLOW_HEADERS)
        );
        $response->addHeader(
            'Access-Control-Max-Age',
            $headers['maxAge'] ?? self::DEFAULT_MAX_AGE
        );

        if(!empty($_SERVER['HTTP_USER_SESSION'])) {
            $GLOBALS['USER_SESSION'] = $_SERVER['HTTP_USER_SESSION'];
        }
    }
}
