<?php

/**
 * @copyright Copyright (C) 2015-2020 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\api\internal;

use Yii;
use app\components\helpers\CombinedBattles;
use app\models\User;
use yii\db\Transaction;
use yii\helpers\Url;

class MyLatestBattlesAction extends BaseLatestBattlesAction
{
    private const BATTLE_LIMIT = 12;

    protected function isPrecheckOK(): bool
    {
        return !Yii::$app->user->isGuest;
    }

    protected function fetchBattles(): array
    {
        return Yii::$app->db->transaction(
            fn (): array => CombinedBattles::getUserRecentBattles(
                Yii::$app->user->identity,
                static::BATTLE_LIMIT
            ),
            Transaction::REPEATABLE_READ
        );
    }

    public function run()
    {
        $json = parent::run();
        if ($json['battles']) {
            $json['user'] = $this->formatUser(Yii::$app->user->identity);
        }
        return $json;
    }

    private function formatUser(User $user): array
    {
        return [
            'name' => $user->name,
            'url' => Url::to(['show-user/profile', 'screen_name' => $user->screen_name], true),
        ];
    }

    protected function getHeading(): string
    {
        return Yii::t('app', '{name}\'s Battles');
    }
}
