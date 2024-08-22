<?php

/**
 * @copyright Copyright (C) 2015-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\widgets;

use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\assets\LuxonAsset;
use app\models\User;
use statink\yii2\calHeatmap\CalHeatmapTooltipAsset;
use statink\yii2\calHeatmap\CalHeatmapWidget;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

use function array_filter;
use function array_keys;
use function array_values;
use function implode;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtolower;
use function time;

final class ActivityWidget extends CalHeatmapWidget
{
    public ?User $user = null;
    public ?string $only = null;
    public int $months = 12;
    public bool $longLabel = true;
    public int $size = 10;

    public function init()
    {
        parent::init();

        $this->options = [
            'style' => [
                'padding' => '5px 2px',
            ],
        ];

        if (!$user = $this->user) {
            throw new InvalidConfigException();
        }

        $apiUrl = Url::to(
            array_filter(
                ['api-internal/activity',
                    'screen_name' => $user->screen_name,
                    'only' => $this->only,
                ],
            ),
        );
        $this->jsOptions = [
            'data' => [
                'source' => $apiUrl,
                'type' => 'json',
                'x' => $this->renderDataConverterX(),
                'y' => $this->renderDataConverterY(),
            ],
            'date' => [
                'start' => $this->renderStartTime(),
                'locale' => preg_replace( // workaround for #1202
                    '/^([a-z]+)-.+$/',
                    '$1',
                    self::getDayjsLocaleName(strtolower((string)Yii::$app->language)),
                ),
                'timezone' => 'Etc/UTC',
            ],
            'range' => $this->months,
            'scale' => [
                'color' => [
                    'domain' => [0, 30],
                    'scheme' => $this->isHalloweenTerm() ? 'Oranges' : 'Greens',
                    'type' => 'linear',
                ],
            ],
            'subDomain' => [
                'height' => $this->size,
                'width' => $this->size,
            ],
            // 'legendTitleFormat' => [
            //     'lower' => Yii::t('app', 'less than {min} {name}'),
            //     'inner' => Yii::t('app', 'between {down} and {up} {name}'),
            //     'upper' => Yii::t('app', 'more than {max} {name}'),
            // ],
            // 'displayLegend' => false,
        ];

        $view = $this->view;
        if ($view instanceof View) {
            LuxonAsset::register($view);
            CalHeatmapTooltipAsset::register($view);
            $this->plugins[] = [
                new JsExpression('window.Tooltip'),
                [
                    'enabled' => true,
                    'text' => $this->renderTooltipFormatter(),
                ],
            ];
        }
    }

    private function renderDataConverterX(): JsExpression
    {
        return new JsExpression(
            'function(e){return window.luxon.DateTime.fromISO(e.date,{zone:"Etc/UTC"}).toMillis()}',
        );
    }

    private function renderDataConverterY(): JsExpression
    {
        return new JsExpression(
            'function(e){return e.count}',
        );
    }

    protected function renderStartTime(): JsExpression
    {
        $today = (new DateTimeImmutable())
            ->setTimezone(new DateTimeZone('Etc/UTC'))
            ->setTimestamp((int)($_SERVER['REQUEST_TIME'] ?? time()))
            ->setTime(0, 0, 0);

        $date = $today->setDate(
            (int)$today->format('Y'),
            (int)$today->format('n') - $this->months + 1,
            1,
        );

        return new JsExpression(sprintf(
            'new Date(%s)',
            Json::encode($date->format('Y-m-d')),
        ));
    }

    private function renderTooltipFormatter(): JsExpression
    {
        // (timestamp, value, dayjsDate) => {
        //   const singular = '{singular}'; // needs replace
        //   const plural = '{plural}'; // needs replace
        //   const noBattle = '{noBattle}'; // needs replace
        //   const date = dayjsDate.format('L');
        //
        //   return (value === null || value < 1)
        //     ? `${date}: ${noBattle}`
        //     : `${date}: ${value} ${value !== 1 ? plural : singular}`;
        // }

        $varMap = [
            '"{singular}"' => Json::encode(Yii::t('app', '{n,plural,=1{battle} other{battles}}', ['n' => 1])),
            '"{plural}"' => Json::encode(Yii::t('app', '{n,plural,=1{battle} other{battles}}', ['n' => 42])),
            '"{noBattle}"' => Json::encode(Yii::t('app', 'No battles')),
        ];

        return new JsExpression(
            str_replace(
                array_keys($varMap),
                array_values($varMap),
                implode('', [
                    'function(t,o,c){c=c.format("L");return null===o||o<1?""',
                    '.concat(c,": ").concat("{noBattle}"):"".concat(c,": ")',
                    '.concat(o," ").concat(1!==o?"{plural}":"{singular}")}',
                ]),
            ),
        );
    }

    private function isHalloweenTerm(): bool
    {
        $now = (new DateTimeImmutable())
            ->setTimestamp((int)($_SERVER['REQUEST_TIME'] ?? time()))
            ->setTimezone(new DateTimeZone(Yii::$app->timeZone));
        $month = (int)$now->format('n');
        $day = (int)$now->format('j');

        return ($month === 10 && $day > 24) || ($month === 11 && $day === 1);
    }
}
