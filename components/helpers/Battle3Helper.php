<?php

/**
 * @copyright Copyright (C) 2015-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\helpers;

use app\models\Battle3;
use app\models\BattlePlayer3;
use app\models\BattleTricolorPlayer3;
use app\models\GearConfiguration3;
use app\models\XMatchingGroup3;
use app\models\XMatchingGroupVersion3;
use app\models\XMatchingGroupWeapon3;
use yii\helpers\ArrayHelper;

use function strcmp;
use function uksort;
use function version_compare;

final class Battle3Helper
{
    /**
     * @return string[]
     */
    public static function getRelationsForApiResponse(bool $listed): array
    {
        $relations = $listed
            ? [
                'agent',
                'battleImageGear3',
                'battleImageJudge3',
                'battleImageResult3',
                'conchClash',
                'festDragon',
                'lobby',
                'map',
                'medals',
                'ourTeamRole',
                'ourTeamTheme',
                'rankAfter',
                'rankBefore',
                'result',
                'rule',
                'theirTeamRole',
                'theirTeamTheme',
                'thirdTeamRole',
                'thirdTeamTheme',
                'user',
                'variables',
                'version',
                'weapon',
                'weapon.canonical',
                'weapon.mainweapon',
                'weapon.mainweapon.type',
                'weapon.special',
                'weapon.subweapon',
                'weapon.weapon3Aliases',
            ]
            : [];

        foreach (['battlePlayer3s', 'battleTricolorPlayer3s'] as $playerTable) {
            $relations[] = $playerTable;
            $relations[] = "{$playerTable}.splashtagTitle";
            $relations[] = "{$playerTable}.weapon";
            $relations[] = "{$playerTable}.weapon.canonical";
            $relations[] = "{$playerTable}.weapon.mainweapon";
            $relations[] = "{$playerTable}.weapon.mainweapon.type";
            $relations[] = "{$playerTable}.weapon.special";
            $relations[] = "{$playerTable}.weapon.subweapon";
            $relations[] = "{$playerTable}.weapon.weapon3Aliases";

            foreach (['clothing', 'headgear', 'shoes'] as $gearTable) {
                $relations[] = "{$playerTable}.{$gearTable}";
                $relations[] = "{$playerTable}.{$gearTable}.ability";
                $relations[] = "{$playerTable}.{$gearTable}.gearConfigurationSecondary3s";
                $relations[] = "{$playerTable}.{$gearTable}.gearConfigurationSecondary3s.ability";
            }
        }

        return $relations;
    }

    /**
     * @return array<string, XMatchingGroup3> weaponkey => groupData
     */
    public static function getWeaponMatchingGroup(Battle3 $battle): array
    {
        if ($battle->lobby?->key !== 'xmatch') {
            return [];
        }

        if (!$version = self::getWeaponMatchingGroupVersion($battle)) {
            return [];
        }

        return ArrayHelper::map(
            XMatchingGroupWeapon3::find()
                ->with(['group', 'weapon'])
                ->andWhere(['version_id' => $version->id])
                ->all(),
            'weapon.key',
            'group',
        );
    }

    public static function getWeaponMatchingGroupVersion(Battle3 $battle): ?XMatchingGroupVersion3
    {
        if (!$gameVersion = $battle->version) {
            return null;
        }

        // (どうせ大した量にはならないので)全バージョンデータを取ってきて、
        // 開始バージョンが大きい順に並べる
        $versions = ArrayHelper::sort(
            XMatchingGroupVersion3::find()->all(),
            fn (XMatchingGroupVersion3 $a, XMatchingGroupVersion3 $b): int => version_compare(
                $b->minimum_version,
                $a->minimum_version,
            ),
        );

        // 大きい順に検査して、最初に見つかった開始バージョン要求を満たすものを返す
        foreach ($versions as $candidate) {
            if (version_compare($candidate->minimum_version, $gameVersion->tag, '<=')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{string, int}|null
     */
    public static function calcGPs(
        BattlePlayer3|BattleTricolorPlayer3|null $player,
        bool $disableSecondary = false,
    ): ?array {
        if (!$player) {
            return null;
        }

        $gps = [];
        foreach (['headgear', 'clothing', 'shoes'] as $gearType) {
            $tmp = self::calcGPsPerGear($player->$gearType, $disableSecondary);
            if ($tmp === null) {
                return null;
            }

            foreach ($tmp as $key => $value) {
                $gps[$key] = ($gps[$key] ?? 0) + $value;
            }
        }

        uksort($gps, fn (string $a, string $b): int => $gps[$b] <=> $gps[$a] ?: strcmp($a, $b));

        return $gps;
    }

    /**
     * @return array{string, int}|null
     */
    private static function calcGPsPerGear(?GearConfiguration3 $gear, bool $disableSecondary): ?array
    {
        if (!$gear?->ability) {
            return null;
        }

        $results = [
            $gear->ability->key => 10,
        ];
        if (!$disableSecondary) {
            $secondaryGP = match ($gear->ability->key) {
                'ability_doubler' => 6,
                default => 3,
            };

            foreach ($gear->gearConfigurationSecondary3s as $secondaryGear) {
                if (!$ability = $secondaryGear->ability) {
                    continue;
                }

                $results[$ability->key] = ($results[$ability->key] ?? 0) + $secondaryGP;
            }
        }

        return $results;
    }
}
