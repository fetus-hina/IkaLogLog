<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\commands\splatoon3Ink;

use Throwable;
use Yii;
use app\models\Lobby3;
use app\models\Schedule3;
use app\models\ScheduleMap3;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Transaction;

use function array_filter;
use function array_map;
use function array_values;
use function fwrite;
use function sort;
use function str_starts_with;
use function vfprintf;

use const STDERR;

trait UpdateSchedule
{
    protected function updateSchedule(array $schedules): int
    {
        return Yii::$app->db->transaction(
            function (Connection $db) use ($schedules): int {
                foreach ($schedules as $lobbyKey => $scheduleData) {
                    if ($lobbyKey === 'event' || str_starts_with($lobbyKey, 'salmon_')) {
                        continue;
                    }

                    $lobby = Lobby3::find()
                        ->andWhere(['key' => $lobbyKey])
                        ->limit(1)
                        ->one();
                    if ($lobby) {
                        if (!$this->registerSchedules($lobby, $scheduleData)) {
                            $db->transaction->rollBack();
                            return ExitCode::UNSPECIFIED_ERROR;
                        }
                    }
                }

                return ExitCode::OK;
            },
            Transaction::REPEATABLE_READ,
        );
    }

    private function registerSchedules(Lobby3 $lobby, array $schedules): bool
    {
        foreach ($schedules as $schedule) {
            if (
                !$this->registerSchedule(
                    $lobby,
                    $schedule['period'],
                    $schedule['rule_id'],
                    $schedule['map_ids'],
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function registerSchedule(Lobby3 $lobby, int $period, int $ruleId, array $mapIds): bool
    {
        return Yii::$app->db->transaction(
            function (Connection $db) use ($lobby, $period, $ruleId, $mapIds): bool {
                // 既にデータが正しく保存されている
                if ($this->isScheduleRegistered($lobby, $period, $ruleId, $mapIds)) {
                    return true;
                }

                if (
                    $this->cleanUpSchedule($lobby, $period) &&
                    $this->registerScheduleImpl($lobby, $period, $ruleId, $mapIds)
                ) {
                    vfprintf(STDERR, "Schedule registered, lobby=%s, period=%d\n", [
                        $lobby->key,
                        $period,
                    ]);

                    return true;
                }

                $db->transaction->rollBack();
                return false;
            },
        );
    }

    private function isScheduleRegistered(Lobby3 $lobby, int $period, int $ruleId, array $mapIds): bool
    {
        $schedule = Schedule3::find()
            ->andWhere([
                'lobby_id' => $lobby->id,
                'period' => $period,
            ])
            ->limit(1)
            ->one();
        if (!$schedule) {
            // 完全にデータなし
            return false;
        }

        // ルールが違う
        if ($schedule->rule_id !== $ruleId) {
            return false;
        }

        // マップが違う
        $mapIds = array_values(
            array_filter(
                $mapIds,
                fn (?int $value): bool => $value !== null,
            ),
        );
        $registeredMapIds = array_values(
            array_filter(
                array_map(
                    fn (ScheduleMap3 $model): ?int => $model->map_id,
                    $schedule->scheduleMap3s,
                ),
                fn (?int $value): bool => $value !== null,
            ),
        );
        sort($mapIds);
        sort($registeredMapIds);

        return $mapIds === $registeredMapIds;
    }

    private function cleanUpSchedule(Lobby3 $lobby, int $period): bool
    {
        $schedule = Schedule3::find()
            ->andWhere([
                'lobby_id' => $lobby->id,
                'period' => $period,
            ])
            ->limit(1)
            ->one();
        if (!$schedule) {
            return true;
        }

        vfprintf(STDERR, "Clean up schedule, lobby=%s, period=%d\n", [
            $lobby->key,
            $period,
        ]);

        try {
            foreach ($schedule->scheduleMap3s as $map) {
                if (!$map->delete()) {
                    return false;
                }
            }

            if (!$schedule->delete()) {
                return false;
            }
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    private function registerScheduleImpl(Lobby3 $lobby, int $period, int $ruleId, array $mapIds): bool
    {
        $schedule = Yii::createObject([
            'class' => Schedule3::class,
            'period' => $period,
            'lobby_id' => $lobby->id,
            'rule_id' => $ruleId,
        ]);
        if (!$schedule->save()) {
            fwrite(STDERR, "Failed to create schedule3 data\n");
            return false;
        }

        foreach ($mapIds as $mapId) {
            $map = Yii::createObject([
                'class' => ScheduleMap3::class,
                'schedule_id' => $schedule->id,
                'map_id' => $mapId,
            ]);
            if (!$map->save()) {
                fwrite(STDERR, "Failed to create schedule_map3 data\n");
                return false;
            }
        }

        return true;
    }
}
