<?php

/**
 * @copyright Copyright (C) 2015-2023 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\api\internal\schedule;

use DateTime;
use Yii;
use app\assets\GameModeIconsAsset;
use app\assets\Spl3SalmonidAsset;
use app\assets\Spl3StageAsset;
use app\assets\Spl3WeaponAsset;
use app\models\Map3;
use app\models\SalmonKing3;
use app\models\SalmonMap3;
use app\models\SalmonSchedule3;
use app\models\SalmonScheduleWeapon3;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use function sprintf;
use function strtotime;

use const SORT_ASC;

trait Salmon3
{
    protected function getSalmon3(): array
    {
        $am = Yii::$app->assetManager;
        return [
            'salmon' => [
                'key' => 'salmon',
                'game' => 'splatoon3',
                'name' => Yii::t('app-salmon3', 'Salmon Run'),
                'image' => Url::to(
                    $am->getAssetUrl(
                        $am->getBundle(GameModeIconsAsset::class, true),
                        'spl3/salmon36x36.png',
                    ),
                    true,
                ),
                'source' => 's3ink',
                'schedules' => ArrayHelper::getColumn(
                    SalmonSchedule3::find()
                        ->with([
                            'bigMap',
                            'king',
                            'map',
                            'salmonScheduleWeapon3s',
                            'salmonScheduleWeapon3s.random',
                            'salmonScheduleWeapon3s.weapon',
                        ])
                        ->andWhere(['is_eggstra_wave' => false])
                        ->andWhere(['>', 'end_at', $this->now->format(DateTime::ATOM)])
                        ->orderBy([
                            'end_at' => SORT_ASC,
                        ])
                        ->limit(10)
                        ->all(),
                    fn (SalmonSchedule3 $sc): array => [
                            'time' => [
                                strtotime($sc->start_at),
                                strtotime($sc->end_at),
                            ],
                            'maps' => [
                                $this->salmonMap3($sc->map ?? $sc->bigMap),
                            ],
                            'king' => $this->salmonKing3($sc->king),
                            'weapons' => ArrayHelper::getColumn(
                                ArrayHelper::sort(
                                    $sc->salmonScheduleWeapon3s,
                                    fn (SalmonScheduleWeapon3 $a, SalmonScheduleWeapon3 $b): int => $a->id <=> $b->id,
                                ),
                                function (SalmonScheduleWeapon3 $info) use ($am): array {
                                    $w = $info->weapon ?: $info->random;
                                    return [
                                        'key' => $w->key,
                                        'name' => Yii::t('app-weapon3', $w->name),
                                        'icon' => Url::to(
                                            $am->getAssetUrl(
                                                $am->getBundle(Spl3WeaponAsset::class, true),
                                                'main/' . $w->key . '.png',
                                            ),
                                            true,
                                        ),
                                    ];
                                },
                            ),
                            'is_big_run' => $sc->map === null,
                        ],
                ),
            ],
        ];
    }

    private function salmonMap3(SalmonMap3|Map3|null $info): ?array
    {
        if (!$info) {
            return null;
        }

        $am = Yii::$app->assetManager;
        return [
            'key' => $info->key,
            'name' => Yii::t('app-map3', $info->name),
            'image' => Url::to(
                $am->getAssetUrl(
                    $am->getBundle(Spl3StageAsset::class, true),
                    sprintf('color-normal/%s.jpg', $info->key),
                ),
                true,
            ),
        ];
    }

    private function salmonKing3(SalmonKing3|null $king): ?array
    {
        if (!$king) {
            return null;
        }

        $am = Yii::$app->assetManager;
        return [
            'key' => $king->key,
            'name' => Yii::t('app-salmon-boss3', $king->name),
            'image' => Url::to(
                $am->getAssetUrl(
                    $am->getBundle(Spl3SalmonidAsset::class, true),
                    sprintf('%s.png', $king->key),
                ),
                true,
            ),
        ];
    }
}
