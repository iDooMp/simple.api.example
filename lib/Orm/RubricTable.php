<?php
declare(strict_types=1);

namespace TwoQuick\Api\Orm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class RubricTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_list_rubric';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('RUBRIC_ENTITY_ID_FIELD'),
                ]
            ),
            new StringField(
                'LID',
                [
                    'required' => true,
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 2),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_LID_FIELD'),
                ]
            ),
            new StringField(
                'CODE',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 100),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_CODE_FIELD'),
                ]
            ),
            new StringField(
                'NAME',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 100),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_NAME_FIELD'),
                ]
            ),
            new TextField(
                'DESCRIPTION',
                [
                    'title' => Loc::getMessage('RUBRIC_ENTITY_DESCRIPTION_FIELD'),
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'default' => 100,
                    'title' => Loc::getMessage('RUBRIC_ENTITY_SORT_FIELD'),
                ]
            ),
            new BooleanField(
                'ACTIVE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                    'title' => Loc::getMessage('RUBRIC_ENTITY_ACTIVE_FIELD'),
                ]
            ),
            new BooleanField(
                'AUTO',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('RUBRIC_ENTITY_AUTO_FIELD'),
                ]
            ),
            new StringField(
                'DAYS_OF_MONTH',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 100),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_DAYS_OF_MONTH_FIELD'),
                ]
            ),
            new StringField(
                'DAYS_OF_WEEK',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 15),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_DAYS_OF_WEEK_FIELD'),
                ]
            ),
            new StringField(
                'TIMES_OF_DAY',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_TIMES_OF_DAY_FIELD'),
                ]
            ),
            new StringField(
                'TEMPLATE',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 100),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_TEMPLATE_FIELD'),
                ]
            ),
            new DatetimeField(
                'LAST_EXECUTED',
                [
                    'title' => Loc::getMessage('RUBRIC_ENTITY_LAST_EXECUTED_FIELD'),
                ]
            ),
            new BooleanField(
                'VISIBLE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                    'title' => Loc::getMessage('RUBRIC_ENTITY_VISIBLE_FIELD'),
                ]
            ),
            new StringField(
                'FROM_FIELD',
                [
                    'validation' => function () {
                        return [
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('RUBRIC_ENTITY_FROM_FIELD_FIELD'),
                ]
            ),
            new Reference(
                'LANG',
                '\Bitrix\Lang\Lang',
                ['=this.LID' => 'ref.LID'],
                ['join_type' => 'LEFT']
            ),
        ];
    }
}
