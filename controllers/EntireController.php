<?php

/**
 * @copyright Copyright (C) 2015-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\controllers;

use app\actions\entire\AgentAction;
use app\actions\entire\CombinedAgentAction;
use app\actions\entire\Festpower2Action;
use app\actions\entire\KDWin2Action;
use app\actions\entire\KDWinAction;
use app\actions\entire\Knockout2Action;
use app\actions\entire\KnockoutAction;
use app\actions\entire\SalmonClearAction;
use app\actions\entire\UsersAction;
use app\actions\entire\Weapon2Action;
use app\actions\entire\WeaponAction;
use app\actions\entire\Weapons2Action;
use app\actions\entire\Weapons2TierAction;
use app\actions\entire\WeaponsAction;
use app\actions\entire\WeaponsUseAction;
use app\actions\entire\salmon3\BigrunAction;
use app\actions\entire\salmon3\EggstraWorkAction;
use app\actions\entire\salmon3\KingSalmonidAction;
use app\actions\entire\salmon3\RandomLoanAction;
use app\actions\entire\salmon3\SalmometerAction;
use app\actions\entire\salmon3\TideAction;
use app\actions\entire\v3\BukichiCup3Action;
use app\actions\entire\v3\Event3Action;
use app\actions\entire\v3\InkColor3Action;
use app\actions\entire\v3\KDWin3Action;
use app\actions\entire\v3\Knockout3Action;
use app\actions\entire\v3\SpecialUse3Action;
use app\actions\entire\v3\SpecialUse3PerSpecialAction;
use app\actions\entire\v3\Splatfest3Action;
use app\actions\entire\v3\StealthJump3Action;
use app\actions\entire\v3\Weapon3Action;
use app\actions\entire\v3\Weapons3Action;
use app\actions\entire\v3\XPowerDistrib3Action;
use app\components\web\Controller;
use yii\filters\VerbFilter;

final class EntireController extends Controller
{
    public $layout = 'main';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    '*' => ['head', 'get'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'agent' => AgentAction::class,
            'bukichi-cup3' => BukichiCup3Action::class,
            'combined-agent' => CombinedAgentAction::class,
            'event3' => Event3Action::class,
            'festpower2' => Festpower2Action::class,
            'ink-color3' => InkColor3Action::class,
            'kd-win' => KDWinAction::class,
            'kd-win2' => KDWin2Action::class,
            'kd-win3' => KDWin3Action::class,
            'knockout' => KnockoutAction::class,
            'knockout2' => Knockout2Action::class,
            'knockout3' => Knockout3Action::class,
            'salmon-clear' => SalmonClearAction::class,
            'salmon3-bigrun' => BigrunAction::class,
            'salmon3-eggstra-work' => EggstraWorkAction::class,
            'salmon3-king-salmonid' => KingSalmonidAction::class,
            'salmon3-random-loan' => RandomLoanAction::class,
            'salmon3-salmometer' => SalmometerAction::class,
            'salmon3-tide' => TideAction::class,
            'special-use3' => SpecialUse3Action::class,
            'special-use3-per-special' => SpecialUse3PerSpecialAction::class,
            'splatfest3' => Splatfest3Action::class,
            'stealth-jump3' => StealthJump3Action::class,
            'users' => UsersAction::class,
            'weapon' => WeaponAction::class,
            'weapon2' => Weapon2Action::class,
            'weapon3' => Weapon3Action::class,
            'weapons' => WeaponsAction::class,
            'weapons-use' => WeaponsUseAction::class,
            'weapons2' => Weapons2Action::class,
            'weapons2-tier' => Weapons2TierAction::class,
            'weapons3' => Weapons3Action::class,
            'xpower-distrib3' => XPowerDistrib3Action::class,
        ];
    }
}
