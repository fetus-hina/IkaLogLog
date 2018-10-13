<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\widgets;

use Yii;
use app\assets\UserMiniinfoAsset;
use app\components\i18n\Formatter;
use app\models\SalmonStats2;
use yii\base\Widget;
use yii\bootstrap\BootstrapAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class SalmonUserInfo extends Widget
{
    public $user;

    public function init()
    {
        parent::init();

        BootstrapAsset::register($this->view);
        UserMiniinfoAsset::register($this->view);
    }

    public function getId($autoGenerate = true)
    {
        return 'user-miniinfo';
    }

    public function run()
    {
        return Html::tag(
            'div',
            Html::tag(
                'div',
                implode('', [
                    $this->renderIconAndName(),
                    $this->renderData(),
                ]),
                ['id' => 'user-miniinfo-box']
            ),
            [
                'id' => $this->id,
                'itemscope' => null,
                'itemtype' => 'http://schema.org/Person',
                'itemprop' => 'author',
            ]
        );
    }

    protected function renderIconAndName(): string
    {
        return Html::tag(
            'h2',
            Html::a(
                implode('', [
                    $this->renderIcon(),
                    $this->renderName(),
                ]),
                ['show-user/profile', 'screen_name' => $this->user->screen_name]
            )
        );
    }

    protected function renderIcon(): string
    {
        return Html::tag(
            'span',
            Html::img(
                $this->user->iconUrl,
                [
                    'width' => 48,
                    'height' => 48,
                    'alt' => '',
                    'itemprop' => 'image',
                ],
            ),
            ['class' => 'miniinfo-user-icon']
        );
    }

    protected function renderName(): string
    {
        return Html::tag('span', Html::encode($this->user->name), [
            'class' => 'miniinfo-user-name',
            'itemprop' => 'name',
        ]);
    }

    protected function renderData(): string
    {
        $fmt = Yii::createObject([
            'class' => Formatter::class,
            'nullDisplay' => Yii::t('app', 'N/A'),
        ]);
        $stats = $this->getUserStats();
        $avg = function ($value, int $decimal = 1) use ($fmt, $stats): string {
            return $fmt->asDecimal(
                $stats->work_count > 0 ? $value / $stats->work_count : null,
                $decimal
            );
        };
        $data = [
            [
                'label' => Yii::t('app-salmon2', 'Works'),
                'value' => $stats->work_count,
                'valueFormat' => 'integer',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Ttl. Pts.'),
                'labelTitle' => Yii::t('app-salmon2', 'Total Points'),
                'value' => $stats->total_point,
                'valueTitle' => $fmt->asInteger($stats->total_point),
                'valueFormat' => 'metricPrefixed',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Avg. Pts.'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Points'),
                'value' => $avg($stats->total_point, 1),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Golden'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Golden Eggs'),
                'value' => $avg($stats->total_golden_eggs),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Pwr Eggs'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Power Eggs'),
                'value' => $avg($stats->total_eggs),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Rescued'),
                'labelTitle' => Yii::t('app-salmon2', 'Average Rescued'),
                'value' => $avg($stats->total_rescued),
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Ttl. Gold'),
                'labelTitle' => Yii::t('app-salmon2', 'Total Golden Eggs'),
                'value' => $stats->total_golden_eggs,
                'valueTitle' => $fmt->asInteger($stats->total_golden_eggs),
                'valueFormat' => 'metricPrefixed',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Ttl. Eggs'),
                'labelTitle' => Yii::t('app-salmon2', 'Total Power Eggs'),
                'value' => $stats->total_eggs,
                'valueTitle' => $fmt->asInteger($stats->total_eggs),
                'valueFormat' => 'metricPrefixed',
                'formatter' => $fmt,
            ],
            [
                'label' => Yii::t('app-salmon2', 'Ttl. Rescued'),
                'labelTitle' => Yii::t('app-salmon2', 'Total Rescued'),
                'value' => $stats->total_rescued,
                'valueTitle' => $fmt->asInteger($stats->total_rescued),
                'valueFormat' => 'metricPrefixed',
                'formatter' => $fmt,
            ],
        ];
        $datetime = ($stats->as_of !== null)
            ? Html::tag(
                'div',
                Html::tag(
                    'div',
                    Yii::t('app-salmon2', 'As of {datetime}', [
                        'datetime' => $fmt->asDatetime($stats->as_of, 'medium'),
                    ]),
                    ['class' => 'user-label text-right']
                ),
                [
                    'class' => 'col-xs-12',
                    'style' => [
                        'margin-top' => '10px',
                    ],
                ]
            )
            : '';
        return Html::tag(
            'div',
            implode('', array_map(
                function (array $item): string {
                    return MiniinfoData::widget($item);
                },
                $data
            )) . $datetime,
            ['class' => 'row']
        );
    }

    protected function getUserStats(): SalmonStats2
    {
        $model = SalmonStats2::find()
            ->andWhere(['user_id' => $this->user->id])
            ->orderBy(['as_of' => SORT_DESC])
            ->limit(1)
            ->one();

        // なければダミーデータを返す
        return $model ?: new SalmonStats2();
    }
}
