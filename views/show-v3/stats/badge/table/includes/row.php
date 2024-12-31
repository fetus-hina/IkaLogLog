<?php

/**
 * @copyright Copyright (C) 2023-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\assets\Spl3BadgeAsset;
use yii\bootstrap\Progress;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var array{int, ?int, int, int}[] $steps
 * @var bool $isEditing
 * @var int $adjust
 * @var mixed $value Current Value (count), ignored if not integer
 * @var string $badgePath badge asset relative path
 * @var string $icon Icon (most left) URL
 * @var string $itemKey
 * @var string $label Icon (most left) label
 * @var string|null $iconFormat
 */

$fmt = Yii::$app->formatter;
$am = Yii::$app->assetManager;
$bundle = Spl3BadgeAsset::register($this);

$rawValue = (int)(is_scalar($value) ? filter_var($value ?? null, FILTER_VALIDATE_INT) : 0);
$value = $isEditing ? $rawValue : max(0, $rawValue + $adjust);

$step = [0, 1, 0, 0];
foreach ($steps as $tmpStep) {
  if ($tmpStep[0] > $value) {
    break;
  }

  $step = $tmpStep;
}

?>
<tr>
  <?= Html::tag(
    'td',
    ($iconFormat ?? null) === 'raw'
      ? $icon
      : Html::img($icon, [
        'alt' => $label,
        'class' => 'auto-tooltip basic-icon text-center',
        'draggable' => 'false',
        'style' => '--icon-height:2em',
        'title' => $label,
      ]),
    ['class' => 'text-center'],
  ) . "\n" ?>
<?php if ($isEditing) { ?>
  <?= Html::tag(
    'td',
    Html::encode($fmt->asInteger($value)),
    ['class' => 'text-right'],
  ) . "\n" ?>
  <td>
    <div class="form-group m-0">
      <?= Html::input(
        type: 'number',
        name: $itemKey,
        value: $adjust === 0 ? '' : (string)$adjust,
        options: [
        'class' => 'form-control',
        'min' => -99999,
        'max' => 99999,
        'step' => 1,
        ],
      ) . "\n" ?>
    </div>
  </td>
<?php } else { ?>
  <?= Html::tag(
    'td',
    Html::encode($fmt->asInteger($value)),
    [
      'class' => 'auto-tooltip text-right',
      'title' => $adjust === 0
        ? false
        : vsprintf('%s + (%s: %s%s)', [
          $fmt->asInteger($rawValue),
          Yii::t('app', 'Correction Value'),
          $adjust >= 0 ? '+' : '-',
          $fmt->asInteger(abs($adjust)),
        ]),
    ],
  ) . "\n" ?>
  <?= Html::tag(
    'td',
    Html::tag(
      'div',
      implode('', [
        Html::img($am->getAssetUrl($bundle, sprintf('%s/%d.png', $badgePath, $step[2])), [
          'alt' => '',
          'class' => 'basic-icon',
          'draggable' => 'false',
          'style' => '--icon-height:2em',
          'title' => '',
        ]),
        Html::tag(
          'div',
          Progress::widget([
            'bars' => [
              [
                'percent' => $step[1] === null
                  ? 100
                  : 100 * ($value - $step[0]) / ($step[1] - $step[0]),
                'label' => $step[1] === null
                  ? Yii::t('app', 'Completed!')
                  : $fmt->asPercent($value / $step[1], 1),
                'options' => $step[1] === null
                  ? ['class' => 'progress-bar-success']
                  : [],
              ],
              [
                'percent' => $step[1] === null
                  ? 0
                  : 100 * (1 - ($value - $step[0]) / ($step[1] - $step[0])),
                'label' => $step[1] === null
                  ? ''
                  : Yii::t('app', '{nFormatted} remaining', [
                    'n' => $step[1] - $value,
                    'nFormatted' => Yii::$app->formatter->asInteger($step[1] - $value),
                  ]),
                'options' => ['class' => 'progress-bar-warning'],
              ],
            ],
          ]),
          ['class' => 'flex-fill px-1'],
        ),
        Html::img($am->getAssetUrl($bundle, sprintf('%s/%d.png', $badgePath, $step[3])), [
          'alt' => '',
          'class' => 'basic-icon',
          'draggable' => 'false',
          'style' => '--icon-height:2em',
          'title' => '',
        ]),
      ]),
      ['class' => 'align-items-center d-flex w-100'],
    ),
  ) . "\n" ?>
<?php } ?>
</tr>
