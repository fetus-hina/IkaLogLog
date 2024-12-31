<?php

/**
 * @copyright Copyright (C) 2022-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\widgets\v3;

use Yii;
use app\components\widgets\Icon;
use app\models\SalmonWeapon3;
use app\models\Weapon3;
use yii\base\Widget;
use yii\helpers\Html;

use function implode;
use function vsprintf;

final class WeaponName extends Widget
{
    public Weapon3|SalmonWeapon3|null $model = null;
    public bool $showName = true;
    public bool $subInfo = true;

    public function run(): string
    {
        $model = $this->model;
        if (!$model) {
            return '';
        }

        return $this->subInfo
            ? vsprintf('%s (%s)', [
                $this->renderMainWeapon($model),
                $this->renderSubSp($model),
            ])
            : $this->renderMainWeapon($model);
    }

    private function renderMainWeapon(Weapon3|SalmonWeapon3 $model): string
    {
        return implode(' ', [
            Icon::s3Weapon($model),
            Html::encode(Yii::t('app-weapon3', $model->name)),
        ]);
    }

    private function renderSubSp(Weapon3|SalmonWeapon3 $model): string
    {
        if (!$this->subInfo || !$model instanceof Weapon3) {
            return '';
        }

        $sub = $model->subweapon;
        $sp = $model->special;
        if (!$sub || !$sp) {
            return '';
        }

        return vsprintf('%3$s %1$s / %4$s %2$s', [
            Html::encode(Yii::t('app-subweapon3', $sub->name)),
            Html::encode(Yii::t('app-special3', $sp->name)),
            Icon::s3Subweapon($sub),
            Icon::s3Special($sp),
        ]);
    }
}
