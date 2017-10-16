<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

use app\components\db\Migration;

class m171016_163157_knockout2 extends Migration
{
    public function up()
    {
        $this->dropTable('knockout2');
        $this->createTable('knockout2', [
            'rule_id'   => $this->pkRef('rule2'),
            'map_id'    => $this->pkRef('map2'),
            'lobby_id'  => $this->pkRef('lobby2'),
            'rank_id'   => $this->pkRef('rank2'),
            'battles'   => $this->bigInteger()->notNull(),
            'knockouts' => $this->bigInteger()->notNull(),
            'avg_game_time' => $this->double()->notNull(),
            'avg_knockout_time' => $this->double()->notNull(),
            'PRIMARY KEY ([[rule_id]], [[map_id]], [[lobby_id]], [[rank_id]])',
        ]);
    }

    public function down()
    {
        $this->dropTable('knockout2');
        $this->createTable('knockout2', [
            'rule_id'   => $this->pkRef('rule2'),
            'map_id'    => $this->pkRef('map2'),
            'rank_id'   => $this->pkRef('rank2'),
            'battles'   => $this->bigInteger()->notNull(),
            'knockouts' => $this->bigInteger()->notNull(),
            'avg_game_time' => $this->double()->notNull(),
            'avg_knockout_time' => $this->double()->notNull(),
            'PRIMARY KEY ([[rule_id]], [[map_id]], [[rank_id]])',
        ]);
    }
}
