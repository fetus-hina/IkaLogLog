<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\openapi;

use Yii;
use app\models\Gear;
use app\models\Map;
use app\models\Rule;
use app\models\Special;
use app\models\Subweapon;
use app\models\Weapon;
use app\models\WeaponType;
use app\models\openapi\Name;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;

class OpenApiSpec extends Component
{
    public $openapi = '3.0.2';
    public $title; // should be set
    public $info;
    public $servers;
    public $paths;
    private $tags;
    private $schemas = [];

    public function init()
    {
        // {{{
        parent::init();

        if (!$this->info) {
            $this->info = [
                'title' => (string)$this->title,
                'version' => '1.0.0',
                'contact' => [
                    'name' => Yii::$app->name,
                    'url' => 'https://github.com/fetus-hina/stat.ink',
                ],
                'license' => [
                    'name' => 'CC-BY 4.0',
                    'url' => vsprintf('https://creativecommons.org/licenses/by/4.0/deed.%s', [
                        substr(Yii::$app->language, 0, 2),
                    ]),
                ],
            ];
        }

        if (!$this->servers) {
            $this->servers = [
                [
                    'url' => rtrim(Url::to('/', true), '/'),
                    'description' => 'production',
                ],
            ];
        }
        // }}}
    }

    public function renderJson(): string
    {
        $json = [
            'openapi' => $this->openapi,
            'info' => $this->info,
            'servers' => $this->servers,
            'paths' => $this->paths,
            'components' => [
                'schemas' => $this->renderSchemas(),
            ],
            'tags' => $this->renderTags(),
        ];
        return Json::encode(
            $json,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    protected function renderSchemas(): array
    {
        $result = [];
        foreach ($this->schemas as $className) {
            $refName = call_user_func([$className, 'oapiRefName']);
            $result[$refName] = call_user_func([$className, 'openApiSchema']);
        }
        return $result;
    }

    public function registerSchema(string $className): void
    {
        $depends = call_user_func([$className, 'openApiDepends']);
        foreach ($depends as $depClass) {
            $this->registerSchema($depClass);
        }

        if (!in_array($className, $this->schemas, true)) {
            $this->schemas[] = $className;
        }
    }

    protected function renderTags(): array
    {
        $result = [];
        foreach ($this->tags as $key => $description) {
            $result[] = array_filter([
                'name' => $key,
                'description' => $description,
            ]);
        }
        return $result;
    }

    public function registerTag(string $key, ?string $description = null): void
    {
        if (!isset($this->tags[$key])) {
            $this->tags[$key] = $description;
        }
    }
}
