<?php

declare(strict_types=1);

use app\assets\GameModeIconsAsset;
use app\assets\SalmonEggAsset;
use app\components\widgets\Icon;
use app\components\widgets\v3\BigrunPercentile;
use app\components\widgets\v3\weaponIcon\WeaponIcon;
use app\models\Salmon3;
use app\models\SalmonScheduleWeapon3;
use app\models\UserStatBigrun3;
use yii\helpers\Html;

return [
  'label' => Yii::t('app-salmon2', 'Rotation'),
  'format' => 'raw',
  'value' => function (Salmon3 $model): ?string {
    if ($model->is_private) {
      return Html::encode(
        Yii::t('app-salmon3', 'Private Job'),
      );
    }

    if ($model->is_eggstra_work) {
      return Html::encode(
        Yii::t('app-salmon3', 'Eggstra Work'),
      );
    }

    $schedule = $model->schedule;
    if (!$schedule) {
      return null;
    }

    $weapons = SalmonScheduleWeapon3::find()
      ->with(['weapon', 'random'])
      ->andWhere(['schedule_id' => $schedule->id])
      ->orderBy(['id' => SORT_ASC])
      ->all();
    if (!$weapons) {
      return null;
    }

    $parts = [];
    $parts[] = Html::a(
      Icon::search(),
      ['salmon-v3/index',
        'screen_name' => $model->user?->screen_name,
        'f' => [
          'term' => 'term',
          'term_from' => sprintf('@%d', (int)strtotime($schedule->start_at)),
          'term_to' => sprintf('@%d', (int)strtotime($schedule->end_at) - 1),
        ],
      ],
    );

    if ($schedule->big_map_id) {
      $asset = GameModeIconsAsset::register(Yii::$app->view);
      $parts[] = Html::img(
        Yii::$app->assetManager->getAssetUrl($asset, 'spl3/salmon-bigrun.png'),
        [
          'title' => Yii::t('app-salmon3', 'Big Run'),
          'class' => 'auto-tooltip basic-icon',
        ],
      );
    }

    $parts[] = implode('', array_map(
      function (SalmonScheduleWeapon3 $info): string {
        if ($info->weapon || $info->random) {
          Yii::$app->view->registerCss(vsprintf('.schedule-weapon-icon{%s}', [
            Html::cssStyleFromArray([
              'background' => '#333',
              'border-radius' => '50%',
              'display' => 'inline-block',
              'margin' => '0 0.333em 0 0',
              'padding' => '0.25em',
            ]),
          ]));
        }

        return Html::tag(
          'span',
          WeaponIcon::widget([
            'model' => $info->weapon ?? $info->random,
          ]),
          ['class' => 'schedule-weapon-icon'],
        );
      },
      $weapons,
    ));

    if ($schedule->big_map_id) {
      $bigrunStats = UserStatBigrun3::find()
        ->andWhere([
            'user_id' => $model->user_id,
            'schedule_id' => $schedule->id,
        ])
        ->limit(1)
        ->one();
      if ($bigrunStats && $bigrunStats->golden_eggs > 0) {
        $asset = SalmonEggAsset::register(Yii::$app->view);
        $parts[] = vsprintf('%s %s', [
          Html::img(
            Yii::$app->assetManager->getAssetUrl($asset, 'golden-egg.png'),
            [
              'class' => 'auto-tooltip basic-icon',
              'title' => Yii::t('app-salmon3', 'High Score'),
            ],
          ),
          Yii::$app->formatter->asInteger($bigrunStats->golden_eggs),
        ]);

        $parts[] = BigrunPercentile::widget([
          'schedule' => $schedule,
        ]);
      }
    }

    return implode(' ', $parts);
  },
];
