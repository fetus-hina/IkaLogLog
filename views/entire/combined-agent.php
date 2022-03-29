<?php
declare(strict_types=1);

use Base32\Base32;
use app\assets\EntireAgentAsset;
use app\components\widgets\AdWidget;
use app\components\widgets\SnsWidget;
use app\components\helpers\Html;
use yii\helpers\Json;

$title = sprintf(
  '%s - %s %s',
  Yii::t('app', 'Battles and Users'),
  $name,
  Yii::t('app', '(combined)')
);
$this->title = implode(' | ', [
  Yii::$app->name,
  $title,
]);

$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:site', 'content' => '@stat_ink']);

EntireAgentAsset::register($this);

$this->registerCss('#graph{height:300px}');
?>
<div class="container">
  <h1><?= Html::encode($title) ?></h1>

  <?= AdWidget::widget() . "\n" ?>
  <?= SnsWidget::widget() . "\n" ?>

  <p>
    <?= Html::a(
      implode('', [
        Html::tag('span', '', ['class' => 'fas fa-fw fa-angle-double-left']),
        Html::encode(Yii::t('app', 'Back')),
      ]),
      ['entire/users'],
      ['class' => 'btn btn-default']
    ) . "\n" ?>
  </p>

  <ul>
<?php foreach ($group->agentGroupMaps as $_): ?>
    <li>
      <?= Html::a(
        Html::encode($_->agent_name),
        ['entire/agent',
          'b32name' => strtolower(rtrim(Base32::encode($_->agent_name), '=')),
        ]
      ) . "\n" ?>
    </li>
<?php endforeach ?>
  </ul>

  <?= Html::tag('div', '', [
    'id' => 'graph',
    'data' => [
      'data' => Json::encode($posts),
      'label-battle' => Yii::t('app', 'Battles'),
      'label-user' => Yii::t('app', 'Users'),
    ],
  ]) . "\n" ?>
</div>
