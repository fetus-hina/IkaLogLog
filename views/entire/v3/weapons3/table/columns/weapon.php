<?php

/**
 * @copyright Copyright (C) 2023-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\components\widgets\v3\WeaponName;
use app\models\StatWeapon3Usage;
use app\models\StatWeapon3UsagePerVersion;
use app\models\StatWeapon3XUsage;
use app\models\StatWeapon3XUsagePerVersion;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$getParams = Yii::$app->request->get();
$weaponUrl = fn (StatWeapon3Usage|StatWeapon3UsagePerVersion|StatWeapon3XUsage|StatWeapon3XUsagePerVersion $model): array => [
  'entire/weapon3',
  'lobby' => ArrayHelper::getValue($getParams, 'lobby'),
  'rule' => ArrayHelper::getValue($getParams, 'rule'),
  'season' =>ArrayHelper::getValue($getParams, 'season'),
  'version' => ArrayHelper::getValue($getParams, 'version'),
  'weapon' => ArrayHelper::getValue($model, 'weapon.key'),
];

return [
  'contentOptions' => fn (StatWeapon3Usage|StatWeapon3UsagePerVersion|StatWeapon3XUsage|StatWeapon3XUsagePerVersion $model): array => [
    'data-sort-value' => Yii::t('app-weapon3', $model->weapon->name),
  ],
  'filter' => Html::encode(Yii::t('app', 'Correlation with Win %')),
  'format' => 'raw',
  'headerOptions' => ['data-sort' => 'string'],
  'label' => Yii::t('app', 'Weapon'),
  'value' => fn (StatWeapon3Usage|StatWeapon3UsagePerVersion|StatWeapon3XUsage|StatWeapon3XUsagePerVersion $model): string => Html::a(
    WeaponName::widget([
      'model' => $model->weapon,
      'showName' => true,
      'subInfo' => false,
    ]),
    $weaponUrl($model),
  ),
];
