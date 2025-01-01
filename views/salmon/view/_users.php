<?php

/**
 * @copyright Copyright (C) 2018-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\components\i18n\Formatter;
use app\components\widgets\SalmonPlayers;
use app\models\Salmon2;
use yii\web\View;

/**
 * @var Salmon2 $model
 * @var View $this
 */

$players = $model->players;
if (!$players) {
  return;
}

?>
<h2><?= Yii::t('app', 'Players') ?></h2>
<?= SalmonPlayers::widget([
  'work' => $model,
  'players' => $players,
  'formatter' => Yii::createObject([
    'class' => Formatter::class,
    'nullDisplay' => '',
  ]),
]) ?>
