<?php

/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\widgets;

use Yii;
use app\assets\AppAsset;
use app\assets\LanguageDialogAsset;
use app\assets\TimezoneDialogAsset;
use app\models\Country;
use app\models\Language;
use app\models\Timezone;
use yii\base\Widget;
use yii\helpers\Html;

class IndexI18nButtons extends Widget
{
    public function run()
    {
        return Html::tag(
            'div',
            $this->renderButtons(),
            [
                'class' => [
                    'd-inline-block',
                    'mb-2',
                ],
            ]
        );
    }

    public function renderButtons(): string
    {
        return implode('', [
            $this->renderLanguageButton(),
            $this->renderTimezoneButton(),
        ]);
    }

    public function renderLanguageButton(): string
    {
        LanguageDialogAsset::register($this->view);

        $lang = Language::findOne([
            'lang' => Yii::$app->locale,
        ]);
        return $this->renderButton(
            '#language-dialog',
            (string)FA::fas('language')->fw(),
            $lang
                ? [
                    (string)FlagIcon::fg(strtolower($lang->getCountryCode()))
                ]
                : [],
            $lang
                ? sprintf(
                    '%s / %s',
                    Html::encode($lang->name),
                    Html::tag('small', Html::encode($lang->name_en)),
                )
                : Html::encode('Language'),
            'Language',
        );
    }

    public function renderTimezoneButton(): string
    {
        TimezoneDialogAsset::register($this->view);

        $tz = Timezone::findOne([
            'identifier' => Yii::$app->timeZone,
        ]);
        return $this->renderButton(
            '#timezone-dialog',
            (string)FA::far('clock')->fw(),
            $tz
                ? array_map(
                    function (Country $country): string {
                        return (string)FlagIcon::fg($country->key);
                    },
                    $tz->countries,
                )
                : [],
            $tz
                ? Html::encode(Yii::t('app-tz', $tz->name))
                : Html::encode(Yii::t('app', 'Time Zone')),
            Yii::t('app', 'Time Zone'),
        );
    }

    private function renderButton(
        string $target,
        string $icon,
        array $countries,
        string $html,
        string $popup
    ): string {
        return Html::button(
            implode('', [
                Html::tag('span', implode(' ', [
                    $icon,
                    implode(' ', $countries),
                    $html,
                ]), ['class' => 'mr-1']),
                (string)FA::fas('caret-down')->fw(),
            ]),
            [
                'class' => [
                    'btn',
                    'btn-block',
                    'btn-default',
                    'btn-sm',
                    'd-flex',
                    'justify-content-between',
                    'align-items-center',
                    'auto-tooltip',
                ],
                'data' => [
                    'toggle' => 'modal',
                    'target' => $target,
                ],
                'aria-role' => 'button',
                'aria-hidden' => 'true',
                'title' => $popup,
            ]
        );
    }
}
