<?php

/**
 * @copyright Copyright (C) 2015-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\models\api\v3;

use Throwable;
use Yii;
use app\components\behaviors\TrimAttributesBehavior;
use app\components\db\Connection;
use app\components\helpers\CriticalSection;
use app\components\helpers\UuidRegexp;
use app\components\helpers\db\Now;
use app\components\validators\AgentVersionValidator;
use app\components\validators\ArrayValidator;
use app\components\validators\BattleAgentVariable3Validator;
use app\components\validators\KeyValidator;
use app\components\validators\SalmonBoss3Validator;
use app\components\validators\SalmonPlayer3FormValidator;
use app\components\validators\SalmonWave3FormValidator;
use app\models\BigrunMap3;
use app\models\BigrunMap3Alias;
use app\models\Salmon3;
use app\models\SalmonAgentVariable3;
use app\models\SalmonFailReason2;
use app\models\SalmonKing3;
use app\models\SalmonKing3Alias;
use app\models\SalmonMap3;
use app\models\SalmonMap3Alias;
use app\models\SalmonSchedule3;
use app\models\SalmonTitle3;
use app\models\SalmonTitle3Alias;
use app\models\api\v3\postBattle\AgentVariableTrait;
use app\models\api\v3\postBattle\GameVersionTrait;
use app\models\api\v3\postBattle\TypeHelperTrait;
use app\models\api\v3\postBattle\UserAgentTrait;
use app\models\api\v3\postSalmon\BossForm;
use app\models\api\v3\postSalmon\PlayerForm;
use app\models\api\v3\postSalmon\WaveForm;
use jp3cki\uuid\Uuid;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use function array_keys;
use function array_values;
use function base64_encode;
use function date;
use function floor;
use function hash_hmac;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function min;
use function rtrim;
use function strtotime;
use function time;
use function trim;
use function vsprintf;

/**
 * @property-read Salmon3|null $sameBattle
 * @property-read bool $isTest
 */
final class PostSalmonForm extends Model
{
    use AgentVariableTrait;
    use GameVersionTrait;
    use TypeHelperTrait;
    use UserAgentTrait;

    public $test;

    public $uuid;
    public $private;
    public $big_run;
    public $eggstra_work;
    public $stage;
    public $danger_rate;
    public $clear_waves;
    public $fail_reason;
    public $king_smell;
    public $king_salmonid;
    public $clear_extra;
    public $title_before;
    public $title_exp_before;
    public $title_after;
    public $title_exp_after;
    public $golden_eggs;
    public $power_eggs;
    public $gold_scale;
    public $silver_scale;
    public $bronze_scale;
    public $job_point;
    public $job_score;
    public $job_rate;
    public $job_bonus;
    public $waves;
    public $players;
    public $bosses;
    public $note;
    public $private_note;
    public $link_url;
    public $agent;
    public $agent_version;
    public $automated;
    public $start_at;
    public $end_at;

    /** @var array<string, string> */
    public $agent_variables;

