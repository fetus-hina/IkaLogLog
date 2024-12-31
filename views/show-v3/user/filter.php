<?php

/**
 * @copyright Copyright (C) 2022-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\assets\BattleListAsset;
use app\components\widgets\AdWidget;
use app\components\widgets\Battle3FilterWidget;
use app\components\widgets\SnsWidget;
use app\components\widgets\UserMiniInfo3;
use app\models\User;
use yii\data\BaseDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var BaseDataProvider $battleDataProvider
 * @var Battle3FilterWidget $filter
 * @var User $user
 * @var View $this
 * @var array $summary
 */

$columns = require __DIR__ . '/list-options/columns.php';

$getClassName = function (array $config): ?string {
  foreach (['contentOptions', 'headerOptions'] as $k) {
    if (
      isset($config[$k]) &&
      is_array($config[$k]) &&
      isset($config[$k]['class'])
    ) {
      $confValue = $config[$k]['class'];
      if (is_string($confValue)) {
        if (preg_match('/\bcell-[\w-]+\b/', $confValue, $match)) {
          return $match[0];
        }
      } elseif (is_array($confValue)) {
        foreach ($confValue as $v) {
          if (preg_match('/\bcell-[\w-]+\b/', $v, $match)) {
            return $match[0];
          }
        }
      }
    }
  }
  return null;
};

$getLabel = function (array $config): ?string {
  if (isset($config['-label'])) {
    return $config['-label'];
  }
  return isset($config['label']) ? $config['label'] : null;
};

foreach ($columns as $column) {
  $class = $getClassName($column);
  if ($class) {
    $label = $getLabel($column);
    if ($label) {
      echo Html::tag(
        'div',
        Html::tag(
          'label',
          vsprintf('%s %s', [
            Html::tag('input', '', [
              'type' => 'checkbox',
              'class' => 'table-config-chk',
              'data-klass' => $class,
            ]),
            Html::encode($label),
          ]),
        ),
        ['class' => 'col-xs-6 col-sm-4 col-lg-3']
      );
    }
  }
}
