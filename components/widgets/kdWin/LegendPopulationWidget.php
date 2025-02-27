<?php

/**
 * @copyright Copyright (C) 2019-2025 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\widgets\kdWin;

use Yii;
use app\assets\EntireKDWinAsset;
use yii\base\Widget;
use yii\bootstrap\BootstrapAsset;
use yii\helpers\Html;

use function array_map;
use function floor;
use function implode;
use function range;

class LegendPopulationWidget extends Widget
{
    public $numCells = 7;

    public function run()
    {
        BootstrapAsset::register($this->view);
        EntireKDWinAsset::register($this->view);

        return Html::tag(
            'div',
            $this->renderTable(),
            [
                'id' => $this->id,
                'class' => 'table-responsive',
            ],
        );
    }

    private function renderTable(): string
    {
        return Html::tag(
            'table',
            Html::tag(
                'tbody',
                implode('', array_map(
                    fn (int $i): string => $this->renderRow($i),
                    range(0, $this->numCells - 1),
                )),
            ),
            [
                'class' => [
                    'table',
                    'table-bordered',
                    'table-condensed',
                    'rule-table',
                ],
            ],
        );
    }

    private function renderRow(int $rowNumber): string
    {
        $label = '⋮';
        if ($rowNumber === 0) {
            $label = Yii::t('app', 'Many');
        } elseif ($rowNumber === $this->numCells - 1) {
            $label = Yii::t('app', 'Few');
        }

        $dummyBattleNum = (int)floor(50 - 50 * $rowNumber / ($this->numCells - 1));
        if ($dummyBattleNum >= 50) {
            $dummyBattleNum = 100;
        }

        return Html::tag(
            'tr',
            implode('', [
                Html::tag('td', '', [
                    'class' => [
                        'text-center',
                        'kdcell',
                        'percent-cell',
                    ],
                    'data' => [
                        'battle' => $dummyBattleNum,
                        'percent' => 70,
                    ],
                ]),
                Html::tag(
                    'td',
                    Html::encode($label),
                    [
                        'class' => [
                            'text-center',
                            'kdcell',
                        ],
                    ],
                ),
            ]),
        );
    }
}
