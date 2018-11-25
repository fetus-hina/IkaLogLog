<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\actions\api\internal;

use DateTime;
use DateTimeImmutable;
use Yii;
use app\components\helpers\Battle as BattleHelper;
use app\models\User;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class ActivityAction extends Action
{
    public $resp;

    public function init()
    {
        parent::init();

        Yii::$app->timeZone = 'Etc/UTC';
        Yii::$app->db->setTimezone('Etc/UTC');
        $this->resp = Yii::$app->response;
        $this->resp->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        $form = $this->getInputPseudoForm();
        if ($form->hasErrors()) {
            $this->resp->statusCode = 400;
            $this->resp->data = $form->getErrors();
            return;
        }

        $user = User::findOne(['screen_name' => $form->screen_name]);
        list($from, $to) = BattleHelper::getActivityDisplayRange();
        $this->resp->data = $this->makeData($user, $from, $to);
    }

    private function getInputPseudoForm(): DynamicModel
    {
        $time = time();
        return DynamicModel::validateData(Yii::$app->request->get(), [
            [['screen_name'], 'required'],
            [['screen_name'], 'string'],
            [['screen_name'], 'exist', 'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => 'screen_name',
            ],
        ]);
    }

    private function makeData(User $user, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->reformatData($this->mergeData([
            $this->makeDataSplatoon1Battle($user, $from, $to),
            $this->makeDataSplatoon2Battle($user, $from, $to),
            $this->makeDataSplatoon2Salmon($user, $from, $to),
        ]));
    }

    private function reformatData(array $inData): array
    {
        return array_map(
            function (string $date, int $count): array {
                return [
                    'date' => $date,
                    'count' => $count,
                ];
            },
            array_keys($inData),
            array_values($inData)
        );
    }

    private function mergeData(array $dataList): array
    {
        $result = [];
        foreach ($dataList as $data) {
            foreach ($data as $date => $count) {
                $result[$date] = ($result[$date] ?? 0) + (int)$count;
            }
        }
        ksort($result, SORT_STRING);
        return $result;
    }

    private function makeDataSplatoon1Battle(
        User $user,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        $date = sprintf('(CASE %s END)::date', implode(' ', [
            'WHEN {{battle}}.[[start_at]] IS NOT NULL THEN {{battle}}.[[start_at]]',
            "WHEN {{battle}}.[[end_at]] IS NOT NULL THEN {{battle}}.[[end_at]] - '3 minutes'::interval",
            "ELSE {{battle}}.[[at]] - '4 minutes'::interval",
        ]));
        $query = (new Query())
            ->select([
                'date' => $date,
                'count' => 'COUNT(*)',
            ])
            ->from('battle')
            ->andWhere(['user_id' => $user->id])
            ->andWhere([
                'between',
                $date,
                $from->format(DateTime::ATOM),
                $to->format(DateTime::ATOM)
            ])
            ->groupBy([$date])
            ->orderBy(['date' => SORT_ASC]);
        return $this->listToMap($query->all());
    }

    private function makeDataSplatoon2Battle(
        User $user,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        $date = sprintf('(CASE %s END)::date', implode(' ', [
            'WHEN {{battle2}}.[[start_at]] IS NOT NULL THEN {{battle2}}.[[start_at]]',
            "WHEN {{battle2}}.[[end_at]] IS NOT NULL THEN {{battle2}}.[[end_at]] - '3 minutes'::interval",
            "ELSE {{battle2}}.[[created_at]] - '4 minutes'::interval",
        ]));
        $query = (new Query())
            ->select([
                'date' => $date,
                'count' => 'COUNT(*)',
            ])
            ->from('battle2')
            ->andWhere(['user_id' => $user->id])
            ->andWhere([
                'between',
                $date,
                $from->format(DateTime::ATOM),
                $to->format(DateTime::ATOM)
            ])
            ->groupBy([$date])
            ->orderBy(['date' => SORT_ASC]);
        return $this->listToMap($query->all());
    }

    private function makeDataSplatoon2Salmon(
        User $user,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        $date = sprintf('(CASE %s END)::date', implode(' ', [
            'WHEN {{salmon2}}.[[start_at]] IS NOT NULL THEN {{salmon2}}.[[start_at]]',
            "ELSE {{salmon2}}.[[created_at]] - '5 minutes'::interval",
        ]));
        $query = (new Query())
            ->select([
                'date' => $date,
                'count' => 'COUNT(*)',
            ])
            ->from('salmon2')
            ->andWhere(['user_id' => $user->id])
            ->andWhere([
                'between',
                $date,
                $from->format(DateTime::ATOM),
                $to->format(DateTime::ATOM)
            ])
            ->groupBy([$date])
            ->orderBy(['date' => SORT_ASC]);
        return $this->listToMap($query->all());
    }

    private function listToMap(array $list): array
    {
        return ArrayHelper::map($list, 'date', 'count');
    }
}
