<?php

declare(strict_types=1);

use app\assets\AppAsset;
use app\components\helpers\I18n;
use app\components\widgets\ColorSchemeDialog;
use app\components\widgets\CookieAlert;
use app\components\widgets\LanguageDialog;
use app\components\widgets\TimezoneDialog;
use app\components\helpers\Html;
use yii\helpers\Json;

AppAsset::register($this);
Yii::$app->theme->registerAssets($this);

$_flashes = Yii::$app->getSession()->getAllFlashes();
if ($_flashes) {
  $_hashKey = microtime(false);
  foreach ($_flashes as $_key => $_messages) {
    if (is_array($_messages)) {
      $i = 0;
      foreach ($_messages as $_message) {
        $this->registerJs(
          sprintf(
            '(function($){$.notify(%s)})(jQuery);',
            Json::encode([
              'message' => Html::encode($_message),
              'type' => Html::encode($_key),
            ])
          ),
          hash_hmac('md5', $_hashKey, (string)($i++))
        );
      }
    } else {
      $this->registerJs(
        sprintf(
          '(function($){$.notify(%s,%s)})(jQuery);',
          Json::encode([
            'message' => Html::encode($_messages),
          ]),
          Json::encode([
            'type' => Html::encode($_key),
            'z_index' => 11031,
          ])
        )
      );
    }
  }
}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<?= Html::beginTag('html', [
  'lang' => preg_replace('/@.+$/', '', Yii::$app->language),
  'data' => [
    'timezone' => (string)Yii::$app->timeZone,
    'calendar' => (string)Yii::$app->localeCalendar,
  ],
]) . "\n" ?>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no,email=no,address=no">
    <?= Html::csrfMetaTags() ?>
    <?= Html::tag(
      'title',
      Html::encode(trim((string)$this->title) === '' ? Yii::$app->name : $this->title)
    ) . "\n" ?>
    <?= I18n::languageLinkTags() ?>
    <?php $this->head(); echo "\n" ?>
  </head>
  <?= Html::beginTag('body', [
    'itemprop' => true,
    'proptype' => 'http://schema.org/WebPage',
    'data' => [
      'theme' => Yii::$app->theme->theme,
    ],
    'class' => [
      Yii::$app->theme->isDarkTheme ? 'theme-dark' : 'theme-light',
    ],
  ]) . "\n" ?>
    <?php $this->beginBody() ?><?= "\n" ?>
      <header>
        <?= $this->render('/layouts/testsite') . "\n" ?>
        <?= $this->render('/layouts/ie') . "\n" ?>
        <?= $this->render('/layouts/navbar') . "\n" ?>
      </header>
      <main>
        <?= $content ?><?= "\n" ?>
      </main>
      <?= $this->render('/layouts/footer') ?><?= "\n" ?>
<?php if (!Yii::$app->user->isGuest) { ?>
        <?= $this->render('/includes/battle-input-modal-2') . "\n" ?>
<?php } ?>
      <span id="event"></span>
      <?= ColorSchemeDialog::widget([
        'id' => 'color-scheme-dialog',
      ]) . "\n" ?>
      <?= LanguageDialog::widget([
        'id' => 'language-dialog',
      ]) . "\n" ?>
      <?= TimezoneDialog::widget([
        'id' => 'timezone-dialog',
      ]) . "\n" ?>
      <?= CookieAlert::widget() . "\n" ?>
    <?php $this->endBody() ?><?= "\n" ?>
  </body>
</html>
<?php $this->endPage() ?>
