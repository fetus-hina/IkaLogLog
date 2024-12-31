<?php

/**
 * @copyright Copyright (C) 2021-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\components\widgets\Icon;
use app\models\Splatfest2;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var Splatfest2 $fest
 * @var User $user
 * @var View $this
 */

echo Html::tag(
  'h2',
  implode('', [
    Html::tag(
      'small', 
      Html::tag(
        'a',
        Icon::permalink(),
        [
          'href' => '#' . $fest->permaID,
        ],
      ),
      ['style' => ['font-size' => '1rem']]
    ),
    Yii::t('app-fest2', '{alpha} vs. {bravo}', [
      'alpha' => Yii::t('app-fest2', $fest->name_a),
      'bravo' => Yii::t('app-fest2', $fest->name_b),
    ]),
    Html::tag(
      'small',
      Html::a(
        Icon::search(),
        ['show-v2/user',
          'screen_name' => $user->screen_name,
          'filter' => [
            'rule' => 'any-fest-nawabari',
            'term' => 'term',
            'term_from' => $fest->beginTime
              ->setTimezone(new DateTimeZone('Etc/UTC'))
              ->sub(new DateInterval('PT1H'))
              ->format('Y-m-d H:i:s'),
            'term_to' => $fest->endTime
              ->setTimezone(new DateTimeZone('Etc/UTC'))
              ->add(new DateInterval('PT1H'))
              ->format('Y-m-d H:i:s'),
            'timezone' => 'Etc/UTC',
          ],
        ]
      ),
      ['style' => ['font-size' => '1rem']]
    ),
  ])
);
