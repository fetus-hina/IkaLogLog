<?php

/**
 * @copyright Copyright (C) 2023-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\models\StatWeapon3Usage;
use app\models\StatWeapon3UsagePerVersion;
use app\models\StatWeapon3XUsage;
use app\models\StatWeapon3XUsagePerVersion;

$calc = fn (StatWeapon3Usage|StatWeapon3UsagePerVersion|StatWeapon3XUsage|StatWeapon3XUsagePerVersion $model): ?float => $model->seconds > 0 && $model->battles > 0
  ? ($model->avg_kill + $model->avg_assist) / ($model->seconds / $model->battles) * 60.0
  : null;

return [
  'contentOptions' => fn (StatWeapon3Usage|StatWeapon3UsagePerVersion|StatWeapon3XUsage|StatWeapon3XUsagePerVersion $model): array => [
    'class' => 'text-right',
    'data-sort-value' => $calc($model),
  ],
  'format' => ['decimal', 3],
  'headerOptions' => [
    'data-sort' => 'float',
    'data-sort-default' => 'desc',
  ],
  'filter' => (require __DIR__ . '/includes/correlation-filter.php')($calc),
  'filterOptions' => ['class' => 'text-right'],
  'label' => Yii::t('app', 'K+A/min'),
  'value' => $calc,
];
