<?php

/**
 * @copyright Copyright (C) 2015-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\commands;

use app\models\Battle;
use app\models\Slack;
use yii\console\Controller;
use yii\helpers\Console;

use function escapeshellarg;
use function printf;

class BattleController extends Controller
{
    public function actionDelete($id)
    {
        $battle = Battle::findOne(['id' => (int)(string)$id]);
        if (!$battle) {
            $this->stderr("Could not find specified battle \"{$id}\"\n", Console::FG_RED);
            return 1;
        }

        $battle->delete();
    }

    public function actionTestSlack($id)
    {
        $battle = Battle::findOne(['id' => (int)(string)$id]);
        if (!$battle) {
            $this->stderr("Could not find specified battle \"{$id}\"\n", Console::FG_RED);
            return 1;
        }

        $list = Slack::find()
            ->andWhere([
                'user_id' => $battle->user->id,
                'suspended' => false,
            ])
            ->orderBy('id')
            ->all();
        foreach ($list as $slack) {
            printf(
                "curl -v -H %s -X POST -d %s %s\n\n",
                escapeshellarg('Content-Type: application/json'),
                escapeshellarg($slack->send($battle, false)),
                escapeshellarg($slack->webhook_url),
            );
        }
    }
}
