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
use app\components\helpers\Battle as BattleHelper;
use app\models\Agent;
use app\models\Salmon2;
use app\models\SalmonBoss2;
use app\models\SalmonBossAppearance2;
use app\models\SalmonFailReason2;
use app\models\SalmonMap2;
use app\models\SalmonTitle2;
use jp3cki\uuid\Uuid;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\validators\NumberValidator;

class PostForm extends Model
{
    // Recommended UUID NS, splatnetNumber@principalID
    const UUID_NAMESPACE_BY_PRINCIPAL_ID = '418fe150-cb33-11e8-8816-d050998473ba';
    
    // If splatnet_number present
    const UUID_NAMESPACE_BY_SPLATNET_AND_USER_ID = 'b03116da-cbae-11e8-a7fa-d050998473ba';

    // If non-UUID
    const UUID_NAMESPACE_BY_FREETEXT = 'b007a6f6-cbae-11e8-aa3e-d050998473ba';

    public $client_uuid;
    public $splatnet_number;
    public $stage;
    public $clear_waves;
    public $fail_reason;
    public $title;
    public $title_exp;
    public $title_after;
    public $title_exp_after;
    public $danger_rate;
    public $boss_appearances;
    public $waves;
    public $shift_start_at;
    public $start_at;
    public $end_at;
    public $note;
    public $private_note;
    public $link_url;
    public $is_automated;
    public $agent;
    public $agent_version;

    private $uuid;

