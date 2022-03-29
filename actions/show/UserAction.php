<?php

/**
 * @copyright Copyright (C) 2015 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\show;

use Yii;
use app\components\helpers\T;
use app\models\Battle;
use app\models\BattleFilterForm;
use app\models\User;
use app\models\query\BattleQuery;
use yii\base\Action;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class UserAction extends Action
{
    /**
     * @return Response|string
     */
    public function run()
    {
        $request = Yii::$app->getRequest();
        $user = User::findOne(['screen_name' => $request->get('screen_name')]);
        if (!$user) {
            throw new NotFoundHttpException(Yii::t('app', 'Could not find user'));
        }

        // リスト表示モード切替
        if ($request->get('v') != '') {
            $view = $request->get('v');
            if ($view === 'simple' || $view === 'standard') {
                Yii::$app->response->cookies->add(
                    new Cookie([
                        'name' => 'battle-list',
                        'value' => $view,
                        'expire' => time() + 86400 * 366,
                    ])
                );
            }

            $next = $_GET;
            unset($next['v']);
            $next[0] = 'show/user';
            return T::webController($this->controller)
                ->redirect(Url::to($next));
        }

        $battle = Battle::find()
            ->with([
                'bonus',
                'lobby',
                'map',
                'rank',
                'rankAfter',
                'rule',
                'rule.mode',
                'weapon',
                'weapon.special',
                'weapon.subweapon',
            ]);

        $filter = new BattleFilterForm();
        $filter->load($_GET);
        $filter->screen_name = $user->screen_name;
        if ($filter->validate()) {
            // @phpstan-ignore-next-line
            $battle->filter($filter);
        }
        $summary = T::is(BattleQuery::class, $battle)->getSummary();

        $permLink = Url::to(
            array_merge(
                ['show/user', 'screen_name' => $user->screen_name],
                $filter->hasErrors() ? [] : $filter->toPermLink()
            ),
            true
        );

        $template = $this->getViewMode() === 'simple' ? 'user.simple.php' : 'user.php';
        return $this->controller->render($template, [
            'user' => $user,
            'battleDataProvider' => new ActiveDataProvider([
                'query' => $battle,
                'pagination' => [
                    'pageSize' => 100,
                ],
            ]),
            'summary' => $summary,
            'filter' => $filter,
            'permLink' => $permLink,
        ]);
    }

    public function getViewMode(): string
    {
        $request = Yii::$app->request;
        $mode = null;
        if ($cookie = $request->cookies->get('battle-list')) {
            $mode = $cookie->value;
        }

        if ($mode === 'simple' || $mode === 'standard') {
            return $mode;
        }

        $ua = $request->userAgent;
        if (
            strpos($ua, 'iPhone') !== false ||
            strpos($ua, 'Android') !== false ||
            strpos($ua, 'Windows Phone') !== false ||
            strpos($ua, 'iPod') !== false
        ) {
            return 'simple';
        }

        return 'standard';
    }
}
