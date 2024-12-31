<?php

/**
 * @copyright Copyright (C) 2023-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\assets\BattleListGroupHeaderAsset;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var string $label
 */

BattleListGroupHeaderAsset::register($this);

?>
<tr>
  <td class="battle-row-group-header" colspan="3">
    <?= Html::encode($label) . "\n" ?>
  </td>
</tr>
