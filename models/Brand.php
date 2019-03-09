<?php
/**
 * @copyright Copyright (C) 2015 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\models;

use Yii;
use app\components\helpers\Translator;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "brand".
 *
 * @property integer $id
 * @property string $key
 * @property string $name
 * @property integer $strength_id
 * @property integer $weakness_id
 *
 * @property Ability $strength
 * @property Ability $weakness
 */
class Brand extends \yii\db\ActiveRecord
{
    use openapi\Util;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'brand';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key', 'name'], 'required'],
            [['strength_id', 'weakness_id'], 'integer'],
            [['key', 'name'], 'string', 'max' => 32],
            [['key'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Key',
            'name' => 'Name',
            'strength_id' => 'Strength ID',
            'weakness_id' => 'Weakness ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrength()
    {
        return $this->hasOne(Ability::className(), ['id' => 'strength_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWeakness()
    {
        return $this->hasOne(Ability::className(), ['id' => 'weakness_id']);
    }

    public function toJsonArray()
    {
        return [
            'key' => $this->key,
            'name' => Translator::translateToAll('app-brand', $this->name),
            'strength' => $this->strength ? $this->strength->toJsonArray() : null,
            'weakness' => $this->weakness ? $this->weakness->toJsonArray() : null,
        ];
    }

    public static function openApiSchema(): array
    {
        $values = static::find()
            ->orderBy(['key' => SORT_ASC])
            ->all();
        return [
            'type' => 'object',
            'description' => Yii::t('app-apidoc1', 'Brand information'),
            'properties' => [
                'key' => static::oapiKey(
                    static::oapiKeyValueTable(
                        Yii::t('app-apidoc1', 'Brand'),
                        'app-brand',
                        $values
                    ),
                    ArrayHelper::getColumn($values, 'key', false)
                ),
                'name' => static::oapiRef(openapi\Name::class),
                'strength' => array_merge(Ability::openApiSchema(), [
                    'description' => Yii::t('app-apidoc1', 'Common ability'),
                    'nullable' => true,
                ]),
                'weakness' => array_merge(Ability::openApiSchema(), [
                    'description' => Yii::t('app-apidoc1', 'Uncommon ability'),
                    'nullable' => true,
                ]),
            ],
            'example' => $values[0]->toJsonArray(),
        ];
    }

    public static function openApiDepends(): array
    {
        return [
            openapi\Name::class,
        ];
    }

    public static function openapiExample(): array
    {
        $models = static::find()
            ->orderBy(['key' => SORT_ASC])
            ->all();
        return array_map(
            function ($model) {
                return $model->toJsonArray();
            },
            $models
        );
    }
}
