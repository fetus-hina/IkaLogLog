<?php

declare(strict_types=1);

use app\assets\InlineListAsset;
use app\assets\PaintballAsset;
use app\assets\ReactCounterAppAsset;
use app\assets\ReactIndexAppAsset;
use app\components\helpers\OgpHelper;
use app\components\widgets\HappyNewYearWidget;
use app\components\widgets\Icon;
use app\components\widgets\IndexI18nButtons;
use app\components\widgets\SnsWidget;
use app\components\widgets\alerts\ImportFromSplatnet;
use app\components\widgets\alerts\LanguageSupportLevelWarning;
use app\components\widgets\alerts\MaintenanceInfo;
use app\components\widgets\alerts\PleaseUseLatest;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\View;

/**
 * @var View $this
 */

assert($this->context instanceof Controller);
$this->context->layout = 'main';

PaintballAsset::register($this);

$discordInviteCode = ArrayHelper::getValue(Yii::$app->params, 'discordInviteCode');

OgpHelper::default($this, Url::to(['site/index'], true));

?>
<div class="container">
  <div class="text-right">
    <?= IndexI18nButtons::widget() . "\n" ?>
  </div>

  <div class="row">
    <div class="col-xs-12 col-sm-6 col-md-8">
      <h1 class="paintball mb-3" style="font-size:42px;margin-top:0">
        <?= Html::encode(Yii::$app->name) . "\n" ?>
      </h1>
      <p class="mb-3">
        <?= Html::encode(Yii::t('app', 'Keep doing it.')) . "\n" ?>
      </p>
    </div>
    <div class="col-12 col-xs-12 col-sm-6 col-md-4">
      <?php ReactCounterAppAsset::register($this); echo "\n" ?>
      <div id="counter-app" class="mb-3"></div>
    </div>
  </div>
  <?= HappyNewYearWidget::widget() . "\n" ?>
  <?= MaintenanceInfo::widget() . "\n" ?>
  <?= LanguageSupportLevelWarning::widget() . "\n" ?>

