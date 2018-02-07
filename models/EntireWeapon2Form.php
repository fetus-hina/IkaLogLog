<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\models;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\models\Map2;
use yii\base\Model;

class EntireWeapon2Form extends Model
{
    public $term;
    public $map;

    public function formName()
    {
        return 'filter';
    }

    public function rules()
    {
        return [
            [['term', 'map'], 'string'],
            [['term'], 'in',
                'range' => array_keys($this->getTermList()),
            ],
            [['map'], 'exist', 'skipOnError' => true,
                'targetClass' => Map2::class,
                'targetAttribute' => 'key',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'term' => 'Term',
            'map' => 'Map',
        ];
    }

    public function toQueryParams($formName = false)
    {
        if ($formName === false) {
            $formName = $this->formName();
        }

        $ret = [];
        $push = function (string $key, $value) use ($formName, &$ret) {
            if ($formName != '') {
                $key = sprintf('%s[%s]', $formName, $key);
            }
            $ret[$key] = $value;
        };
        foreach ($this->attributes as $key => $value) {
            $push($key, $value);
        }
        return $ret;
    }

    public function getTermList() : array
    {
        static $list;
        if (!$list) {
            $list = array_merge(
                ['' => Yii::t('app', 'Any Time')],
                $this->getVersionList(),
                $this->getMonthList()
            );
        }
        return $list;
    }

    private function getVersionList() : array
    {
        $result = [];
        $groups = SplatoonVersionGroup2::find()->with('versions')->asArray()->all();
        usort($groups, function (array $a, array $b) : int {
            return version_compare($b['tag'], $a['tag']);
        });
        foreach ($groups as $group) {
            switch (count($group['versions'])) {
                case 0:
                    break;

                case 1:
                    $version = array_shift($group['versions']);
                    $result['v' . $version['tag']] = Yii::t('app', 'Version {0}', [
                        Yii::t('app-version2', $version['name']),
                    ]);
                    break;

                default:
                    $result['~v' . $group['tag']] = Yii::t('app', 'Version {0}', [
                        Yii::t('app-version2', $group['name']),
                    ]);
                    usort($group['versions'], function (array $a, array $b) : int {
                        return version_compare($b['tag'], $a['tag']);
                    });
                    $n = count($group['versions']);
                    foreach ($group['versions'] as $i => $version) {
                        $result['v' . $version['tag']] = sprintf(
                            '%s %s',
                            $i === $n - 1 ? '┗' : '┣',
                            Yii::t('app', 'Version {0}', [
                                Yii::t('app-version2', $version['name']),
                            ])
                        );
                    }
                    break;
            }
        }
        return $result;
    }

    private function getMonthList() : array
    {
        $interval = new DateInterval('P1M');
        $date = (new DateTimeImmutable())
            ->setTimeZone(new DateTimeZone('Etc/UTC'))
            ->setDate(2017, 7, 1)
            ->setTime(0, 0, 0);
        $limit = (new DateTimeImmutable())
            ->setTimeZone(new DateTimeZone('Etc/UTC'))
            ->setTimestamp($_SERVER['REQUEST_TIME'] ?? time());
        $format = (function (string $locale) {
            switch (strtolower(substr($locale, 0, 2))) {
                case 'ja':
                case 'zh':
                    return "y'年'M'月'";
                case 'ko':
                    return "y'년'M'월'";
                case 'es':
                    return "MMMM 'de' y";
                default:
                    return 'MMMM y';
            }
        })(Yii::$app->language);
        $formatter = Yii::$app->formatter;
        $result = [];
        for (; $date <= $limit; $date = $date->add($interval)) {
            $result[$date->format('Y-m')] = $formatter->asDate($date, $format);
        }
        return array_reverse($result, true);
    }
}
