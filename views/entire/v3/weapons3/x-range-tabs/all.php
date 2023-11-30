<?php

declare(strict_types=1);

use app\components\widgets\Icon;
use app\models\StatWeapon3XUsageRange;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var StatWeapon3XUsageRange[] $xRanges
 * @var View $this
 * @var bool $isActive
 * @var callable(StatWeapon3XUsageRange|null): string $xRangeUrl
 */

echo Html::tag(
  'li',
  Html::tag(
    'a',
    trim(
      implode(' ', [
        Icon::s3LobbyX(),
        Html::encode(Yii::t('app', 'All')),
      ]),
    ),
    $isActive ? [] : ['href' => $xRangeUrl(null)],
  ),
  [
    'role' => 'presentation',
    'class' => $isActive ? 'active' : false,
  ],
);
