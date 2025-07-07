<?php
declare(strict_types=1);

namespace TwoQuick\Api\Orm;


use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\TextField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class ContentTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_CHANGE datetime mandatory
 * <li> MODULE_ID string(50) mandatory
 * <li> ITEM_ID string(255) mandatory
 * <li> CUSTOM_RANK int optional default 0
 * <li> USER_ID int optional
 * <li> ENTITY_TYPE_ID string(50) optional
 * <li> ENTITY_ID string(255) optional
 * <li> URL text optional
 * <li> TITLE text optional
 * <li> BODY text optional
 * <li> TAGS text optional
 * <li> PARAM1 text optional
 * <li> PARAM2 text optional
 * <li> UPD string(32) optional
 * <li> DATE_FROM datetime optional
 * <li> DATE_TO datetime optional
 * </ul>
 *
 * @package Bitrix\Search
 **/

class ContentTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_search_content';
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
                    'title' => Loc::getMessage('CONTENT_ENTITY_ID_FIELD')
                ]
            ),
            new DatetimeField(
                'DATE_CHANGE',
                [
                    'required' => true,
                    'title' => Loc::getMessage('CONTENT_ENTITY_DATE_CHANGE_FIELD')
                ]
            ),
            new StringField(
                'MODULE_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateModuleId'],
                    'title' => Loc::getMessage('CONTENT_ENTITY_MODULE_ID_FIELD')
                ]
            ),
            new StringField(
                'ITEM_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateItemId'],
                    'title' => Loc::getMessage('CONTENT_ENTITY_ITEM_ID_FIELD')
                ]
            ),
            new IntegerField(
                'CUSTOM_RANK',
                [
                    'default' => 0,
                    'title' => Loc::getMessage('CONTENT_ENTITY_CUSTOM_RANK_FIELD')
                ]
            ),
            new IntegerField(
                'USER_ID',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_USER_ID_FIELD')
                ]
            ),
            new StringField(
                'ENTITY_TYPE_ID',
                [
                    'validation' => [__CLASS__, 'validateEntityTypeId'],
                    'title' => Loc::getMessage('CONTENT_ENTITY_ENTITY_TYPE_ID_FIELD')
                ]
            ),
            new StringField(
                'ENTITY_ID',
                [
                    'validation' => [__CLASS__, 'validateEntityId'],
                    'title' => Loc::getMessage('CONTENT_ENTITY_ENTITY_ID_FIELD')
                ]
            ),
            new TextField(
                'URL',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_URL_FIELD')
                ]
            ),
            new TextField(
                'TITLE',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_TITLE_FIELD')
                ]
            ),
            new TextField(
                'BODY',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_BODY_FIELD')
                ]
            ),
            new TextField(
                'TAGS',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_TAGS_FIELD')
                ]
            ),
            new TextField(
                'PARAM1',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_PARAM1_FIELD')
                ]
            ),
            new TextField(
                'PARAM2',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_PARAM2_FIELD')
                ]
            ),
            new StringField(
                'UPD',
                [
                    'validation' => [__CLASS__, 'validateUpd'],
                    'title' => Loc::getMessage('CONTENT_ENTITY_UPD_FIELD')
                ]
            ),
            new DatetimeField(
                'DATE_FROM',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_DATE_FROM_FIELD')
                ]
            ),
            new DatetimeField(
                'DATE_TO',
                [
                    'title' => Loc::getMessage('CONTENT_ENTITY_DATE_TO_FIELD')
                ]
            ),
        ];
    }

    /**
     * Returns validators for MODULE_ID field.
     *
     * @return array
     */
    public static function validateModuleId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for ITEM_ID field.
     *
     * @return array
     */
    public static function validateItemId()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for ENTITY_TYPE_ID field.
     *
     * @return array
     */
    public static function validateEntityTypeId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for ENTITY_ID field.
     *
     * @return array
     */
    public static function validateEntityId()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for UPD field.
     *
     * @return array
     */
    public static function validateUpd()
    {
        return [
            new LengthValidator(null, 32),
        ];
    }
}
