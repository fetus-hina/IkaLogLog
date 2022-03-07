<?php

/**
 * @copyright Copyright (C) 2015-2020 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\models;

use Throwable;
use jp3cki\uuid\Uuid;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "blog_entry".
 *
 * @property int $id
 * @property string $uuid
 * @property string $url
 * @property string $title
 * @property string $at
 */
final class BlogEntry extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'blog_entry';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'url', 'title', 'at'], 'required'],
            [['at'], 'safe'],
            [['url', 'title'], 'string', 'max' => 256],
            [['uuid'], 'string'],
            [['uuid'],
                function ($attribute, $params) {
                    if ($this->hasErrors($attribute)) {
                        return;
                    }
                    // error check and normalize
                    try {
                        $this->$attribute = (new Uuid($this->$attribute))->__toString();
                    } catch (Throwable $e) {
                        $this->addErrors($attribute, 'invalid uuid given');
                        return;
                    }
                },
            ],
            [['uuid'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uuid' => 'Uuid',
            'url' => 'Url',
            'title' => 'Title',
            'at' => 'At',
        ];
    }
}
