<?php

/**
 * @copyright Copyright (C) 2023-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\models\MedalCanonical3;
use app\models\Rule3;
use app\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var User $user
 * @var View $this
 * @var array<string, MedalCanonical3> $medals
 * @var array<string, Rule3> $rules
 * @var array<string, array<string, int>> $stats
 */

foreach ($medals as $medalKey => $medal) {
  $medalStats = ArrayHelper::getValue($stats, $medalKey);
  if (!$medalStats) {
    continue;
  }

  $total = array_sum(array_values($medalStats));
  if ($total < 1) {
    continue;
  }

  echo Html::tag(
    'tr',
    implode(
      '',
      [
        $this->render('cell-medal', compact('medal')),
        $this->render('cell-data', ['count' => $total]),
        implode(
          '',
          array_map(
            function (string $ruleKey) use ($medalStats): string {
              return $this->render('cell-data', [
                'count' => (int)ArrayHelper::getValue($medalStats, $ruleKey, 0),
              ]);
            },
            array_keys($rules),
          ),
        ),
      ],
    ),
  ) . "\n";
}
