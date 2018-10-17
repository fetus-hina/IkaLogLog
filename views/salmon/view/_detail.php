<?php
declare(strict_types=1);

use app\components\helpers\Battle as BattleHelper;
use app\components\i18n\Formatter;
use app\components\widgets\Label;
use app\components\widgets\PrivateNote;
use app\models\Salmon2;
use app\models\SalmonTitle2;
use yii\bootstrap\Progress;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

$formatter = Yii::createObject([
  'class' => Formatter::class,
  'nullDisplay' => '',
]);

$widget = Yii::createObject([
  'class' => DetailView::class,
  'model' => $model,
  'formatter' => $formatter,
  'options' => [
    'class' => 'table table-striped',
  ],
  'attributes' => [
    [
      'attribute' => 'splatnet_number',
      'format' => 'integer',
    ],
    [
      'attribute' => 'stage_id',
      'value' => Yii::t('app-salmon-map2', $model->stage->name ?? null),
    ],
    [
      'label' => Yii::t('app', 'Result'),
      'format' => 'raw',
      'value' => function (Salmon2 $model, DetailView $widget): string {
        $labels = [];
        $isCleared = $model->getIsCleared(); // true | false | null
        if ($isCleared === true) {
          $labels[] = Label::widget([
            'color' => 'success',
            'content' => Yii::t('app-salmon2', 'Cleared'),
          ]);
        } elseif ($isCleared === false) {
          $labels[] = Label::widget([
            'color' => 'danger',
            'content' => Yii::t('app-salmon2', 'Failed in wave {waveNumber}', [
              'waveNumber' => $widget->formatter->asInteger($model->clear_waves + 1),
            ]),
          ]);
          if ($model->fail_reason_id) {
            $labels[] = Label::widget([
              'color' => 'warning',
              'content' => Yii::t('app-salmon2', $model->failReason->name),
            ]);
          }
        }
        return implode(' ', $labels);
      },
    ],
    [
      'label' => Yii::t('app', 'Title'),
      'format' => 'raw',
      'value' => function (Salmon2 $model, DetailView $widget): ?string {
        if (!$model->title_before_id && !$model->title_after_id) {
          return null;
        }

        $fmt = function (?SalmonTitle2 $title, ?int $exp) use ($widget): string {
          if ($title === null) {
            return '?';
          }

          return trim(implode(' ', [
            Yii::t('app-salmon-title2', $title->name),
            $exp === null ? '' : $widget->formatter->asInteger($exp),
          ]));
        };

        return vsprintf('%1$s %3$s %2$s', [
          Html::encode($fmt($model->titleBefore, $model->title_before_exp)),
          Html::encode($fmt($model->titleAfter, $model->title_after_exp)),
          Html::tag('span', '', ['class' => 'fas fa-fw fa-arrow-right']),
        ]);
      },
    ],
    [
      'attribute' => 'danger_rate',
      'format' => 'raw',
      'value' => function (Salmon2 $model, DetailView $widget): ?string {
        if ($model->danger_rate === null) {
          return null;
        }
        $parts = [];
        $parts[] = Progress::widget([
          'percent' => min(100, $model->danger_rate * 100 / 200),
          'label' => $model->danger_rate,
          'barOptions' => ['class' => 'progress-bar progress-bar-danger'],
          // options
        ]);
        $this->registerCss("#{$widget->id} .progress{margin-bottom:0}");

        if ($quota = $model->quota) {
          $parts[] = Html::encode(sprintf(
            '(%s)',
            Yii::t('app-salmon2', 'Quota: {wave1} - {wave2} - {wave3}', [
              'wave1' => $widget->formatter->asInteger($quota[0]),
              'wave2' => $widget->formatter->asInteger($quota[1]),
              'wave3' => $widget->formatter->asInteger($quota[2]),
            ])
          ));
        }
        return implode(' ', $parts);

        return sprintf(
          '%s (%s)',
          $widget->formatter->asDecimal($model->danger_rate, 1),
        );
      },
    ],
    [
      'attribute' => 'link_url',
      'format' => 'url',
      'value' => 'http://example.com',
    ],
    [
      'attribute' => 'shift_period',
      'value' => function (Salmon2 $model, DetailView $widget): ?string {
        $period = $model->shift_period;
        if ($period === null) {
          return null;
        }
        list ($periodStart, ) = BattleHelper::periodToRange2($period);
        return Yii::t('app-salmon2', 'From {shiftStart}', [
          'shiftStart' => $widget->formatter->asDateTime($periodStart, 'short'),
        ]);
      },
    ],
    [
      'attribute' => 'start_at',
      'format' => 'datetime',
    ],
    [
      'attribute' => 'end_at',
      'format' => 'datetime',
    ],
    [
      'attribute' => 'created_at',
      'format' => 'datetime',
    ],
    [
      'attribute' => 'agent_id',
      'format' => 'raw',
      'value' => function (Salmon2 $model, DetailView $widget): ?string {
        if (!$model->agent) {
          return null;
        }
        return implode(' / ', [
          $model->agent->productUrl
            ? Html::a(
              Html::encode($model->agent->name),
              $model->agent->productUrl,
              ['target' => '_blank', 'rel' => 'nofollow']
            )
            : Html::encode($model->agent->name),
          $model->agent->versionUrl
            ? Html::a(
              Html::encode($model->agent->version),
              $model->agent->versionUrl,
              ['target' => '_blank', 'rel' => 'nofollow']
            )
            : Html::encode($model->agent->version),
        ]);
      },
    ],
    [
      'attribute' => 'note',
      'format' => 'ntext',
    ],
    [
      'attribute' => 'private_note',
      'format' => 'raw',
      'value' => function (Salmon2 $model, DetailView $widget): ?string {
        if ($model->private_note === null || $model->private_note == '') {
          return null;
        }

        if (Yii::$app->user->isGuest || Yii::$app->user->identity->id != $model->user_id) {
          return null;
        }

        return PrivateNote::widget([
          'text' => $model->private_note,
          'formatter' => $widget->formatter,
        ]);
      },
    ],
  ],

  // Override \yii\widgets\DetailView::renderAttribute() to skip empty cell
  // https://github.com/yiisoft/yii2/blob/558cd482d8a0525fceec8646ea20ccc9b7c3fdf4/framework/widgets/DetailView.php#L167-L187
  'template' => function (array $attribute, int $index, DetailView $widget): string {
    $value = $widget->formatter->format($attribute['value'], $attribute['format']);
    if ($value == '') {
      return '';
    }

    $captionOptions = Html::renderTagAttributes(
      ArrayHelper::getValue($attribute, 'captionOptions', [])
    );
    $contentOptions = Html::renderTagAttributes(
      ArrayHelper::getValue($attribute, 'contentOptions', [])
    );
    return strtr('<tr><th{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>', [
      '{label}' => $attribute['label'],
      '{value}' => $value,
      '{captionOptions}' => $captionOptions,
      '{contentOptions}' => $contentOptions,
    ]);
  },
]);

$this->registerCss(implode('', [
  "#{$widget->id} th{width:15em}",
  "@media(max-width:30em){#{$widget->id} th{width:auto}}",
]));

echo $widget->run();
