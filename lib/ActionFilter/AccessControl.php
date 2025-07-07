<?php
declare(strict_types=1);

namespace TwoQuick\Api\ActionFilter;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use TwoQuick\Api\Helper\Server;

class AccessControl extends Base
{

    /**
     * Handler of event `onBeforeAction`.
     *
     * @param Event $event
     * @return void
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function onBeforeAction(Event $event): void
    {
        Server::setAccessControlHeaders(Context::getCurrent()->getResponse());
    }

    /**
     * Handler of event `onAfterAction`.
     *
     * @param Event $event Event.
     * return void
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function onAfterAction(Event $event): void
    {
        Server::setAccessControlHeaders(Context::getCurrent()->getResponse());
    }
}
