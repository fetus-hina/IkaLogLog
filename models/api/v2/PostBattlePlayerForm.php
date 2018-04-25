<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\models\api\v2;

use Yii;
use app\components\behaviors\FixAttributesBehavior;
use app\components\behaviors\TrimAttributesBehavior;
use app\models\FestTitle;
use app\models\Gender;
use app\models\Rank2;
use app\models\Weapon2;
use yii\base\Model;

class PostBattlePlayerForm extends Model
{
    public $team;
    public $is_me;
    public $weapon;
    public $level;
    public $star_rank;
    public $rank;
    public $rank_in_team;
    public $kill;
    public $death;
    public $kill_or_assist;
    public $special;
    public $point;
    public $my_kill;
    public $name;
    public $gender;
    public $fest_title;
    public $splatnet_id;
    public $top_500;

    public function behaviors()
    {
        return [
            [
                'class' => TrimAttributesBehavior::class,
                'targets' => array_keys($this->attributes),
            ],
            [
                'class' => FixAttributesBehavior::class,
                'attributes' => [
                    'weapon' => [
                        'manueuver' => 'maneuver', // issue #221
                        'manueuver_collabo' => 'maneuver_collabo', // issue #221
                        'publo_hue' => 'pablo_hue', // issue #301
                    ],
                    'fest_title' => [
                        'friend' => 'fiend', // issue #44
                    ],
                ],
            ],
        ];
    }

    public function rules()
    {
        return [
            [['team', 'is_me'], 'required'],
            [['team'], 'in', 'range' => [ 'my', 'his' ]],
            [['is_me'], 'boolean', 'trueValue' => 'yes', 'falseValue' => 'no'],
            [['weapon'], 'exist',
                'targetClass' =>  Weapon2::class,
                'targetAttribute' => 'key',
            ],
            [['level'], 'integer', 'min' => 1, 'max' => 99],
            [['star_rank'], 'integer'],
            [['rank'], 'exist',
                'targetClass' => Rank2::class,
                'targetAttribute' => 'key',
            ],
            [['rank_in_team'], 'integer', 'min' => 1, 'max' => 4],
            [['kill', 'death', 'my_kill'], 'integer', 'min' => 0],
            [['kill_or_assist', 'special'], 'integer', 'min' => 0],
            [['point'], 'integer', 'min' => 0],
            [['name'], 'string', 'max' => 10],
            [['gender'], 'in', 'range' => ['boy', 'girl']],
            [['fest_title'], 'string'],
            [['fest_title'], 'exist', 'skipOnError' => true,
                'targetClass' => FestTitle::class,
                'targetAttribute' => 'key',
            ],
            [['splatnet_id'], 'string', 'max' => 16],
            [['top_500'], 'boolean', 'trueValue' => 'yes', 'falseValue' => 'no'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }
}
