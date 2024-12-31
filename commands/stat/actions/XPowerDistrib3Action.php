<?php

/**
 * @copyright Copyright (C) 2022-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\commands\stat\actions;

use LogicException;
use Throwable;
use Yii;
use yii\base\Action;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Query;
use yii\db\Transaction;

use function array_keys;
use function array_map;
use function fprintf;
use function fwrite;
use function implode;
use function sprintf;
use function vfprintf;
use function vsprintf;

use const STDERR;

final class XPowerDistrib3Action extends Action
{
    private const TMP_USER_XPOWER_TABLE_NAME = '{{tmp_user_xpower}}';

    public function run(): int
    {
        $db = Yii::$app->db;
        if (!$db instanceof Connection) {
            throw new LogicException();
        }

        $isOk = $db->transaction(
            fn (Connection $db): bool => $this->makeStats($db),
            Transaction::REPEATABLE_READ,
        );
        if (!$isOk) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->vacuumTables($db);

        return ExitCode::OK;
    }

    private function makeStats(Connection $db): bool
    {
        $transaction = $db->transaction;
        if (!$transaction) {
            throw new LogicException();
        }

        fwrite(STDERR, "Updating X Power Distribution stats\n");
        if (
            $this->createTmpUserXpowerTable($db) &&
            $this->updateDistribAbstract($db) &&
            $this->updateDistribHistogram($db)
        ) {
            return true;
        }

        $transaction->rollBack();

        return false;
    }

    private function createTmpUserXpowerTable(Connection $db): bool
    {
        $select = (new Query())
            ->select([
                'user_id' => '{{%battle3}}.[[user_id]]',
                'season_id' => '{{%season3}}.[[id]]',
                'rule_id' => '{{%battle3}}.[[rule_id]]',
                'x_power' => 'GREATEST(MAX({{%battle3}}.[[x_power_before]]), MAX({{%battle3}}.[[x_power_after]]))',
            ])
            ->from('{{%battle3}}')
            ->innerJoin('{{%lobby3}}', '{{%battle3}}.[[lobby_id]] = {{%lobby3}}.[[id]]')
            ->innerJoin('{{%rule3}}', '{{%battle3}}.[[rule_id]] = {{%rule3}}.[[id]]')
            ->innerJoin('{{%rule_group3}}', '{{%rule3}}.[[group_id]] = {{%rule_group3}}.[[id]]')
            ->innerJoin('{{%season3}}', '{{%battle3}}.[[start_at]] <@ {{%season3}}.[[term]]')
            ->andWhere([
                '{{%battle3}}.[[is_automated]]' => true,
                '{{%battle3}}.[[is_deleted]]' => false,
                '{{%lobby3}}.[[key]]' => 'xmatch',
                '{{%rule_group3}}.[[key]]' => 'gachi',
            ])
            ->andWhere(['not', ['{{%battle3}}.[[start_at]]' => null]])
            ->andWhere(['or',
                ['not', ['{{%battle3}}.[[x_power_after]]' => null]],
                ['not', ['{{%battle3}}.[[x_power_before]]' => null]],
            ])
            ->groupBy([
                '{{%battle3}}.[[user_id]]',
                '{{%battle3}}.[[rule_id]]',
                '{{%season3}}.[[id]]',
            ]);

        $sql = vsprintf('CREATE TEMPORARY TABLE %s AS %s', [
            $db->quoteTableName(self::TMP_USER_XPOWER_TABLE_NAME),
            $select->createCommand($db)->rawSql,
        ]);

        try {
            fwrite(STDERR, "Creating temporary table...\n");
            $db->createCommand($sql)->execute();

            fwrite(STDERR, "Creating index...\n");
            $db->createCommand()
                ->createIndex(
                    name: 'tmp_user_xpower_pkey',
                    table: self::TMP_USER_XPOWER_TABLE_NAME,
                    columns: [
                        'season_id',
                        'rule_id',
                        'user_id',
                    ],
                    unique: true,
                )
                ->execute();

            fwrite(STDERR, "Analyze temporary table..\n");
            $db->createCommand(sprintf('ANALYZE %s', self::TMP_USER_XPOWER_TABLE_NAME))
                ->execute();

            fwrite(STDERR, "OK.\n");

            return true;
        } catch (Throwable $e) {
            vfprintf(STDERR, "Failed to create temporary table, exception=%s, message=%s, sql=%s\n", [
                $e::class,
                $e->getMessage(),
                $sql,
            ]);

            return false;
        }
    }

    private function updateDistribAbstract(Connection $db): bool
    {
        $percentile = fn (float $p): string => sprintf(
            'PERCENTILE_CONT(%.2f) WITHIN GROUP (ORDER BY {{t}}.[[x_power]] ASC)',
            $p,
        );

        $select = (new Query())
            ->select([
                'season_id' => '{{t}}.[[season_id]]',
                'rule_id' => '{{t}}.[[rule_id]]',
                'users' => 'COUNT(*)',
                'average' => 'AVG({{t}}.[[x_power]])',
                'stddev' => 'STDDEV_SAMP({{t}}.[[x_power]])',
                'median' => $percentile(0.5),
                'pct5' => $percentile(0.05),
                'pct25' => $percentile(0.25),
                'pct75' => $percentile(0.75),
                'pct80' => $percentile(0.80),
                'pct95' => $percentile(0.95),
                'histogram_width' => 'HISTOGRAM_WIDTH(COUNT(*), STDDEV_SAMP({{t}}.[[x_power]]))',
            ])
            ->from(['t' => self::TMP_USER_XPOWER_TABLE_NAME])
            ->groupBy(['season_id', 'rule_id']);

        $sql = vsprintf('INSERT INTO %s ( %s ) %s', [
            $db->quoteTableName('{{stat_x_power_distrib_abstract3}}'),
            implode(
                ', ',
                array_map(
                    fn (string $columnName): string => $db->quoteColumnName($columnName),
                    array_keys($select->select),
                ),
            ),
            $select->createCommand($db)->rawSql,
        ]);

        try {
            fwrite(STDERR, "Cleanup stat_x_power_distrib_abstract3...\n");
            $db->createCommand()->delete('{{%stat_x_power_distrib_abstract3}}')->execute();

            fwrite(STDERR, "Inserting stat_x_power_distrib_abstract3...\n");
            $db->createCommand($sql)->execute();
            fwrite(STDERR, "OK.\n");

            return true;
        } catch (Throwable $e) {
            vfprintf(STDERR, "Failed to update, exception=%s, message=%s, sql=%s\n", [
                $e::class,
                $e->getMessage(),
                $sql,
            ]);

            return false;
        }
    }

    private function updateDistribHistogram(Connection $db): bool
    {
        $classValue = sprintf(
            // +0.5 は階級値は階級の幅の中央を表すための調整
            '((FLOOR(%1$s.%3$s / %2$s.%4$s) + 0.5) * %2$s.%4$s)::integer',
            $db->quoteTableName('{{t}}'),
            $db->quoteTableName('{{%stat_x_power_distrib_abstract3}}'),
            $db->quoteColumnName('x_power'),
            $db->quoteColumnName('histogram_width'),
        );

        $select = (new Query())
            ->select([
                'season_id' => '{{t}}.[[season_id]]',
                'rule_id' => '{{t}}.[[rule_id]]',
                'class_value' => $classValue,
                'users' => 'COUNT(*)',
            ])
            ->from(['t' => self::TMP_USER_XPOWER_TABLE_NAME])
            ->innerJoin(
                '{{%stat_x_power_distrib_abstract3}}',
                implode(' AND ', [
                    '{{t}}.[[season_id]] = {{%stat_x_power_distrib_abstract3}}.[[season_id]]',
                    '{{t}}.[[rule_id]] = {{%stat_x_power_distrib_abstract3}}.[[rule_id]]',
                ]),
            )
            ->groupBy([
                '{{t}}.[[season_id]]',
                '{{t}}.[[rule_id]]',
                $classValue,
            ]);

        $sql = vsprintf('INSERT INTO %s ( %s ) %s', [
            $db->quoteTableName('{{%stat_x_power_distrib_histogram3}}'),
            implode(
                ', ',
                array_map(
                    fn (string $columnName): string => $db->quoteColumnName($columnName),
                    array_keys($select->select),
                ),
            ),
            $select->createCommand($db)->rawSql,
        ]);

        try {
            fwrite(STDERR, "Cleanup stat_x_power_distrib_histogram3...\n");
            $db->createCommand()->delete('{{%stat_x_power_distrib_histogram3}}')->execute();

            fwrite(STDERR, "Inserting stat_x_power_distrib_histogram3...\n");
            $db->createCommand($sql)->execute();
            fwrite(STDERR, "OK.\n");

            return true;
        } catch (Throwable $e) {
            vfprintf(STDERR, "Failed to update, exception=%s, message=%s, sql=%s\n", [
                $e::class,
                $e->getMessage(),
                $sql,
            ]);

            return false;
        }
    }

    private function vacuumTables(Connection $db): void
    {
        $tables = [
            '{{%stat_x_power_distrib_abstract3}}',
            '{{%stat_x_power_distrib_histogram3}}',
        ];

        foreach ($tables as $table) {
            fprintf(STDERR, "Vacuuming %s\n", $table);
            $sql = vsprintf('VACUUM ( ANALYZE ) %s', [
                $db->quoteTableName($table),
            ]);
            $db->createCommand($sql)->execute();
        }

        fwrite(STDERR, "OK\n");
    }
}
