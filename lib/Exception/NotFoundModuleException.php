<?php
declare(strict_types=1);

namespace TwoQuick\Api\Exception;

use Bitrix\Main\Localization\Loc;

class NotFoundModuleException extends \Exception
{
    public function __construct(string $moduleName)
    {
        $message = Loc::getMessage('NOT_FOUND_MODULE_EXCEPTION') . $moduleName;
        parent::__construct($message);
    }
}
