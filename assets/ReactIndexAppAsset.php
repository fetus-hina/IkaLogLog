<?php

/**
 * @copyright Copyright (C) 2015-2020 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class ReactIndexAppAsset extends AssetBundle
{
    public $sourcePath = '@app/resources/.compiled/react';
    public $js = [
        'index-app.js',
    ];
}
