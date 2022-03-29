<?php

declare(strict_types=1);

use app\assets\DownloadsPageAsset;
use app\components\helpers\Html;
use app\components\widgets\FA;
use app\components\widgets\FlagIcon;
use app\models\Language;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 * @var string $route
 */

DownloadsPageAsset::register($this);

// @phpstan-ignore-next-line
$langs = Language::find()
  ->standard()
  ->with('languageCharsets')
  ->orderBy(['name' => SORT_ASC])
  ->asArray()
  ->all();
?>
<ul class="dl-langs">
<?php foreach ($langs as $lang) { ?>
<?php if ($lang['languageCharsets']) { ?>
  <li>
    <?= Html::tag(
      'span',
      implode(' ', [
        (string)FlagIcon::fg(strtolower(substr($lang['lang'], 3, 2))),
        Html::encode($lang['name']),
      ]),
      ['class' => 'lang']
    ) . "\n" ?>
    <span class="charsets">
<?php foreach ($lang['languageCharsets'] as $_charset) { ?>
<?php $charset = $_charset['charset'] ?>
      <span class="charset">
        <?= Html::a(
          trim(implode(' ', [
            $_charset['is_win_acp']
              ? (string)FA::fab('windows')
              : '',
            Html::encode($charset['name']),
          ])),
          [$route,
            'lang' => $lang['lang'], 
            'charset' => $charset['php_name'],
          ],
          [
            'hreflang' => $lang['lang'],
            'rel' => 'nofollow',
          ]
        ) . "\n" ?>
      </span>
<?php if ($charset['name'] === 'UTF-8') { ?>
        <span class="charset">
          <?= Html::a(
            Html::encode($charset['name']) . '(BOM)',
            [$route,
              'lang' => $lang['lang'],
              'charset' => $charset['php_name'],
              'bom' => 1,
            ],
            [
              'hreflang' => $lang['lang'],
              'rel' => 'nofollow',
            ]
          ) . "\n" ?>
        </span>
<?php } elseif ($charset['name'] === 'UTF-16LE') { ?>
        <span class="charset">
          <?= Html::a(
            Html::encode($charset['name']) . '(TSV)',
            [$route,
              'lang' => $lang['lang'],
              'charset' => $charset['php_name'],
              'tsv' => 1,
            ],
            [
              'hreflang' => $lang['lang'],
              'rel' => 'nofollow',
            ]
          ) . "\n" ?>
        </span>
<?php } ?>
<?php } ?>
    </span>
  </li>
<?php } ?>
<?php } ?>
</ul>
