<?php

declare(strict_types = 1);

namespace TwoQuick\Api\Controller\Base;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Cors;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Error;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Response;
use Bitrix\Main\Web\HttpHeaders;
use Bitrix\Main\Web\Json;
use DI\ContainerBuilder;
use TwoQuick\Api\ActionFilter\AccessControl;
use TwoQuick\Api\ActionFilter\TokenAuth;
use TwoQuick\Api\RouterOptions;
use TwoQuick\Api\Service\Base\ServiceInterface;
use TwoQuick\Api\Helper\Server;
use TwoQuick\Api\Service\Base\Service;
use TwoQuick\Api\Service\TokenService;

abstract class Controller extends \Bitrix\Main\Engine\Controller
{
    protected string $service = '';

    public const DEFAULT_HTTP_STATUS = 200;
    public const UNPROCESSABLE_ERROR_STATUS = 422;

    public const PREFIX_API = '/api/v1';
    public const PREFIX_API_V2 = '/api/v2';

    public const API_V1 = 1;
    public const API_V2 = 2;

    /**
     * @param HttpResponse $response
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public function finalizeResponse(Response $response)
    {
        $status = self::DEFAULT_HTTP_STATUS;

        if ($error = current($this->getErrors())) {
            $status = $error->getCode() ?: self::DEFAULT_HTTP_STATUS;
        }

        if ($this->isApiVersion(self::API_V1)) {
            $content = Json::decode($response->getContent());
            $content['status'] = $status;
            $content['message'] = 'ok';
            if ($this->getErrors()) {
                $content['message'] = current($this->getErrors())->getMessage();
                foreach ($this->getErrors() as $error) {
                    if ($error->getCustomData()) {
                        $content['data'] = $error->getCustomData();
                    }
                }
            }
            unset($content['errors']);
            $response->setContent(Json::encode($content));
        }

        $response->setStatus($status);
    }

    protected function getDefaultPreFilters() : array
    {
        $options  = new RouterOptions();
        $options->mergeWith(Application::getInstance()->getRouter()->match($this->getRequest())->getOptions());
        $preFilters = [];

        return array_merge($options->getMiddlewareClassList(), $preFilters);
    }

    protected function getDefaultPostFilters() : array
    {
        return [];
    }


    protected function getParams(string $strParams) : array
    {
        $result = [];
        if (!empty($strParams)) {
            parse_str($strParams, $result);
        }
        return $result;
    }

    protected function sanitizeArgs(array &$args) : void
    {
        foreach ($args as &$arg) {
            if (is_string($arg)) {
                $arg = Server::getSanitizedString($arg);
            } else {
                if (is_array($arg)) {
                    $this->sanitizeArgs($arg);
                }
            }
        }
    }

    protected function sanitizeString(string &$str) : void
    {
        $str = Server::getSanitizedString($str);
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function getRequestJson() : array
    {
        return Json::decode(file_get_contents('php://input'));
    }

    protected function getService(string $service = '') : ServiceInterface
    {
        $service = !empty($service) ? $service : $this->service;
        return ServiceLocator::getInstance()->get($service);
    }

    protected function getUriPrefix() : string
    {
        return Application::getInstance()
            ->getRouter()
            ->match($this->request)
            ->getOptions()
            ->getFullPrefix();
    }

    protected function isApiVersion(int $version) : bool
    {
        preg_match('#^\/api\/v([\d]+).*#', $this->getUriPrefix(), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        return $matches[1] == $version;
    }

}