<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */
declare(strict_types=1);

namespace app\models\api\v2\salmon;

use Yii;
use app\components\behaviors\AutoTrimAttributesBehavior;
use app\models\Salmon2;
use app\models\SalmonEvent2;
use app\models\SalmonWaterLevel2;
use app\models\SalmonWave2;
use yii\base\Model;

class Wave extends Model
{
    public $known_occurrence;
    public $water_level;
    public $golden_egg_quota;
    public $golden_egg_appearances;
    public $golden_egg_delivered;
    public $power_egg_collected;

    public function behaviors()
    {
        return [
            AutoTrimAttributesBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['known_occurrence', 'water_level'], 'string'],
            [['golden_egg_quota'], 'integer',
                'min' => 1,
                'max' => 25,
            ],
            [['golden_egg_appearances', 'golden_egg_delivered', 'power_egg_collected'], 'integer',
                'min' => 0,
            ],
            [['known_occurrence'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonEvent2::class,
                'targetAttribute' => ['known_occurrence' => 'key'],
            ],
            [['water_level'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonWaterLevel2::class,
                'targetAttribute' => ['water_level' => 'key'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'known_occurrence' => Yii::t('app-salmon2', 'Known Occurrence'),
            'water_level' => Yii::t('app-salmon2', 'Water Level'),
            'golden_egg_quota' => Yii::t('app-salmon2', 'Golden Egg quota'),
            'golden_egg_appearances' => Yii::t('app-salmon2', 'Golden Egg appearances'),
            'golden_egg_delivered' => Yii::t('app-salmon2', 'Golden Egg delivered'),
            'power_egg_collected ' => Yii::t('app-salmon2', 'Power Egg collected'),
        ];
    }

    public function save(Salmon2 $work, int $waveNumber): bool
    {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->db->transactionEx(function () use ($work, $waveNumber): bool {
            $int = function ($value): ?int {
                if ($value === '' || $value === null) {
                    return null;
                }

                $value2 = filter_var($value, FILTER_VALIDATE_INT);
                if ($value2 === false) {
                    return null;
                }

                return $value2;
            };

            $keyValue = function (string $class, ?string $key): ?int {
                if ($key === null) {
                    return null;
                }

                if (!$model = $class::findOne(['key' => $key])) {
                    return null;
                }

                return (int)$model->id;
            };

            $model = Yii::createObject([
                'class' => SalmonWave2::class,
                'salmon_id' => (int)$work->id,
                'wave' => (int)$waveNumber,
                'event_id' => $keyValue(SalmonEvent2::class, $this->known_occurrence),
                'water_id' => $keyValue(SalmonWaterLevel2::class, $this->water_level),
                'golden_egg_quota' => $int($this->golden_egg_quota),
                'golden_egg_appearances' => $int($this->golden_egg_appearances),
                'golden_egg_delivered' => $int($this->golden_egg_delivered),
                'power_egg_collected' => $int($this->power_egg_collected),
            ]);

            if ($model->save()) {
                return true;
            }

            // copy errors
            foreach ($model->getErrors() as $attrName => $errors) {
                switch ($attrName) {
                    case 'event_id':
                        $attrName = 'known_occurrence';
                        break;

                    case 'water_id':
                        $attrName = 'water_level';
                        break;
                }

                if (is_array($errors)) {
                    foreach ($errors as $error) {
                        $this->addError($attrName, $error);
                    }
                } else {
                    $this->addError($attrName, $errors);
                }
            }
            return false;
        });
    }
}