    public function behaviors()
    {
        return [
            AutoTrimAttributesBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['client_uuid', 'stage', 'fail_reason', 'title', 'title_after'], 'string'],
            [['note', 'private_note', 'agent', 'agent_version'], 'string'],
            [['splatnet_number'], 'integer'],
            [['clear_waves'], 'integer', 'min' => 0, 'max' => 3],
            [['title_exp', 'title_exp_after'], 'integer', 'min' => 0, 'max' => 999],
            [['danger_rate'], 'number', 'min' => 0, 'max' => 999.9],
            [['shift_start_at', 'start_at', 'end_at'], 'integer',
                'min' => strtotime('2018-10-03T11:00:00+09:00'), // Splatoon 2 v4.1 released
                'max' => time() + 3600, // クライアント側時計の誤差の受け入れのため余裕を持たせる
            ],
            [['link_url'], 'url'],
            [['is_automated'], 'in', 'range' => ['yes', 'no']],
            [['agent'], 'string', 'max' => 64],
            [['agent_version'], 'string', 'max' => 255],

            [['agent', 'agent_version'], 'required',
                'when' => function (self $model, string $attrName): bool {
                    return trim((string)$model->agent) !== '' ||
                        trim((string)$model->agent_version) !== '';
                },
            ],
            [['stage'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonMap2::class,
                'targetAttribute' => ['stage' => 'key'],
            ],
            [['title', 'title_after'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonTitle2::class,
                'targetAttribute' => 'key',
            ],
            [['boss_appearances'], 'validateBossAppearances'],
            [['waves'], 'validateWaves'],
        ];
    }

    public function validateBossAppearances()
    {
        if ($this->hasErrors('boss_appearances')) {
            return;
        }

        if ($this->boss_appearances === null || $this->boss_appearances === '') {
            $this->boss_appearances = null;
            return;
        }

        if (!is_array($this->boss_appearances)) {
            $this->addError('boss_appearances', 'boss_appearances should be an associative array');
            return;
        }

        if (empty($this->boss_appearances)) {
            $this->boss_appearances = null;
            return;
        }

        $countValidator = Yii::createObject([
            'class' => NumberValidator::class,
            'integerOnly' => true,
            'min' => 0,
        ]);
        foreach ($this->boss_appearances as $key => $value) {
            $boss = SalmonBoss2::findOne(['key' => (string)$key]);
            if (!$boss) {
                $this->addError('boss_appearances', sprintf('unknown key "%s"', (string)$key));
                continue;
            }

            $error = null;
            if (!$countValidator->validate($value, $error)) {
                $this->addError('boss_appearances', sprintf('%s: %s', $key, $error));
                continue;
            }
        }
    }

    public function validateWaves()
    {
        if ($this->hasErrors('waves')) {
            return;
        }

        if ($this->waves === null || $this->waves === '') {
            $this->waves = null;
            return;
        }

        if (!is_array($this->waves)) {
            $this->addError('waves', 'waves should be an array');
            return;
        }

        if (empty($this->waves)) {
            $this->waves = null;
            return;
        }

        if (!ArrayHelper::isIndexed($this->waves)) {
            $this->addError('waves', 'waves should be an array (not associative array)');
            return;
        }

        if (count($this->waves) > 3) {
            $this->addError('waves', 'too many waves');
            return;
        }

        for ($i = 0; $i < 3; ++$i) {
            $wave = $this->waves[$i] ?? null;
            if (!$wave) {
                break;
            }

            $model = Yii::createObject(Wave::class);
            $model->attributes = $wave;
            if (!$model->validate()) {
                foreach ($model->getErrors() as $key => $errors) {
                    foreach ((array)$errors as $error) {
                        $this->addError('waves', sprintf('%s: %s', $key, $error));
                    }
                }
            }
        }
    }

    public function save(): ?Salmon2
    {
        if (!$this->validate()) {
            return null;
        }

        return Yii::$app->db->transactionEx(function (): ?Salmon2 {
            if (!$main = $this->saveMain()) {
                return null;
            }

            if (!$this->saveBossAppearances($main)) {
                return null;
            }

            if (!$this->saveWaves($main)) {
                return null;
            }

            return $main;
        });
    }

    private function saveMain(): ?Salmon2
    {
        return Yii::$app->db->transactionEx(function (): ?Salmon2 {
            $agent = $this->getAgent();
            $model = Yii::createObject(Salmon2::class);
            $model->attributes = [
                'user_id' => Yii::$app->user->id,
                'uuid' => $this->getUuid(),
                'splatnet_number' => $this->splatnet_number,
                'stage_id' => static::findRelatedId(SalmonMap2::class, $this->stage),
                'clear_waves' => $this->clear_waves,
                'fail_reason_id' => static::findRelatedId(
                    SalmonFailReason2::class,
                    $this->fail_reason
                ),
                'title_before_id' => static::findRelatedId(SalmonTitle2::class, $this->title),
                'title_before_exp' => $this->title_exp,
                'title_after_id' => static::findRelatedId(SalmonTitle2::class, $this->title_after),
                'title_after_exp' => $this->title_exp_after,
                'danger_rate' => ($this->danger_rate == '')
                    ? null
                    : sprintf('%.1f', (float)$this->danger_rate),
                'shift_period' => ($this->shift_start_at == '')
                    ? null
                    : BattleHelper::calcPeriod2((int)$this->shift_start_at),
                'start_at' => ($this->start_at == '')
                    ? null
                    : gmdate(\DateTime::ATOM, (int)$this->start_at),
                'end_at' => ($this->end_at == '')
                    ? null
                    : gmdate(\DateTime::ATOM, (int)$this->end_at),
                'note' => $this->note,
                'private_note' => $this->private_note,
                'link_url' => $this->link_url,
                'is_automated' => ($this->is_automated == '')
                    ? ($agent ? $agent->getIsAutomatedByDefault() : null)
                    : ($this->is_automated === 'yes'),
                'agent_id' => $agent->id ?? null,
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.2',
                'remote_port' => (int)($_SERVER['REMOTE_PORT'] ?? 0),
            ];
            if (!$model->save()) {
                var_dump($model->attributes);
                var_dump($model->getErrors());
                exit;
                return null;
            }

            return $model;
        });
    }

    private function saveBossAppearances(Salmon2 $salmon): bool
    {
        return Yii::$app->db->transactionEx(function () use ($salmon): bool {
            if (!$this->boss_appearances) {
                return true;
            }

            foreach ($this->boss_appearances as $key => $value) {
                if ($value > 0) {
                    $bossId = static::findRelatedId(SalmonBoss2::class, $key);
                    if ($bossId === null) {
                        return false;
                    }

                    $model = Yii::createObject([
                        'class' => SalmonBossAppearance2::class,
                        'salmon_id' => $salmon->id,
                        'boss_id' => $bossId,
                        'count' => (int)$value,
                    ]);
                    if (!$model->save()) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    private function saveWaves(Salmon2 $salmon): bool
    {
        return Yii::$app->db->transactionEx(function () use ($salmon): bool {
            if (!$this->waves) {
                return true;
            }

            for ($i = 0; $i < 3; ++$i) {
                $wave = $this->wave[$i] ?? null;
                if (!$wave) {
                    return true;
                }

                $model = Yii::createObject(Wave::class);
                $model->attributes = $wave;
                if (!$model->save($salmon, $i + 1)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function getUuid(): string
    {
        if (!is_string($this->uuid)) {
            $this->uuid = $this->getUuidImpl()->formatAsString();
        }

        return $this->uuid;
    }

    private function getUuidImpl(): Uuid
    {
        if ($this->client_uuid != '') {
            try {
                $uuid = Uuid::fromString($this->client_uuid);
                switch ($uuid->getVersion()) {
                    case 1:
                    case 3:
                    case 4:
                    case 5:
                        return $uuid;

                    default:
                        break;
                }
            } catch (\Exception $e) {
            }

            return Uuid::v5(static::UUID_NAMESPACE_BY_FREETEXT, $this->client_uuid);
        }

        if ($this->splatnet_number != '') {
            return Uuid::v5(
                static::UUID_NAMESPACE_BY_SPLATNET_AND_USER_ID,
                sprintf(
                    '%d@%d',
                    (int)$this->splatnet_number,
                    (int)Yii::$app->user->id
                )
            );
        }

        return Uuid::v4();
    }

    private function getAgent(): ?Agent
    {
        if ($this->agent == '' || $this->agent_version == '') {
            return null;
        }

        return Yii::$app->db->transactionEx(function (): ?Agent {
            $model = Agent::findOne([
                'name' => $this->agent,
                'version' => $this->agent_version,
            ]);
            if ($model) {
                return $model;
            }

            $model = Yii::createObject([
                'class' => Agent::class,
                'name' => $this->agent,
                'version' => $this->agent_version,
            ]);
            return $model->save() ? $model : null;
        });
    }

    private static function findRelatedId(string $class, ?string $key): ?int
    {
        if ($key === null || $key === '') {
            return null;
        }

        if (!$model = call_user_func([$class, 'findOne'], ['key' => $key])) {
            return null;
        }

        return (int)$model->id;
    }
}
