<?php

/**
 * @copyright Copyright (C) 2022-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\formatters\api\v3;

use yii\base\Model;
use yii\helpers\Url;

use function vsprintf;

final class ImageApiFormatter
{
    public static function toJson(?Model $model): ?string
    {
        return $model && isset($model->filename)
            ? Url::to(
                vsprintf('@imageurl/%s', [
                    $model->filename,
                ]),
                true,
            )
            : null;
    }
}
