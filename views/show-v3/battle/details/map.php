<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\components\widgets\Icon;
use app\models\Battle3;
use yii\helpers\Html;

return [
  'attribute' => 'map_id',
  'format' => 'raw',
  'label' => Yii::t('app', 'Stage'),
  'value' => function (Battle3 $model): ?string {
    if (!$map = $model->map) {
      return null;
    }

    return implode(' ', [
      Html::a(
        Icon::search(),
        ['/show-v3/user',
          'screen_name' => $model->user->screen_name,
          'f' => [
            'map' => $map->key,
          ],
        ]
      ),
      Html::encode(Yii::t('app-map3', $map->name)),
    ]);
  },
];
