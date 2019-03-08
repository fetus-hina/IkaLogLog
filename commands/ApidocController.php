<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\commands;

use Yii;
use app\components\openapi\OpenApiSpec;
use yii\console\Controller;
use yii\helpers\FileHelper;

class ApidocController extends Controller
{
    public $defaultAction = 'create';
    public $layout = false;

    public $languages = [
        'en' => 'en-US',
        'ja' => 'ja-JP',
    ];

    public function actionCreate(): int
    {
        $successful = true;
        foreach ($this->languages as $langCodeShort => $langCodeLong) {
            if (!$this->create($langCodeShort, $langCodeLong)) {
                $successful = false;
            }
        }
        return $successful ? 0 : 1;
    }

    private function create(string $langCodeShort, string $langCodeLong): bool
    {
        Yii::$app->language = $langCodeLong;

        $successful = true;
        $successful = $this->createV1Json($langCodeShort) && $successful;
        return $successful;
    }

    private function createV1Json(string $langCode): bool
    {
        $renderer = Yii::createObject([
            'class' => OpenApiSpec::class,
            'title' => Yii::t('app-apidoc1', 'stat.ink API for Splatoon 1'),
        ]);
        $json = $renderer->renderJson();
        echo $json . "\n";
        exit;


        $this->stderr(__METHOD__ . "(): {$langCode}: Creating JSON...\n");
        $yamlPath = vsprintf('%s/runtime/apidoc/%s.json', [
            Yii::getAlias('@app'),
            vsprintf('%d-%08x', [
                time(),
                mt_rand(0, 0xffffffff),
            ]),
        ]);
        FileHelper::createDirectory(dirname($yamlPath));
        $yaml = $this->render('//apidoc/v1', [
            'langCode' => $langCode,
        ]);
        if (@file_put_contents($yamlPath, $yaml) === false) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Failed to create a YAML file!\n");
            return false;
        }

        $this->stderr(__METHOD__ . "(): {$langCode}: Checking syntax...\n");
        $cmdline = vsprintf('/usr/bin/env %s lint %s', [
            escapeshellarg(Yii::getAlias('@app/node_modules/.bin/speccy')),
            escapeshellarg($yamlPath),
        ]);
        @exec($cmdline, $lines, $status);
        if ($status !== 0) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Lint failed (status={$status}).\n");
            $this->stderr("YAML: {$yamlPath}\n");
            $this->stderr(implode("\n", $lines) . "\n");
            return false;
        }

        $this->stderr(__METHOD__ . "(): {$langCode}: Creating HTML...\n");
        $outPath = vsprintf('%s/web/apidoc/v1.%s.html', [
            Yii::getAlias('@app'),
            $langCode,
        ]);
        $cmdline = vsprintf('/usr/bin/env %s bundle -o %s --title %s %s', [
            escapeshellarg(Yii::getAlias('@app/node_modules/.bin/redoc-cli')),
            escapeshellarg($outPath),
            escapeshellarg(Yii::t('app-apidoc1', 'stat.ink API for Splatoon 1')),
            escapeshellarg($yamlPath),
        ]);
        @exec($cmdline, $lines, $status);
        if ($status !== 0) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Create failed (status={$status}).\n");
            $this->stderr("YAML: {$yamlPath}\n");
            $this->stderr(implode("\n", $lines) . "\n");
            return false;
        }
        $this->stderr(__METHOD__ . "(): OK\n");

        return true;
    }

    private function createV1(string $langCode): bool
    {
        $this->stderr(__METHOD__ . "(): {$langCode}: Creating YAML...\n");
        $yamlPath = vsprintf('%s/runtime/apidoc/%s.yaml', [
            Yii::getAlias('@app'),
            vsprintf('%d-%08x', [
                time(),
                mt_rand(0, 0xffffffff),
            ]),
        ]);
        FileHelper::createDirectory(dirname($yamlPath));
        $yaml = $this->render('//apidoc/v1', [
            'langCode' => $langCode,
        ]);
        if (@file_put_contents($yamlPath, $yaml) === false) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Failed to create a YAML file!\n");
            return false;
        }

        $this->stderr(__METHOD__ . "(): {$langCode}: Checking syntax...\n");
        $cmdline = vsprintf('/usr/bin/env %s lint %s', [
            escapeshellarg(Yii::getAlias('@app/node_modules/.bin/speccy')),
            escapeshellarg($yamlPath),
        ]);
        @exec($cmdline, $lines, $status);
        if ($status !== 0) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Lint failed (status={$status}).\n");
            $this->stderr("YAML: {$yamlPath}\n");
            $this->stderr(implode("\n", $lines) . "\n");
            return false;
        }

        $this->stderr(__METHOD__ . "(): {$langCode}: Creating HTML...\n");
        $outPath = vsprintf('%s/web/apidoc/v1.%s.html', [
            Yii::getAlias('@app'),
            $langCode,
        ]);
        $cmdline = vsprintf('/usr/bin/env %s bundle -o %s --title %s %s', [
            escapeshellarg(Yii::getAlias('@app/node_modules/.bin/redoc-cli')),
            escapeshellarg($outPath),
            escapeshellarg(Yii::t('app-apidoc1', 'stat.ink API for Splatoon 1')),
            escapeshellarg($yamlPath),
        ]);
        @exec($cmdline, $lines, $status);
        if ($status !== 0) {
            $this->stderr(__METHOD__ . "(): {$langCode}: Create failed (status={$status}).\n");
            $this->stderr("YAML: {$yamlPath}\n");
            $this->stderr(implode("\n", $lines) . "\n");
            return false;
        }
        $this->stderr(__METHOD__ . "(): OK\n");

        return true;
    }
}
