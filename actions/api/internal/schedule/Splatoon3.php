<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\api\internal\schedule;

use function array_merge;

trait Splatoon3
{
    use Battle3;
    use Salmon3;

    protected function getSplatoon3(): array
    {
        return array_merge(
            $this->getBattle3(),
            $this->getSalmon3(),
        );
    }
}
