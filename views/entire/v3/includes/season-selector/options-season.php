<?php

/**
 * @copyright Copyright (C) 2023-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\models\Season3;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var Season3[] $seasons
 * @var Season3|null $season
 * @var View $this
 * @var callable(Season3): string $seasonUrl
 */

echo implode(
  '',
  ArrayHelper::getColumn(
    $seasons,
    fn (Season3 $model): string => Html::tag(
      'option',
      Html::encode(Yii::t('app-season3', $model->name)),
      [
        'selected' => $model->key === $season?->key,
        'value' => $seasonUrl($model),
      ],
    ),
  ),
);
