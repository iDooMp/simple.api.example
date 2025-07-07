<?php
declare(strict_types=1);

namespace TwoQuick\Api\ActionFilter;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use TwoQuick\Api\Service\AuthService;
use TwoQuick\Api\Service\ErrorService;

class AuthAction extends Base
{

    private bool $enabled;

    /**
     * Token constructor.
     *
     * @param bool $enabled
     */
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
        parent::__construct();
    }

    /**
     * @param Event $event
     * @return |null
     * @throws \Exception
     */
    public function onBeforeAction(Event $event)
    {
        if (!$this->enabled) {
            return null;
        }

        if (!AuthService::checkAuthWithJWT()) {
            ErrorService::show(403, 'You are not authorize');
        }

        return null;
    }
}
