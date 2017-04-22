<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\models\api\internal;

use Yii;
use app\models\OstatusPubsubhubbub;
use app\models\User;
use yii\base\Model;

class PubsubhubbubForm extends Model
{
    public $callback;
    public $mode;
    public $topic;
    public $lease_seconds;
    public $secret;
    public $screen_name;

    public function rules()
    {
        return [
            [['callback', 'mode', 'topic'], 'required'],
            [['callback', 'topic'], 'url', 'enableIDN' => true],
            [['mode'], 'in', 'range' => ['subscribe', 'unsubscribe']],
            [['topic'], 'validateTopic'],
            [['lease_seconds'], 'integer', 'min' => 1],
            [['secret'], 'string', 'length' => [0, 200]],
        ];
    }

    public function validateTopic($attribute, $params)
    {
        if ($this->hasErrors($attribute)) {
            return;
        }
        if ($this->getUser()) {
            return;
        }
        $this->addError('topic', 'Invalid topic URL');
    }

    public function save() : bool
    {
        if ($this->hasErrors()) {
            return false;
        }
        return $this->mode === 'subscribe'
            ? $this->saveSubscribe()
            : $this->saveUnsubscribe();
    }

    private function saveSubscribe() : bool
    {
        $model = OstatusPubsubhubbub::find()
            ->andWhere([
                'topic' => $this->getUser()->id,
                'callback' => $this->callback,
            ])
            ->one();
        if (!$model) {
            $model = Yii::createObject(OstatusPubsubhubbub::class);
        }
        $model->topic = $this->getUser()->id;
        $model->callback = $this->callback;
        $model->lease_until = $this->lease_seconds > 0
            ? gmdate('Y-m-d\TH:i:sP', $_SERVER['REQUEST_TIME'] + $this->lease_seconds)
            : null;
        $model->secret = trim($this->secret) === '' ? null : trim($this->secret);
        return !!$model->save();
    }

    private function saveUnsubscribe() : bool
    {
        $model = OstatusPubsubhubbub::find()
            ->andWhere([
                'topic' => $this->getUser()->id,
                'callback' => $this->callback,
            ])
            ->one();
        if ($model) {
            $model->delete();
        }
        return true;
    }

    private function getUser() : ?User
    {
        if ($this->screen_name === false) {
            return null;
        }
        if ($this->screen_name === null) {
            if (!preg_match('!/u/([0-9a-zA-Z_]+)!', $this->topic, $match)) {
                $this->screen_name = false;
                return null;
            }
            $this->screen_name = $match[1];
        }
        return User::findOne(['screen_name' => $this->screen_name]);
    }
}
