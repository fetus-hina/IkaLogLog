<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\openapi\doc;

use Yii;
use app\models\Ability;
use app\models\Brand;
use app\models\Gear;
use app\models\GearType;
use app\models\Map;
use app\models\Rule;
use app\models\Special;
use app\models\Subweapon;
use app\models\Weapon;
use app\models\WeaponType;
use app\models\openapi\Name;
use yii\helpers\ArrayHelper;

class V1 extends Base
{
    public function getTitle(): string
    {
        return Yii::t('app-apidoc1', 'stat.ink API for Splatoon 1');
    }

    public function getPaths(): array
    {
        return [
            '/api/v1/gear' => $this->getPathInfoGear(),
            '/api/v1/map' => $this->getPathInfoMap(),
            '/api/v1/rule' => $this->getPathInfoRule(),
            '/api/v1/weapon' => $this->getPathInfoWeapon(),
        ];
    }

    protected function getPathInfoGear(): array
    {
        // {{{
        $this->registerSchema(Gear::class);
        $this->registerTag('general');
        return [
            'get' => [
                'operationId' => 'getGear',
                'summary' => Yii::t('app-apidoc1', 'Get gears'),
                'description' => Yii::t(
                    'app-apidoc1',
                    'Returns an array of gear information'
                ),
                'tags' => [
                    'general',
                ],
                'parameters' => [
                    [
                        'name' => 'type',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of gear type'
                            ),
                            '',
                            WeaponType::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Gear Type'),
                                'app-gear',
                                GearType::find()
                                    ->orderBy(['id' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                GearType::find()
                                    ->orderBy(['id' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                    [
                        'name' => 'brand',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of brand'
                            ),
                            '',
                            WeaponType::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Brand'),
                                'app-brand',
                                Brand::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                Brand::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                    [
                        'name' => 'ability',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of ability'
                            ),
                            '',
                            WeaponType::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Ability'),
                                'app-ability',
                                Ability::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                Ability::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => Yii::t('app-apidoc1', 'Successful'),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => Gear::oapiRef(),
                                ],
                                'example' => Gear::openapiExample(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // }}}
    }

    protected function getPathInfoMap(): array
    {
        // {{{
        $this->registerSchema(Map::class);
        $this->registerTag('general');
        return [
            'get' => [
                'operationId' => 'getMap',
                'summary' => Yii::t('app-apidoc1', 'Get stages'),
                'description' => Yii::t(
                    'app-apidoc1',
                    'Returns an array of stage information'
                ),
                'tags' => [
                    'general',
                ],
                'responses' => [
                    '200' => [
                        'description' => Yii::t('app-apidoc1', 'Successful'),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => Map::oapiRef(),
                                ],
                                'example' => Map::openapiExample(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // }}}
    }

    protected function getPathInfoRule(): array
    {
        // {{{
        $this->registerSchema(Rule::class);
        $this->registerTag('general');
        return [
            'get' => [
                'operationId' => 'getRule',
                'summary' => Yii::t('app-apidoc1', 'Get game modes'),
                'description' => Yii::t(
                    'app-apidoc1',
                    'Returns an array of game mode information'
                ),
                'tags' => [
                    'general',
                ],
                'responses' => [
                    '200' => [
                        'description' => Yii::t('app-apidoc1', 'Successful'),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => Rule::oapiRef(),
                                ],
                                'example' => Rule::openapiExample(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // }}}
    }

    protected function getPathInfoWeapon(): array
    {
        // {{{
        $this->registerSchema(Weapon::class);
        $this->registerTag('general');
        return [
            'get' => [
                'operationId' => 'getWeapon',
                'summary' => Yii::t('app-apidoc1', 'Get weapons'),
                'description' => Yii::t(
                    'app-apidoc1',
                    'Returns an array of weapon information'
                ),
                'tags' => [
                    'general',
                ],
                'parameters' => [
                    [
                        'name' => 'weapon',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of the weapon'
                            ),
                            '',
                            Weapon::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Weapon'),
                                'app-weapon',
                                Weapon::find()
                                    ->naturalOrder()
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                Weapon::find()
                                    ->naturalOrder()
                                    ->asarray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                    [
                        'name' => 'type',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of weapon type'
                            ),
                            '',
                            WeaponType::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Weapon Type'),
                                'app-weapon',
                                WeaponType::find()
                                    ->orderBy(['id' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                WeaponType::find()
                                    ->orderBy(['id' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                    [
                        'name' => 'sub',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of sub weapon'
                            ),
                            '',
                            Subweapon::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Sub Weapon'),
                                'app-subweapon',
                                Subweapon::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                Subweapon::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                    [
                        'name' => 'special',
                        'in' => 'query',
                        'description' => implode("\n", [
                            Yii::t(
                                'app-apidoc1',
                                'Filter by key-string of special weapon'
                            ),
                            '',
                            Special::oapiKeyValueTable(
                                Yii::t('app-apidoc1', 'Special Weapon'),
                                'app-special',
                                Special::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->all()
                            ),
                        ]),
                        'schema' => [
                            'type' => 'string',
                            'enum' => ArrayHelper::getColumn(
                                Special::find()
                                    ->orderBy(['key' => SORT_ASC])
                                    ->asArray()
                                    ->all(),
                                'key'
                            ),
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => Yii::t('app-apidoc1', 'Successful'),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => Weapon::oapiRef(),
                                ],
                                'example' => Weapon::openapiExample(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // }}}
    }
}
