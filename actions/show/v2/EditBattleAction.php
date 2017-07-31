<?php
/**
 * @copyright Copyright (C) 2015 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\actions\show\v2;

use Yii;
use app\models\Battle2;
use app\models\Battle2Form;
use app\models\Lobby2;
use app\models\Map2;
use app\models\Mode2;
use app\models\Rank2;
use app\models\Rule2;
use app\models\Weapon2;
use app\models\WeaponCategory2;
use app\models\WeaponType2;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\ViewAction as BaseAction;

class EditBattleAction extends BaseAction
{
    private $battle;

    public function init()
    {
        parent::init();
        $this->battle = null;
        if ($user = Yii::$app->user->identity) {
            $this->battle = Battle2::findOne([
                'id' => Yii::$app->request->get('battle'),
                'user_id' => $user->id,
            ]);
        }
    }

    public function getIsEditable()
    {
        return !!$this->battle;
    }

    public function run()
    {
        // $del = new BattleDeleteForm();
        if (Yii::$app->request->isPost) {
            $form = Yii::createObject(['class' => Battle2Form::class]);
            $form->load($_POST);
            // $del->load($_POST);
            // if (Yii::$app->request->post('_action') === 'delete') {
            //     if ($del->validate()) {
            //         $transaction = Yii::$app->db->beginTransaction();
            //         if ($this->battle->delete()) {
            //             $transaction->commit();
            //             $this->controller->redirect([
            //                 'show/user',
            //                 'screen_name' => $this->battle->user->screen_name,
            //             ]);
            //             return;
            //         }
            //         $transaction->rollback();
            //     }
            // } else {
                if ($form->validate()) {
                    $this->battle->attributes = $form->attributes;
                    if ($this->battle->save()) {
                        $this->controller->redirect([
                            'show-v2/battle',
                            'screen_name' => $this->battle->user->screen_name,
                            'battle' => $this->battle->id,
                        ]);
                        return;
                    }
                }
            // }
        } else {
            $form = Battle2Form::fromBattle($this->battle);
        }
        return $this->controller->render('edit-battle', [
            'user' => $this->battle->user,
            'battle' => $this->battle,
            'form' => $form,
            // 'delete' => $del,
            'maps' => $this->makeMaps(),
            'weapons' => $this->makeWeapons(),
            'ranks' => $this->makeRanks(),
        ]);
    }

    private function makeMaps()
    {
        $ret = [];
        foreach (Map2::find()->all() as $map) {
            $ret[$map->id] = Yii::t('app-map2', $map->name);
        }
        asort($ret);
        return static::arrayMerge(
            ['' => Yii::t('app', 'Unknown')],
            $ret
        );
    }

    private function makeWeapons()
    {
        $ret = [];
        $categories = WeaponCategory2::find()
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($categories as $category) {
            $types = $category->getWeaponTypes()
                ->orderBy(['id' => SORT_ASC])
                ->all();
            foreach ($types as $type) {
                $typeName = ($category->name === $type->name)
                    ? Yii::t('app-weapon2', $category->name)
                    : sprintf(
                        '%s » %s',
                        Yii::t('app-weapon2', $category->name),
                        Yii::t('app-weapon2', $type->name)
                    );
                $ret[$typeName] = (function (WeaponType2 $type) : array {
                    $tmp = [];
                    foreach ($type->weapons as $weapon) {
                        $tmp[$weapon->id] = Yii::t('app-weapon2', $weapon->name);
                    }
                    asort($tmp);
                    return $tmp;
                })($type);
            }
        }
        return static::arrayMerge(
            ['' => Yii::t('app', 'Unknown')],
            $ret
        );
    }

    private function makeRanks()
    {
        return static::arrayMerge(
            ['' => ''],
            ArrayHelper::map(
                Rank2::find()->orderBy(['[[id]]' => SORT_DESC])->asArray()->all(),
                'id',
                function (array $row) : string {
                    return Yii::t('app-rank2', $row['name']);
                }
            )
        );
    }

    private static function arrayMerge()
    {
        $ret = [];
        foreach (func_get_args() as $arg) {
            foreach ($arg as $k => $v) {
                $ret[$k] = $v;
            }
        }
        return $ret;
    }
}