    public function behaviors()
    {
        return [
            [
                'class' => TrimAttributesBehavior::class,
                'targets' => array_keys($this->attributes),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['eggstra_work'], 'default', 'value' => 'no'],
            [['private', 'big_run'], 'required'],

            [['uuid', 'stage', 'fail_reason', 'king_salmonid', 'title_before'], 'string'],
            [['title_after', 'note', 'private_note', 'link_url'], 'string'],
            [['agent'], 'string', 'max' => 64],
            [['agent_version'], 'string', 'max' => 255],

            [['test', 'private', 'big_run', 'eggstra_work', 'clear_extra', 'automated'], 'in',
                'range' => ['yes', 'no', true, false],
                'strict' => true,
            ],

            [['danger_rate'], 'number', 'min' => 0, 'max' => 350],
            [['clear_waves'], 'integer',
                'min' => 0,
                'max' => 3,
                'when' => fn (self $model): bool => !self::boolVal($model->eggstra_work),
            ],
            [['clear_waves'], 'integer',
                'min' => 0,
                'max' => 5,
                'when' => fn (self $model): bool => self::boolVal($model->eggstra_work),
            ],
            [['king_smell'], 'integer', 'min' => 0, 'max' => 5],
            [['title_exp_before', 'title_exp_after'], 'integer', 'min' => 0, 'max' => 999],
            [['golden_eggs', 'power_eggs'], 'integer', 'min' => 0],
            [['gold_scale', 'silver_scale', 'bronze_scale'], 'integer', 'min' => 0],
            [['job_point', 'job_score', 'job_bonus'], 'integer', 'min' => 0],
            [['job_rate'], 'number', 'min' => 0],
            [['start_at', 'end_at'], 'integer',
                'min' => strtotime('2022-01-01T00:00:00+00:00'),
                'max' => time() + 3600,
            ],

            [['uuid'], 'match', 'pattern' => UuidRegexp::get(true)],
            [['link_url'], 'url',
                'validSchemes' => ['http', 'https'],
                'defaultScheme' => null,
                'enableIDN' => false,
            ],
            [['agent', 'agent_version'], 'required',
                'when' => fn () => trim((string)$this->agent) !== '' || trim((string)$this->agent_version) !== '',
            ],
            [['agent_version'], AgentVersionValidator::class,
                'gameVersion' => 'splatoon3',
                'when' => fn () => trim((string)$this->agent) !== '' && trim((string)$this->agent_version) !== '',
            ],
            [['stage'], KeyValidator::class,
                'modelClass' => SalmonMap3::class,
                'aliasClass' => SalmonMap3Alias::class,
                'when' => fn (self $model): bool => self::boolVal($model->big_run) !== true,
            ],
            [['stage'], KeyValidator::class,
                'modelClass' => BigrunMap3::class,
                'aliasClass' => BigrunMap3Alias::class,
                'when' => fn (self $model): bool => self::boolVal($model->big_run) === true,
            ],
            [['fail_reason'], KeyValidator::class,
                'modelClass' => SalmonFailReason2::class,
            ],
            [['king_salmonid'], KeyValidator::class,
                'modelClass' => SalmonKing3::class,
                'aliasClass' => SalmonKing3Alias::class,
            ],
            [['title_before', 'title_after'], KeyValidator::class,
                'modelClass' => SalmonTitle3::class,
                'aliasClass' => SalmonTitle3Alias::class,
            ],

            [['agent_variables'], BattleAgentVariable3Validator::class],
            [['players'], ArrayValidator::class,
                'rule' => [SalmonPlayer3FormValidator::class, 'skipOnEmpty' => false],
                'min' => 1,
                'max' => 4,
            ],
            [['waves'], ArrayValidator::class,
                'rule' => [SalmonWave3FormValidator::class, 'skipOnEmpty' => false],
                'min' => 1,
                'max' => 4,
                'when' => fn (self $model): bool => !self::boolVal($model->eggstra_work),
            ],
            [['waves'], ArrayValidator::class,
                'rule' => [SalmonWave3FormValidator::class, 'skipOnEmpty' => false],
                'min' => 1,
                'max' => 5,
                'when' => fn (self $model): bool => self::boolVal($model->eggstra_work),
            ],
            [['bosses'], SalmonBoss3Validator::class],
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

    public function getSameBattle(): ?Salmon3
    {
        if (
            !is_string($this->uuid) ||
            $this->uuid === ''
        ) {
            return null;
        }

        if (!$user = Yii::$app->user->identity) {
            return null;
        }

        return Salmon3::find()
            ->where([
                'user_id' => $user->id,
                'client_uuid' => $this->uuid,
                'is_deleted' => false,
            ])
            ->limit(1)
            ->one();
    }

    public function getIsTest(): bool
    {
        return self::boolVal($this->test) === true;
    }

    /**
     * @return Salmon3|bool|null
     */
    public function save()
    {
        if (!$this->validate()) {
            return null;
        }

        if ($this->getIsTest()) {
            return true;
        }

        if (!$lock = CriticalSection::lock($this->getCriticalSectionId(), 60)) {
            $this->addError('_system', 'Failed to get lock. System busy. Try again.');
            return null;
        }

        try {
            return $this->getSameBattle() ?? $this->saveNewBattleRelation();
        } finally {
            unset($lock);
        }
    }

    private function getCriticalSectionId(): string
    {
        $values = [
            'class' => self::class,
            'user' => Yii::$app->user->id,
            'version' => 1,
        ];
        ksort($values);
        return rtrim(
            base64_encode(
                hash_hmac(
                    'sha256',
                    Json::encode($values),
                    (string)Yii::getAlias('@app'),
                    true,
                ),
            ),
            '=',
        );
    }

    private function saveNewBattleRelation(): ?Salmon3
    {
        try {
            $connection = Yii::$app->db;
            if (!$connection instanceof Connection) {
                throw new InvalidConfigException();
            }

            return $connection->transactionEx(function (Connection $connection): ?Salmon3 {
                if (!$battle = $this->saveNewBattle()) {
                    return null;
                }

                if (!$this->savePlayers($battle)) {
                    return null;
                }

                if (!$this->saveWaves($battle)) {
                    return null;
                }

                if (!$this->saveBosses($battle)) {
                    return null;
                }

                if (!$this->saveAgentVariables($battle)) {
                    return null;
                }

                // TODO: more data

                return $battle;
            });
        } catch (Throwable $e) {
            $this->addError(
                '_system',
                vsprintf('Failed to store your battle (internal error), %s', [
                    $e::class,
                ]),
            );
            return null;
        }
    }

    private function saveNewBattle(): ?Salmon3
    {
        $uuid = (string)Uuid::v4();
        $isBigRun = self::boolVal($this->big_run) === true;
        $model = Yii::createObject([
            'class' => Salmon3::class,
            'user_id' => Yii::$app->user->id,
            'uuid' => $uuid,
            'client_uuid' => $this->uuid ?: $uuid,
            'is_private' => self::boolVal($this->private),
            'is_big_run' => $isBigRun,
            'is_eggstra_work' => self::boolVal($this->eggstra_work) === true,
            'stage_id' => $isBigRun
                ? null
                : self::key2id($this->stage, SalmonMap3::class, SalmonMap3Alias::class, 'map_id'),
            'big_stage_id' => $isBigRun
                ? self::key2id($this->stage, BigrunMap3::class, BigrunMap3Alias::class, 'map_id')
                : null,
            'danger_rate' => self::floatVal($this->danger_rate),
            'clear_waves' => self::intVal($this->clear_waves),
            'fail_reason_id' => self::key2id($this->fail_reason, SalmonFailReason2::class),
            'king_smell' => self::intVal($this->king_smell),
            'king_salmonid_id' => self::key2id($this->king_salmonid, SalmonKing3::class, SalmonKing3Alias::class, 'salmonid_id'),
            'clear_extra' => self::boolVal($this->clear_extra),
            'title_before_id' => self::key2id($this->title_before, SalmonTitle3::class, SalmonTitle3Alias::class, 'title_id'),
            'title_exp_before' => self::intVal($this->title_exp_before),
            'title_after_id' => self::key2id($this->title_after, SalmonTitle3::class, SalmonTitle3Alias::class, 'title_id'),
            'title_exp_after' => self::intVal($this->title_exp_after),
            'golden_eggs' => self::intVal($this->golden_eggs),
            'power_eggs' => self::intVal($this->power_eggs),
            'gold_scale' => self::intVal($this->gold_scale),
            'silver_scale' => self::intVal($this->silver_scale),
            'bronze_scale' => self::intVal($this->bronze_scale),
            'job_point' => self::intVal($this->job_point),
            'job_score' => self::intVal($this->job_score),
            'job_rate' => self::floatVal($this->job_rate),
            'job_bonus' => self::intVal($this->job_bonus),
            'note' => self::strVal($this->note),
            'private_note' => self::strVal($this->private_note),
            'link_url' => self::strVal($this->link_url),
            'version_id' => self::gameVersion(
                self::guessStartAt(
                    self::intVal($this->start_at),
                    self::intVal($this->end_at),
                    self::intVal($this->clear_waves),
                ),
            ),
            'agent_id' => self::userAgent($this->agent, $this->agent_version),
            'is_automated' => self::boolVal($this->automated) ?: false,
            'start_at' => self::tsVal($this->start_at),
            'end_at' => self::tsVal($this->end_at),
            'period' => self::guessPeriod(
                self::intVal($this->start_at),
                self::intVal($this->end_at),
                self::intVal($this->clear_waves),
            ),
            'schedule_id' => self::guessScheduleId(
                self::boolVal($this->eggstra_work) === true,
                self::intVal($this->start_at),
                self::intVal($this->end_at),
                self::intVal($this->clear_waves),
            ),
            'has_disconnect' => $this->hasDisconnect(),
            'is_deleted' => false,
            'has_broken_data' => $this->hasBrokenData(),
            'remote_addr' => Yii::$app->request->getUserIP() ?? '127.0.0.2',
            'remote_port' => self::intVal($_SERVER['REMOTE_PORT'] ?? 0),
            'created_at' => self::now(),
            'updated_at' => self::now(),
        ]);

        if (!$model->save()) {
            $this->addError('_system', vsprintf('Failed to store new battle, info=%s', [
                base64_encode(Json::encode($model->getFirstErrors())),
            ]));
            return null;
        }

        return $model;
    }

    private function hasDisconnect(): bool
    {
        if (is_array($this->players)) {
            foreach ($this->players as $player) {
                $value = self::boolVal(ArrayHelper::getValue($player, 'disconnected'));
                if ($value === true) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasBrokenData(): bool
    {
        if (is_array($this->bosses)) {
            foreach ($this->bosses as $data) {
                if ($data === null) {
                    continue;
                }

                $form = Yii::createObject(BossForm::class);
                $form->attributes = $data;
                if ($form->appearances > 0) {
                    // https://github.com/frozenpandaman/s3s/issues/80#issuecomment-1328040023
                    if (
                        $form->appearances < $form->defeated ||
                        $form->appearances < $form->defeated_by_me ||
                        $form->defeated < $form->defeated_by_me
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function savePlayers(Salmon3 $battle): bool
    {
        if (is_array($this->players)) {
            foreach ($this->players as $player) {
                $model = Yii::createObject(PlayerForm::class);
                $model->attributes = $player;
                if (!$model->save($battle)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function saveWaves(Salmon3 $battle): bool
    {
        if (is_array($this->waves) && $this->waves) {
            foreach (array_values($this->waves) as $i => $data) {
                if (!$data) {
                    return false;
                }

                $form = Yii::createObject(WaveForm::class);
                $form->attributes = $data;
                if (!$form->save($battle, $i + 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function saveBosses(Salmon3 $battle): bool
    {
        if (is_array($this->bosses)) {
            foreach ($this->bosses as $key => $data) {
                if ($data === null) {
                    continue;
                }

                $form = Yii::createObject(BossForm::class);
                $form->attributes = $data;
                if (!$form->save($battle, $key)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function saveAgentVariables(Salmon3 $battle): bool
    {
        $map = $this->agent_variables;
        if (!is_array($map) || !$map) {
            return true;
        }

        foreach ($map as $k => $v) {
            $model = Yii::createObject([
                'class' => SalmonAgentVariable3::class,
                'salmon_id' => $battle->id,
                // `findOrCreateAgentVariable()` may returns null and it will fail on `save()`
                'variable_id' => $this->findOrCreateAgentVariable($k, $v),
            ]);
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    private static function now(): Now
    {
        return Yii::createObject(Now::class);
    }

    private static function guessPeriod(?int $startAt, ?int $endAt, ?int $clearWaves = null): int
    {
        return self::timestamp2period(
            self::guessStartAt($startAt, $endAt, $clearWaves),
        );
    }

    private static function timestamp2period(int $ts): int
    {
        return (int)floor($ts / 7200);
    }

    private static function guessScheduleId(
        bool $isEggstraWork,
        ?int $startAt,
        ?int $endAt,
        ?int $clearWaves = null,
    ): ?int {
        $startAt = self::guessStartAt($startAt, $endAt, $clearWaves);
        $model = SalmonSchedule3::find()
            ->andWhere(['and',
                ['is_eggstra_work' => $isEggstraWork],
                ['<=', 'start_at', date('Y-m-d\TH:i:sP', $startAt)],
                ['>', 'end_at', date('Y-m-d\TH:i:sP', $startAt)],
            ])
            ->limit(1)
            ->one();
        return $model ? (int)$model->id : null;
    }

    private static function guessStartAt(
        ?int $startAt,
        ?int $endAt,
        ?int $clearWaves = null,
    ): int {
        if (is_int($startAt)) {
            return $startAt;
        }

        if (is_int($endAt)) {
            $playTime = self::guessPlayTime($clearWaves);
            if (is_int($playTime)) {
                return $endAt - $playTime;
            }
        }

        return time() - 370;
    }

    private static function guessPlayTime(?int $clearWaves): ?int
    {
        if (
            !is_int($clearWaves) ||
            $clearWaves < 0 ||
            $clearWaves > 3
        ) {
            return null;
        }

        $waves = min(3, $clearWaves + 1);
        return (int)floor(10 + $waves * 120);
    }
}
