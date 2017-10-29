<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */
namespace app\components\db;

use Yii;
use yii\db\Expression;
use yii\db\Query;

trait StageMigration
{
    protected function setArea(array $list) : void
    {
        $db = Yii::$app->db;
        $value = new Expression(vsprintf('(CASE %s %s END)', [
            $db->quoteColumnName('key'),
            (function () use ($list, $db) : string {
                return implode(' ', array_map(
                    function (string $key, ?int $area) use ($db) : string {
                        return vsprintf('WHEN %s THEN %s', [
                            $db->quoteValue($key),
                            $area === null ? 'NULL' : $db->quoteValue($area),
                        ]);
                    },
                    array_keys($list),
                    array_values($list)
                ));
            })(),
        ]));
        $this->execute(
            $db->createCommand()
                ->update('map2', ['area' => $value], ['key' => array_keys($list)])
                ->rawSql
        );
    }
}
