<?php

/**
 * @copyright Copyright (C) 2016 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\actions\api\info;

use Yii;
use app\components\helpers\Translator;
use app\models\Gear;
use app\models\GearType;
use app\models\Language;
use yii\web\NotFoundHttpException;
use yii\web\ViewAction as BaseAction;

class GearAction extends BaseAction
{
    public $type;

    public function getType()
    {
        return GearType::findOne(['key' => (string)$this->type]);
    }

    public function init()
    {
        parent::init();
        if (!$this->getType()) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    public function run()
    {
        $type = $this->getType();
        $gears = array_map(
            fn (array $gear): array => [
                'key'   => $gear['key'],
                'name'  => Yii::t('app-gear', $gear['name']),
                'names' => Translator::translateToAll('app-gear', $gear['name']),
                'brand' => Yii::t('app-brand', $gear['brand']['name'] ?? null),
                'ability' => Yii::t('app-ability', $gear['ability']['name'] ?? null),
            ],
            Gear::find()
                ->with(['brand', 'ability'])
                ->andWhere(['type_id' => $type->id])
                ->asArray()
                ->all()
        );
        usort($gears, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

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

        return $this->controller->render('gear', [
            'type'  => $type,
            'types' => GearType::find()->asArray()->orderBy(['id' => SORT_ASC])->all(),
            'langs' => $langs,
            'gears' => $gears,
        ]);
    }
}
