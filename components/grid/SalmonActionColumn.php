<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\grid;

use Yii;
use app\models\Salmon2;
use app\models\User;
use yii\grid\Column;
use yii\helpers\Html;
use yii\helpers\Url;

class SalmonActionColumn extends Column
{
    public $user;

    protected function renderDataCellContent($model, $key, $index)
    {
        $user = $this->user ?: $model->user;

        return implode(' ', array_filter(
            [
                Html::a(
                    Html::encode(Yii::t('app', 'Detail')),
                    ['salmon/view',
                        'screen_name' => $user->screen_name ?? '_',
                        'id' => $model->id,
                    ],
                    [
                        'class' => 'btn btn-primary btn-xs',
                    ]
                ),
                //TODO: video link
            ],
            function (?string $content): bool {
                return $content !== null && $content !== '';
            }
        ));
    }
}
