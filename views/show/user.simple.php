<?php

use app\components\helpers\Html;
use app\components\widgets\AdWidget;
use app\components\widgets\BattleFilterWidget;
use app\components\widgets\SnsWidget;
use app\models\BattleFilterForm;
use app\models\BattleSummary;
use app\models\Language;
use app\models\User;
use yii\bootstrap\ActiveForm;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;

/**
 * @var ActiveDataProvider $battleDataProvider
 * @var BattleFilterForm $filter
 * @var BattleSummary $summary
 * @var User $user
 * @var View $this
 * @var string $permLink
 */

$title = Yii::t('app', '{name}\'s Splat Log', ['name' => $user->name]);
$this->title = implode(' | ', [
  Yii::$app->name,
  $title,
]);

$this->registerLinkTag(['rel' => 'canonical', 'href' => $permLink]);
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:url', 'content' => $permLink]);
$this->registerMetaTag(['name' => 'twitter:site', 'content' => '@stat_ink']);
$this->registerMetaTag(['name' => 'twitter:image', 'content' => $user->iconUrl]);

if ($user->twitter != '') {
  $this->registerMetaTag(['name' => 'twitter:creator', 'content' => '@' . $user->twitter]);
}

// @phpstan-ignore-next-line
$langs = Language::find()->standard()->all();
foreach ($langs as $lang) {
  $this->registerLinkTag([
    'rel' => 'alternate',
    'type' => 'application/rss+xml',
    'title' => sprintf('%s - RSS Feed (%s)', $title, $lang->name),
    'href' => Url::to(
      ['feed/user',
        'screen_name' => $user->screen_name,
        'type' => 'rss',
        'lang' => $lang->lang,
      ],
      true
    ),
    'hreflang' => $lang->lang,
  ]);
  $this->registerLinkTag([
    'rel' => 'alternate',
    'type' => 'application/atom+xml',
    'title' => sprintf('%s - Atom Feed (%s)', $title, $lang->name),
    'href' => Url::to(
      ['feed/user',
        'screen_name' => $user->screen_name,
        'type' => 'atom',
        'lang' => $lang->lang,
      ],
      true
    ),
    'hreflang'  => $lang->lang,
  ]);
}
unset($langs);

$this->registerCss('.simple-battle-list{display:block;list-style-type:none;margin:0;padding:0}');

$battle = $user->getLatestBattle();
$f = Yii::$app->formatter;
?>
<div class="container">
  <h1><?= Html::encode($title) ?></h1>
  
<?php
if ($battle &&
    $battle->agent &&
    $battle->agent->isIkaLog &&
    $battle->agent->getIsOldIkalogAsAtTheTime($battle->at)
) {
?>
<?php $this->registerCss('.old-ikalog{font-weight:bold;color:#f00}') ?>
  <p class="old-ikalog">
    <?= Html::encode(
      Yii::t(
        'app',
        'These battles were recorded with an outdated version of IkaLog. Please upgrade to the latest version.'
      )
    ) . "\n" ?>
  </p>
<?php } ?>

  <?= SnsWidget::widget([
    'feedUrl' => Url::to(
      ['feed/user',
        'screen_name' => $user->screen_name,
        'type' => 'rss',
        'lang' => preg_replace('/@.+$/', '', Yii::$app->language),
      ],
      true
    ),
    'tweetText' => sprintf(
      '%s [ %s ]',
      $title,
      Yii::t(
        'app',
        'Battles:{0} / Win %:{1} / Avg Kills:{2} / Avg Deaths:{3} / Kill Ratio:{4}',
        [
          $f->asInteger($summary->battle_count),
          $summary->wp === null
            ? '-'
            : $f->asPercent($summary->wp / 100, 1),
          $summary->kd_present > 0
            ? $f->asDecimal($summary->total_kill / $summary->kd_present, 2)
            : '-',
          $summary->kd_present > 0
            ? $f->asDecimal($summary->total_death / $summary->kd_present, 2)
            : '-',
          $summary->kd_present > 0
            ? ($summary->total_death > 0
              ? $f->asDecimal($summary->total_kill / $summary->total_death, 2)
              : ($summary->total_kill > 0
                ? '∞'
                : '-'
              )
            )
            : '-',
        ]
      )
    ),
  ]) . "\n" ?>

  <div class="row">
    <div class="col-xs-12 col-sm-8 col-lg-9">
      <div class="text-center">
        <?= ListView::widget([
          'dataProvider' => $battleDataProvider,
          'itemOptions' => [ 'tag' => false ],
          'layout' => '{pager}',
          'pager' => [
            'maxButtonCount' => 5
          ]
        ]) . "\n" ?>
      </div>
      <?= $this->render(
        '//includes/battles-summary',
        [
          'headingText' => Yii::t('app', 'Summary: Based on the current filter'),
          'summary' => $summary
        ]
      ) . "\n" ?>
      <div>
        <a href="#filter-form" class="visible-xs-inline btn btn-info"><span class="fas fa-fw fa-search"></span><?= Html::encode(Yii::t('app', 'Search')) ?></a>
        <?= Html::a(
          '<span class="fas fa-fw fa-list"></span>' . Html::encode(Yii::t('app', 'Detailed List')),
          array_merge($filter->toQueryParams(), ['show/user', 'v' => 'standard']),
          ['class' => 'btn btn-default', 'rel' => 'nofollow']
        ) . "\n" ?>
      </div>
      <div id="battles">
        <ul class="simple-battle-list">
          <?= ListView::widget([
            'dataProvider' => $battleDataProvider,
            'itemView' => '_battle.simple.tablerow.php',
            'itemOptions' => [ 'tag' => false ],
            'layout' => '{items}'
          ]) . "\n" ?>
        </ul>
      </div>
      <div class="text-center">
        <?= ListView::widget([
          'dataProvider' => $battleDataProvider,
          'itemView' => '_battle.simple.tablerow.php',
          'itemOptions' => [ 'tag' => false ],
          'layout' => '{pager}',
          'pager' => [
            'maxButtonCount' => 5
          ]
        ]) . "\n" ?>
      </div>
    </div>
    <div class="col-xs-12 col-sm-4 col-lg-3">
      <?= BattleFilterWidget::widget(['route' => 'show/user', 'screen_name' => $user->screen_name, 'filter' => $filter]) . "\n" ?>
      <?= $this->render("//includes/user-miniinfo", ["user" => $user]) . "\n" ?>
      <?= AdWidget::widget() . "\n" ?>
    </div>
  </div>
</div>
