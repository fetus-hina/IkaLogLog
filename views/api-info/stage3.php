<?php

declare(strict_types=1);

use app\components\widgets\AdWidget;
use app\components\widgets\CcBy;
use app\components\widgets\Icon;
use app\components\widgets\SnsWidget;
use app\models\BigrunMap3;
use app\models\Map3;
use app\models\SalmonMap3;
use yii\bootstrap\Html;
use yii\web\View;

/**
 * @var BigrunMap3[] $bigrunStages
 * @var Map3[] $stages
 * @var SalmonMap3[] $salmonStages
 * @var View $this
 * @var array[] $langs
 */

$this->context->layout = 'main';
$this->title = Yii::t('app', 'API Info: Stages (Splatoon 3)');

$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $this->title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $this->title]);
$this->registerMetaTag(['name' => 'twitter:site', 'content' => '@stat_ink']);

?>
<div class="container">
  <h1>
    <?= Html::encode($this->title) . "\n" ?>
  </h1>
  <?= AdWidget::widget() . "\n" ?>
  <?= SnsWidget::widget() . "\n" ?>
  <p>
    <?= implode(' ', [
      Html::a(
        implode(' ', [
          Icon::apiJson(),
          Html::encode(Yii::t('app', 'JSON format')),
        ]),
        ['api-v3/stage'],
        ['class' => 'label label-default']
      ),
      Html::a(
        implode(' ', [
          Icon::apiJson(),
          Html::encode(Yii::t('app', 'JSON format (All langs)')),
        ]),
        ['api-v3/stage', 'full' => 1],
        ['class' => 'label label-default']
      ),
    ]) . "\n" ?>
  </p>
  <?= $this->render('stage3/list', ['langs' => $langs, 'stages' => $stages]) . "\n" ?>
  <?= $this->render('stage3/salmon', ['langs' => $langs, 'stages' => $salmonStages]) . "\n" ?>
  <?= $this->render('stage3/bigrun', ['langs' => $langs, 'stages' => $bigrunStages]) . "\n" ?>
  <hr>
  <?= CcBy::widget() . "\n" ?>
</div>
