<?php

/**
 * @copyright Copyright (C) 2017-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "controller_mode2".
 *
 * @property integer $id
 * @property string $key
 * @property string $name
 *
 * @property Playstyle2[] $playstyles
 * @property NsMode2[] $nsModes
 */
class ControllerMode2 extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'controller_mode2';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key', 'name'], 'required'],
            [['key'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 32],
            [['key'], 'unique'],
            [['name'], 'unique'],
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
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getPlaystyles()
    {
        return $this->hasMany(Playstyle2::class, ['controller_mode_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getNsModes()
    {
        return $this->hasMany(NsMode2::class, ['id' => 'ns_mode_id'])
            ->viaTable('playstyle2', ['controller_mode_id' => 'id']);
    }
}
