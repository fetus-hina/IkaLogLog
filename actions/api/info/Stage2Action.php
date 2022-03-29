<?php

/**
 * @copyright Copyright (C) 2015-2020 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\actions\api\info;

use Yii;
use app\models\Language;
use app\models\Map2;
use yii\web\ViewAction as BaseAction;

class Stage2Action extends BaseAction
{
    public function run()
    {
        // @phpstan-ignore-next-line
        $langs = Language::find()->standard()->all();
        $stages = Map2::find()->all();
        $sysLang = Yii::$app->language;

        usort($langs, function (Language $a, Language $b) use ($sysLang): int {
            if ($a->lang === $sysLang) {
                return -1;
            }
            if ($b->lang === $sysLang) {
                return 1;
            }
            return strnatcasecmp($a->name, $b->name);
        });

        usort($stages, function (Map2 $a, Map2 $b): int {
            if (
                substr($a->key, 0, strlen('mystery')) === 'mystery' &&
                substr($b->key, 0, strlen('mystery')) !== 'mystery'
            ) {
                return 1;
            } elseif (
                substr($a->key, 0, strlen('mystery')) !== 'mystery' &&
                substr($b->key, 0, strlen('mystery')) === 'mystery'
            ) {
                return -1;
            } else {
                return strnatcasecmp(Yii::t('app-map2', $a->name), Yii::t('app-map2', $b->name))
                    ?: strnatcasecmp($a->name, $b->name)
                    ?: strnatcasecmp($a->key, $b->key)
                    ?: strcmp($a->key, $b->key);
            }
        });

        return $this->controller->render('stage2', [
            'stages' => $stages,
            'langs' => $langs,
        ]);
    }
}
