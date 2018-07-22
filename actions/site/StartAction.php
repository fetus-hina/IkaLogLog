<?php
/**
 * @copyright Copyright (C) 2016 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\actions\site;

use Yii;
use yii\web\ViewAction as BaseAction;

class StartAction extends BaseAction
{
    public function run()
    {
        switch (strtolower(Yii::$app->language)) {
           case 'ja-jp':
               return $this->controller->render('start.ja.php');

           default:
               return $this->controller->render('start.en.tpl');
       }
    }
}
