<?php

declare(strict_types=1);

use app\assets\SimpleBattleListAsset;
use app\components\helpers\Html;
use app\components\helpers\T;
use app\components\i18n\Formatter;
use app\components\widgets\FA;
use app\models\Salmon2;
use app\models\User;
use app\models\query\Salmon2Query;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\ListView;

/**
 * @var ActiveDataProvider $dataProvider
 * @var User $user
 * @var View $this
 */

SimpleBattleListAsset::register($this);

?>
<div class="text-center">
  <?= ListView::widget([
    'dataProvider' => $dataProvider,
    'itemOptions' => [ 'tag' => false ],
    'layout' => '{pager}',
    'pager' => [
      'maxButtonCount' => 5
    ],
  ]) . "\n" ?>
</div>
<?= $this->render('_summary', [
    'summary' => T::is(Salmon2Query::class, $dataProvider->query)->summary(),
]) . "\n" ?>
<p>
  <?= Html::a(
    implode(' ', [
      (string)FA::fas('list')->fw(),
      Html::encode(Yii::t('app', 'Detailed List')),
    ]),
    array_merge(
      [], // $filter->toQueryParams(),
      ['salmon/index',
        'screen_name' => $user->screen_name,
        'v' => 'standard',
      ]
    ),
    ['class' => 'btn btn-default', 'rel' => 'nofollow']
  ) . "\n" ?>
</p>
<div id="battles">
  <?= ListView::widget([
    'dataProvider' => $dataProvider,
    'layout' => '{items}',
    'options' => [
      'tag' => 'ul',
      'class' => 'list-view simple-battle-list',
    ],
    'itemOptions' => [
      'tag' => 'li',
      'class' => 'simple-battle-row',
    ],
    'itemView' => '_list_sp_item',
    'viewParams' => [
      'user' => $user,
      'formatter' => Yii::createObject([
        'class' => Formatter::class,
        'nullDisplay' => '?',
      ]),
    ],
  ]) . "\n" ?>
</div>
<div class="text-center">
  <?= ListView::widget([
    'dataProvider' => $dataProvider,
    'itemOptions' => [ 'tag' => false ],
    'layout' => '{pager}',
    'pager' => [
      'maxButtonCount' => 5
    ],
  ]) . "\n" ?>
</div>
