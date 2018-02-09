<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\models;

use Yii;
use app\components\helpers\Translator;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "rule2".
 *
 * @property integer $id
 * @property string $key
 * @property string $name
 * @property string $short_name
 *
 * @property ModeRule2[] $modeRules
 * @property Mode2[] $modes
 */
class Rule2 extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rule2';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key', 'name', 'short_name'], 'required'],
            [['key', 'short_name'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 32],
            [['key'], 'unique'],
            [['name'], 'unique'],
            [['short_name'], 'unique'],
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
            'short_name' => 'Short Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModeRules()
    {
        return $this->hasMany(ModeRule2::class, ['rule_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModes()
    {
        return $this->hasMany(Mode2::class, ['id' => 'mode_id'])->viaTable('mode_rule2', ['rule_id' => 'id']);
    }

    public function toJsonArray() : array
    {
        return [
            'key' => $this->key,
            'name' => Translator::translateToAll('app-rule2', $this->name),
        ];
    }

    public static function getSortedAll(?string $mode, $callback = null) : array
    {
        $query = static::find()->orderBy(['rule2.id' => SORT_ASC])->asArray();
        if ($mode) {
            $query->innerJoinWith('modes')
                ->andWhere(['mode2.key' => $mode]);
        }
        if ($callback && is_callable($callback)) {
            call_user_func($callback, $query);
        }
        $list = ArrayHelper::map(
            $query->all(),
            'key',
            function (array $row) : string {
                return Yii::t('app-rule2', $row['name']);
            }
        );
        return $list;
    }
}
