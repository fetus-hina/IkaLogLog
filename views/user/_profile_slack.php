<?php

declare(strict_types=1);

use app\assets\EmojifyResourceAsset;
use app\assets\SlackAsset;
use app\models\User;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use app\components\helpers\Html;
use yii\web\View;

/**
 * @var User $user
 * @var View $this
 */

SlackAsset::register($this);
?>
<h2>
  <?= Html::encode(Yii::t('app', 'Slack Integration')) . "\n" ?>
  <?= Html::a(
    '<span class="fas fa-plus"></span>',
    ['slack-add'],
    ['class' => 'btn btn-primary']
  ) . "\n" ?>
</h2>
<?= GridView::widget([
  'dataProvider' => new ActiveDataProvider([
    'query' => $user->getSlacks()->with('language'),
    'pagination' => false,
    'sort' => false
  ]),
  'columns' => [
    [
      'label' => Yii::t('app', 'Enabled'),
      'format' => 'raw',
      'value' => function ($model) : string {
        return Html::checkbox(
          sprintf('slack-%d', $model->id),
          !$model->suspended,
          [
            "class" => [ "slack-toggle-enable" ],
            "data" => [
              "toggle" => "toggle",
              "on" => Yii::t('app', 'Enabled'),
              "off" => Yii::t('app', 'Disabled'),
              "id" => $model->id
            ],
            "disabled" => true
          ]
        );
      },
    ],
    [
      'label' => Yii::t('app', 'User Name'),
      'value' => function ($model) : string {
        $value = trim((string)$model->username);

        if ($value === '') {
          return Yii::t('app', '(default)');
        }

        return $value;
      },
    ],
    [
      'label' => Yii::t('app', 'Icon'),
      'format' => 'raw',
      'value' => function ($model) : string {
        $value = trim((string)$model->icon);

        if ($value === '') {
          return Html::encode(Yii::t('app', '(default)'));
        }

        if (strtolower(substr($value, 0, 4)) === 'http' || substr($value, 0, 2) === '//') {
          return Html::img($value, ['class' => 'emoji emoji-url']);
        }

        if (preg_match('/^:[a-zA-Z0-9+._-]+:$/', $value)) {
          $asset = EmojifyResourceAsset::register($this);
          $fileName = trim((string)$value, ':') . '.png';
          return implode(' ', [
            Html::img(
              Yii::$app->assetManager->getAssetUrl($asset, $fileName),
              ['style' => 'height:2em;width:auto']
            ),
            Html::encode($value),
          ]);
        }

        return Html::encode($value);
      },
    ],
    [
      'label' => Yii::t('app', 'Channel'),
      'value' => function ($model) : string {
        $value = trim((string)$model->channel);
        if ($value === '') {
          return Yii::t('app', '(default)');
        }

        return $value;
      },
    ],
    [
      'label' => Yii::t('app', 'Language'),
      'attribute' => 'language.name',
    ],
    [
      'label' => '',
      'format' => 'raw',
      'value' => function ($model) : string {
        return implode(' ', [
          Html::tag(
            'button',
            Html::encode(Yii::t('app', 'Test')),
            [
              'class' => 'slack-test btn btn-info btn-sm',
              'data' => [
                'id' => $model->id,
              ],
              'disabled' => true,
            ]
          ),
          Html::tag(
            'button',
            Html::encode(Yii::t('app', 'Delete')),
            [
              'class' => 'slack-del btn btn-danger btn-sm',
              'data' => [
                'id' => $model->id,
              ],
              'disabled ' => true,
            ]
          ),
        ]);
      },
    ],
  ],
]) ?>
