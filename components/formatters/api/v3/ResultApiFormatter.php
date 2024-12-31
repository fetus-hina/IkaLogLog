<?php

/**
 * @copyright Copyright (C) 2022-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\formatters\api\v3;

use app\models\Result3;

final class ResultApiFormatter
{
    public static function toJson(?Result3 $model): ?string
    {
        return $model ? $model->key : null;
    }
}
