<?php

/**
 * @copyright Copyright (C) 2022-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\api\v3\salmon;

use Throwable;
use Yii;
use app\actions\api\v3\traits\ApiInitializerTrait;
use app\components\helpers\UuidRegexp;
use app\components\jobs\SalmonStatsJob;
use app\models\Salmon3;
use app\models\User;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use function preg_match;
use function usleep;

final class DeleteSalmonAction extends Action
{
    use ApiInitializerTrait;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->apiInit();
    }

    public function run(string $uuid): Response
    {
        if (Yii::$app->request->method !== 'DELETE') {
            throw new MethodNotAllowedHttpException();
        }

        $user = Yii::$app->user->identity;
        if (
            !$user instanceof User || // Logic error
            !preg_match(UuidRegexp::get(true), $uuid)
        ) {
            // Logic error
            throw new BadRequestHttpException();
        }

        $model = Salmon3::find()
            ->andWhere([
                'is_deleted' => false,
                'user_id' => $user->id,
                'uuid' => $uuid,
            ])
            ->limit(1)
            ->one();
        if (!$model) {
            throw new NotFoundHttpException('The job is not online');
        }

        $resp = Yii::$app->response;
        for ($retry = 0; $retry < 3; ++$retry) {
            try {
                if ($retry > 0) {
                    usleep(250000);
                    $model->refresh();
                    if ($model->is_deleted) {
                        $resp->statusCode = 409;
                        $resp->data = ['error' => 'conflict'];
                        return $resp;
                    }
                }

                $model->is_deleted = true;
                if ($model->save(false)) {
                    SalmonStatsJob::pushQueue3($user);
                    $resp->statusCode = 204;
                    return $resp;
                }
            } catch (Throwable $e) {
            }
        }
        $resp->statusCode = 503;
        $resp->data = ['error' => 'failed to delete the job'];
        return $resp;
    }
}
