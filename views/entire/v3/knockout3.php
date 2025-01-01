<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\assets\EntireKnockoutAsset;
use app\components\helpers\OgpHelper;
use app\components\widgets\AdWidget;
use app\components\widgets\SnsWidget;
use app\models\Knockout3;
use app\models\Lobby3;
use app\models\Map3;
use app\models\Rule3;
use app\models\Season3;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var Knockout3[] $data
 * @var Knockout3[] $total
 * @var Lobby3 $xMatch
 * @var Season3 $season
 * @var Season3[] $seasons
 * @var View $this
 * @var array<int, Map3> $maps
 * @var array<int, Rule3> $rules
 * @var callable(Season3): string $seasonUrl
 */

$title = Yii::t('app', 'Knockout Rate');
$this->title = $title . ' | ' . Yii::$app->name;

OgpHelper::default($this, title: $this->title);

EntireKnockoutAsset::register($this);

?>
<div class="container">
  <h1>
    <?= Html::encode($title) . "\n" ?>
  </h1>
  <?= AdWidget::widget() . "\n" ?>
  <?= SnsWidget::widget() . "\n" ?>

  <aside>
    <nav>
      <?= $this->render('../knockout/version-tabs', ['version' => 3]) . "\n" ?>
    </nav>
  </aside>

  <div class="mb-3">
    <?= $this->render('includes/season-selector', compact('season', 'seasons', 'seasonUrl')) . "\n" ?>
  </div>

  <?= $this->render('includes/aggregate', compact('xMatch')) . "\n" ?>

  <div class="alert alert-info mb-3">
    <?= Html::encode(
      Yii::t('app', 'The width of the histogram bins is automatically adjusted by Scott\'s rule-based algorithm.'),
    ) . "\n" ?>
  </div>

  <?= $this->render('knockout3/table', compact('data', 'maps', 'rules', 'season', 'total')) . "\n" ?>
</div>
