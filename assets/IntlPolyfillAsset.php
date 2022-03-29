<?php

/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\assets;

use Yii;
use app\components\web\Application;
use yii\web\AssetBundle;

final class IntlPolyfillAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [];

    public function init()
    {
        parent::init();

        $features = [];
        $features[] = 'Intl.~locale.en-US';
        $features[] = 'Intl.~locale.en';

        if (($app = Yii::$app) instanceof Application) {
            $features[] = 'Intl.~locale.' . $app->getLocale();
        }

        $this->js[] = 'https://cdn.polyfill.io/v2/polyfill.min.js?' . http_build_query(
            ['features' => implode(',', $features)],
            '',
            '&'
        );
    }
}