<?php InlineListAsset::register($this) ?>
  <nav class="mb-3"><?= implode('', array_map(
    function (array $line): string {
      return Html::tag(
        'ul',
        implode('', array_map(
          function (string $html): string {
            return Html::tag('li', $html);
          },
          $line
        )),
        ['class' => 'inline-list mb-1']
      );
    },
    [
      array_filter([
        Yii::$app->user->isGuest
          ? Html::a(Html::encode(Yii::t('app', 'Join us')), ['user/register'])
          : Html::a(Html::encode(Yii::t('app', 'Your Battles')), ['show-user/profile',
            'screen_name' => Yii::$app->user->identity->screen_name,
          ]),
        Html::a(Html::encode(Yii::t('app', 'Getting Started')), ['site/start']),
        Html::a(Html::encode(Yii::t('app', 'FAQ')), ['site/faq']),
        is_string($discordInviteCode) && $discordInviteCode
          ? Html::a(
            implode(' ', [
              Icon::discord(),
              Html::encode('Discord'),
            ]),
            sprintf('https://discord.gg/%s', rawurlencode($discordInviteCode)),
            [
              'class' => 'auto-tooltip',
              'rel' => 'nofollow noopener',
              'target' => '_blank',
              'title' => Yii::t('app', '{siteName} Discord Community', ['siteName' => Yii::$app->name]),
            ],
          )
          : null,
        Html::a(Html::encode(Yii::t('app', 'Stats: User Activity')), ['entire/users']),
      ]),
      [
        Icon::splatoon3(),
        Html::a(Html::encode(Yii::t('app', 'Weapon Stats')), ['entire/weapons3']),
        Html::a(Html::encode(Yii::t('app', 'K/D vs Win %')), ['entire/kd-win3']),
        Html::a(Html::encode(Yii::t('app', 'Knockout Rate')), ['entire/knockout3']),
        Html::a(Html::encode(Yii::t('app', 'Special Uses')), ['entire/special-use3']),
        Html::a(
          Icon::s3LobbyX() . ' ' . Html::encode(Yii::t('app', 'X Power')),
          ['entire/xpower-distrib3'],
        ),
        Html::a(
          Icon::s3AbilityStealthJump() . ' ' . Html::encode(Yii::t('app', 'Stealth Jump Equipment Rate')),
          ['entire/stealth-jump3'],
        ),
        Html::a(Html::encode(Yii::t('app', 'Ink Color')), ['entire/ink-color3']),
      ],
      [
        Icon::splatoon3() . ' ' . Icon::s3LobbyEvent(),
        Html::a(
          Html::encode(Yii::t('app', 'Weapon Stats')),
          ['entire/event3'],
        ),
      ],
      [
        Icon::splatoon3() . ' ' . Icon::s3LobbySplatfest(),
        Html::a(
          Html::encode(Yii::t('app', 'Splatfest Stats')),
          ['entire/splatfest3'],
        ),
      ],
      [
        implode(' ', [
          Icon::splatoon3(),
          Icon::s3Salmon(),
        ]),
        Html::a(
          Icon::s3SalmonRandomRandom() . ' ' . Html::encode(Yii::t('app-salmon3', 'Random Loan Rate')),
          ['entire/salmon3-random-loan'],
        ),
        Html::a(
          Html::encode(Yii::t('app-salmon3', 'Water Level and Events')),
          ['entire/salmon3-tide'],
        ),
        Html::a(
          implode(' ', [
            Icon::s3BossSalmonid('yokozuna', alt: Yii::t('app-salmon', 'King Salmonid')),
            Html::encode(Yii::t('app-salmon3', 'King Salmonid Defeat Rate')),
          ]),
          ['entire/salmon3-king-salmonid'],
        ),
        Html::a(
          Icon::s3Salmometer() . ' ' . Html::encode(Yii::t('app-salmon3', 'Salmometer')),
          ['entire/salmon3-salmometer'],
        ),
        Html::a(
          Icon::s3BigRun() . ' ' . Html::encode(Yii::t('app-salmon3', 'Big Run')),
          ['entire/salmon3-bigrun'],
        ),
        Html::a(
          Icon::s3Eggstra() . ' ' . Html::encode(Yii::t('app-salmon3', 'Eggstra Work')),
          ['entire/salmon3-eggstra-work'],
        ),
      ],
      [
        Icon::stats(),
        Html::a(
          Icon::splatoon2() . ' ' . Html::encode(Yii::t('app', 'Stats: FestPwr diff vs Win %')),
          ['entire/festpower2'],
        ),
        Html::a(
          Icon::splatoon2() . ' ' . Html::encode(Yii::t('app-salmon2', 'Stats: Salmon Clear %')),
          ['entire/salmon-clear'],
        ),
        Html::a(
          Icon::splatoon1() . ' ' . Html::encode(Yii::t('app', 'Stats: Stages')),
          ['stage/index'],
        ),
      ],
      [
        Icon::download(),
        Html::a(
          implode('', [
            Icon::splatoon3(),
            Icon::splatoon2(),
            Icon::splatoon1(),
            ' ',
            Html::encode(Yii::t('app', 'Download Stats')),
          ]),
          ['download-stats/index'],
        ),
      ],
      [
        Html::a(Html::encode(Yii::t('app', 'About support for color-blindness')), ['site/color']),
        Html::a(
          Icon::splatoon1() . ' ' . Html::encode(Yii::t('app-privacy', 'About image sharing with the IkaLog team')),
          ['site/privacy'],
        ),
      ],
    ]
  )) ?></nav>
  <?= SnsWidget::widget() . "\n" ?>

  <?php ReactIndexAppAsset::register($this); ?>
  <div id="index-app"></div>
</div>
