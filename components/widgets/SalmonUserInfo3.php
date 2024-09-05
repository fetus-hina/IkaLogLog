<?php

/**
 * @copyright Copyright (C) 2015-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\widgets;

use Yii;
use app\components\i18n\Formatter;
use app\models\UserStatSalmon3;
use yii\helpers\Html;
use yii\web\View;

use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function vsprintf;

final class SalmonUserInfo3 extends SalmonUserInfo
{
    protected function renderData(): string
    {
        $fmt = Yii::createObject([
            'class' => Formatter::class,
            'nullDisplay' => Yii::t('app', 'N/A'),
        ]);

        $stats = $this->getUserStats();
        $avg = fn ($value, int $decimal = 1): string => $fmt->asDecimal(
            $value !== null && $stats->agg_jobs > 0 ? $value / $stats->agg_jobs : null,
            $decimal,
        );

        $maxTitle = null;
        $maxTitleFull = null;
        if ($stats->peakTitle) {
            $maxTitleFull = vsprintf('%s %s', [
                Yii::t('app-salmon-title3', $stats->peakTitle->name),
                $stats->peak_title_exp === null ? '?' : $fmt->asInteger($stats->peak_title_exp),
            ]);
            $maxTitle = $stats->peakTitle->key === 'eggsecutive_vp' && $stats->peak_title_exp !== null
                ? $fmt->asInteger($stats->peak_title_exp)
                : Yii::t('app-salmon-title3', $stats->peakTitle->name);
        }

        $data = [
            [
                'label' => Yii::t('app-salmon2', 'Jobs'),
                'value' => Html::a(
                    Html::encode($fmt->asInteger((int)$stats->jobs)),
                    ['salmon-v3/index', 'screen_name' => $this->user->screen_name],
                ),
                'valueFormat' => 'raw',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Clear %'),
                'value' => $stats->agg_jobs > 0 ? $stats->clear_jobs / $stats->agg_jobs : null,
                'valueFormat' => ['percent', 1],
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Waves'),
                'labelTitle' => Yii::t('app-salmon2', 'Avg. Waves'),
                'value' => $avg($stats->clear_waves, 2),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'King'),
                'labelTitle' => Yii::t('app-salmon3', 'King Salmonid Appearances'),
                'value' => $stats->king_appearances,
                'valueFormat' => 'integer',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'Defeat %'),
                'labelTitle' => Yii::t('app-salmon3', 'King Salmonid Defeat Rate'),
                'value' => $stats->king_appearances > 0
                    ? $stats->king_defeated / $stats->king_appearances
                    : null,
                'valueFormat' => ['percent', 1],
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Hazard Level'),
                'labelTitle' => Yii::t('app-salmon3', 'Max. Hazard Level (cleared)'),
                'valueFormat' => 'raw',
                'value' => (function (UserStatSalmon3 $stats) use ($fmt): ?string {
                    $value = $stats->peak_danger_rate;
                    if ($value === null) {
                        return null;
                    }

                    if ($value > 332.95) {
                        $view = $this->view;
                        if ($view instanceof View) {
                            return Html::tag(
                                'div',
                                Html::tag(
                                    'span',
                                    Icon::s3HazardLevelMax(),
                                    [
                                        'title' => Yii::t('app-salmon3', 'MAX Hazard Level Cleared'),
                                        'class' => 'auto-tooltip',
                                    ],
                                ),
                                ['class' => 'text-center'],
                            );
                        }
                    }

                    return $fmt->asPercent((int)$value / 100, 0);
                })($stats),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'Rescues'),
                'labelTitle' => Yii::t('app-salmon3', 'Rescues'),
                'value' => $avg($stats->rescues, 2),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'Rescued'),
                'labelTitle' => Yii::t('app-salmon3', 'Rescued'),
                'value' => $avg($stats->rescued, 2),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app', 'Peak'),
                'labelTitle' => Yii::t('app-salmon3', 'Title Reached'),
                'value' => Html::tag(
                    'span',
                    Html::encode($maxTitle ?? '-'),
                    [
                        'class' => 'd-block nobr',
                        'style' => [
                            'overflow' => 'hidden',
                        ],
                    ],
                ),
                'valueTitle' => $maxTitleFull,
                'valueFormat' => 'raw',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Golden'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Golden Eggs'),
                'value' => $avg($stats->golden_eggs),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'Eggs'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Power Eggs'),
                'value' => $avg($stats->power_eggs, 0),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon3', 'Boss'),
                'labelTitle' => Yii::t('app-salmon3', 'Average Defeated'),
                'value' => $avg($stats->boss_defeated, 2),
                'nullDisplay' => Html::tag('span', Html::encode('-'), ['class' => 'text-muted']),
                'nullDisplayFormat' => 'raw',
                'formatter' => $fmt,
            ],
        ];
        return Html::tag(
            'div',
            implode(
                '',
                array_map(
                    fn (array $item): string => MiniinfoData::widget($item),
                    $data,
                ),
            ),
            ['class' => 'row'],
        );
    }

    protected function renderLinkToBattles(): string
    {
        return Html::tag(
            'div',
            Html::a(
                implode('', [
                    (string)FA::fas('paint-roller')->fw(),
                    Html::tag('span', Html::encode(Yii::t('app', 'Battles'))),
                    (string)FA::fas('angle-right')->fw(),
                ]),
                ['show-v3/user',
                    'screen_name' => $this->user->screen_name,
                ],
                [
                    'class' => [
                        'btn',
                        'btn-sm',
                        'btn-block',
                        'btn-default',
                    ],
                ],
            ),
            ['class' => 'miniinfo-databox'],
        );
    }

    protected function renderActivity(): string
    {
        return Html::tag(
            'div',
            implode('', [
                Html::tag(
                    'div',
                    Html::encode(Yii::t('app', 'Activity')),
                    ['class' => 'user-label'],
                ),
                Html::tag(
                    'div',
                    ActivityWidget::widget([
                        'user' => $this->user,
                        'months' => 4,
                        'longLabel' => false,
                        'size' => 9,
                        'only' => 'salmon3',
                    ]),
                    ['class' => 'table-responsive bg-white text-body'],
                ),
            ]),
            ['class' => 'miniinfo-databox'],
        );
    }

    protected function renderLinks(): string
    {
        $user = $this->user;
        $links = [
            Yii::t('app-salmon3', 'Stats') => ['salmon-v3/stats-stats', 'screen_name' => $user->screen_name],
            Yii::t('app-salmon3', 'Stats (Bosses)') => ['salmon-v3/stats-bosses', 'screen_name' => $user->screen_name],
            Yii::t('app', 'Badge Progress') => ['show-v3/stats-badge', 'screen_name' => $user->screen_name],
        ];

        return Html::tag(
            'ul',
            implode('', array_map(
                fn (string $text, array $link): string => Html::tag(
                    'li',
                    implode(' ', [
                        Icon::stats(),
                        Html::a(
                            Html::encode($text),
                            $link,
                        ),
                    ]),
                    [
                        'class' => 'm-0 p-0',
                        'style' => 'list-style-type:none',
                    ],
                ),
                array_keys($links),
                array_values($links),
            )),
            [
                'class' => 'mt-3 mb-0 p-0',
                'style' => 'list-style-type:none',
            ],
        );
    }

    protected function getUserStats(): UserStatSalmon3
    {
        return UserStatSalmon3::find()->andWhere(['user_id' => $this->user->id])->limit(1)->one()
            ?? Yii::createObject(UserStatSalmon3::class);
    }
}
