<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\assets\NotoSansMathAsset;
use app\components\helpers\OgpHelper;
use app\components\widgets\AdWidget;
use app\components\widgets\SnsWidget;
use app\models\Rule3;
use app\models\Season3;
use app\models\StatXPowerDistribAbstract3;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var Season3 $season
 * @var Season3[] $seasons
 * @var View $this
 * @var array<int, Rule3> $rules
 * @var callable(Season3): string $seasonUrl
 */

$title = Yii::t('app', 'X Power');
$this->title = $title . ' | ' . Yii::$app->name;

OgpHelper::default($this, title: $title);

/**
 * @var array<int, StatXPowerDistribAbstract3> $abstracts
 */
$abstracts = ArrayHelper::map(
  StatXPowerDistribAbstract3::find()
    ->andWhere(['season_id' => $season->id])
    ->all(),
  'rule_id',
  fn (StatXPowerDistribAbstract3 $v): StatXPowerDistribAbstract3 => $v,
);

$fmt = Yii::$app->formatter;

?>
<div class="container">
  <h1>
    <?= Html::encode($title) . "\n" ?>
  </h1>
  <?= AdWidget::widget() . "\n" ?>
  <?= SnsWidget::widget() . "\n" ?>

  <div class="alert alert-danger mb-3">
    <?= Html::encode(
      Yii::t('app', 'This data is based on {siteName} users and differs significantly from overall game statistics.', [
        'siteName' => Yii::$app->name,
      ]),
    ) . "\n" ?>
  </div>

  <div class="alert alert-info mb-3">
    <?= Html::encode(
      Yii::t('app', 'The width of the histogram bins is automatically adjusted by Scott\'s rule-based algorithm.'),
    ) . "\n" ?>
  </div>

  <div class="mb-3">
    <?= $this->render('includes/season-selector', compact('season', 'seasons', 'seasonUrl')) . "\n" ?>
  </div>
  <?= $this->render('includes/rule-link', compact('rules')) . "\n" ?>

  <?= $this->render(
    'xpower-distrib3/integrated',
    compact('abstracts', 'rules'),
  ) . "\n" ?>

<?php foreach ($rules as $ruleId => $rule) { ?>
  <?= $this->render(
    'xpower-distrib3/rule',
    array_merge(
      compact('rule', 'season'),
      [
        'abstract' => $abstracts[$ruleId] ?? null,
      ],
    ),
  ) . "\n" ?>
<?php } ?>
</div>
