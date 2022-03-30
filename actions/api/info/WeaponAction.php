<?php

/**
 * @copyright Copyright (C) 2016 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\actions\api\info;

use Yii;
use app\components\helpers\Translator;
use app\models\Language;
use app\models\Weapon;
use app\models\WeaponType;
use yii\web\ViewAction as BaseAction;

use const SORT_ASC;

final class WeaponAction extends BaseAction
{
    public function run()
    {
        $types = array_map(
            fn (WeaponType $type): array => [
                'key'   => $type->key,
                'name'  => Yii::t('app-weapon', $type->name),
                'weapons' => array_map(
                    fn (Weapon $weapon): array => [
                        'key' => $weapon->key,
                        'names' => Translator::translateToAll('app-weapon', $weapon->name),
                    ],
                    Weapon::find()
                        ->andWhere(['type_id' => $type->id])
                        ->orderBy(['key' => SORT_ASC])
                        ->all(),
                ),
            ],
            WeaponType::find()
                ->orderBy(['id' => SORT_ASC])
                ->all(),
        );

        // @phpstan-ignore-next-line
        $langs = Language::find()->standard()->asArray()->all();
        $sysLang = Yii::$app->language;
        usort($langs, function (array $a, array $b) use ($sysLang): int {
            if ($a['lang'] === $sysLang) {
                return -1;
            }
            if ($b['lang'] === $sysLang) {
                return 1;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        return $this->controller->render('weapon', [
            'types' => $types,
            'langs' => $langs,
        ]);
    }
}
