<?php
declare(strict_types=1);

namespace TwoQuick\Api\Helper;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use TwoQuick\Api\Config\Constants;

/**
 * Работа со страницей
 */
class Page
{
    /**
     * Константа для set_time_limit
     * @param integer
     */
    const SET_TIME_LIMIT = 60 * 5;

    /**
     * Устанавливает 404 ошибку
     * @param array $arParams
     * [
     * SET_STATUS_404 Устанавливать статус 404 Y|N
     * SHOW_404 Показ специальной страницы Y|N
     * FILE_404 Страница для показа (по умолчанию /404.php) Y|N
     * ]
     */
    public static function set404(array $arParams)
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException('Iblock module not installed');
        }

        \Bitrix\Iblock\Component\Tools::process404(
            "",
            ($arParams["SET_STATUS_404"] === "Y"),
            ($arParams["SET_STATUS_404"] === "Y"),
            ($arParams["SHOW_404"] === "Y"),
            $arParams["FILE_404"]
        );
    }

    /**
     * Устанавливает 403 ошибку
     * @param string $message сообщение на вывод
     */
    public static function set403(string $message = 'Доступ запрещен')
    {
        $response = Context::getCurrent()->getResponse();
        $response->setStatus('403 Forbidden');
        echo $message;
    }

    /**
     * Подключение модулей
     * Loader::includeModule()
     * @param array $modules
     * @throws SystemException
     */
    public static function initModules(array $modules)
    {
        foreach ($modules as $module) {
            if (!Loader::includeModule($module)) {
                throw new SystemException('module ' . $module . ' not installed');
            }
        }
    }

    /**
     * Возвращает значение кастомного заголовка x-frontend-uri-path
     * @return string
     */
    public static function getFrontendUri() : string
    {
        $request = Context::getCurrent()->getRequest();
        return htmlspecialchars((string)$request->getHeader('x-frontend-uri-path')) ?? '';
    }

    public static function getApiMethod()
    {
        $request = Context::getCurrent()->getRequest();
        return htmlspecialchars((string)str_replace(Constants::BASE_URI, '', $request->getRequestedPage()));
    }
}
