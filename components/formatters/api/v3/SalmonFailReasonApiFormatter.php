<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\formatters\api\v3;

use app\models\SalmonFailReason2;

final class SalmonFailReasonApiFormatter
{
    public static function toJson(?SalmonFailReason2 $model, bool $fullTranslate = false): ?array
    {
        if (!$model) {
            return null;
        }

        return [
            'key' => $model->key,
            'name' => NameApiFormatter::toJson($model->name, 'app-salmon3', $fullTranslate),
        ];
    }
}
