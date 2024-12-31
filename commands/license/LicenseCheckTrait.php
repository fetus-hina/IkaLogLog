<?php

/**
 * @copyright Copyright (C) 2021-2024 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\commands\license;

use Throwable;
use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use function array_map;
use function array_shift;
use function call_user_func;
use function count;
use function escapeshellarg;
use function fwrite;
use function in_array;
use function is_array;
use function is_string;
use function join;
use function natcasesort;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function substr;
use function trim;
use function vsprintf;

use const STDERR;

trait LicenseCheckTrait
{
    use Helper;

    private array $safeLicenses = [
        '0BSD',
        'AFLv2.1',
        'Apache-2.0',
        'BSD',
        'BSD*',
        'BSD-2-Clause',
        'BSD-3-Clause',
        'ISC',
        'MIT',
        'MIT*',
        'MIT-0',
        'OFL-1.1',
        'Public Domain',
        'Python-2.0',
        'Unlicense',
        'WTFPL',
    ];

    private array $safeLicenseRegexes = [
        '/^CC-BY-(?:NC-)?[0-9.]+$/',
        '/^CC0-[0-9.]+$/',
        '/^LGPL/',
    ];

    private array $skipPackages = [
        'composer::omnilight/yii2-scheduling',
    ];

    public function actionCheck(): int
    {
        $status = 0;
        $status |= $this->actionCheckPhp();
        $status |= $this->actionCheckJs();
        return $status;
    }

    public function actionCheckPhp(): int
    {
        return $this->check(
            'composer',
            vsprintf('/usr/bin/env %s --no-interaction --no-plugins license --format=%s', [
                escapeshellarg(Yii::getAlias('@app/composer.phar')),
                escapeshellarg('json'),
            ]),
            'dependencies',
            function (array $json): array {
                $results = [];
                foreach ($json as $key => $values) {
                    $version = $values['version'] ?? 'NONE';
                    $package = "{$key}@${version}";
                    $license = $values['license'] ?? null;
                    if (is_array($license) && count($license) === 1) {
                        $license = array_shift($license);
                    }
                    $results[$package] = is_string($license) ? $license : Json::encode($license);
                }
                return $results;
            },
        );
    }

    public function actionCheckJs(): int
    {
        return $this->check(
            'npm',
            vsprintf('/usr/bin/env %s %s --json', [
                escapeshellarg('npx'),
                escapeshellarg('license-checker-rseidelsohn'),
            ]),
            null,
            fn (array $json): array => array_map(
                function (array $values): string {
                    $tmp = $values['licenses'] ?? null;
                    return is_string($tmp) ? $tmp : Json::encode($tmp);
                },
                $json,
            ),
        );
    }

    protected function check(string $manager, string $cmdline, ?string $packagesSelector, ?callable $normalizer): int
    {
        try {
            $jsonStr = $this->execCommand($cmdline);
            if ($jsonStr === null) {
                return 1;
            }

            try {
                $json = Json::decode($jsonStr, true);
            } catch (InvalidArgumentException $e) {
                fwrite(STDERR, "Failed to decode a JSON: broken JSON\n");
                return 1;
            }
            if (!is_array($json)) {
                fwrite(STDERR, "Failed to decode a JSON: is not an array\n");
                return 1;
            }

            if ($packagesSelector !== null) {
                $json = ArrayHelper::getValue($json, $packagesSelector);
            }

            if ($normalizer !== null) {
                $json = call_user_func($normalizer, $json);
            }

            return $this->doCheck($manager, $json);
        } catch (Throwable $e) {
            return 1;
        }
    }

    private function doCheck(string $manager, array $json): int
    {
        $list = [];
        foreach ($json as $package => $license) {
            $name = vsprintf('%s::%s', [$manager, $package]);
            if (
                !$this->shouldSkipChecking($name) &&
                !$this->isSafeLicense($license)
            ) {
                $list[] = vsprintf('%-55s %s', [
                    $name,
                    $license,
                ]);
            }
        }

        if (!$list) {
            return 0;
        }

        natcasesort($list);
        fwrite(STDERR, join("\n", $list) . "\n");
        return 1;
    }

    private function isSafeLicense(string $license): bool
    {
        // OK if known license
        if (in_array($license, $this->safeLicenses, true)) {
            return true;
        }

        // OK if known license (regex match)
        foreach ($this->safeLicenseRegexes as $regex) {
            if (preg_match($regex, $license)) {
                return true;
            }
        }

        // AND and OR, check recursive
        $list = preg_split('/\s+(?:OR|AND)\s+/i', trim($license, '()'));
        if (count($list) > 1) {
            foreach ($list as $tmp) {
                if ($this->isSafeLicense($tmp)) {
                    return true;
                }
            }
        }

        // Process JSON encoded
        if (substr($license, 0, 1) === '[') {
            try {
                $list = Json::decode($license);
                if (is_array($list)) {
                    foreach ($list as $tmp) {
                        if ($this->isSafeLicense($tmp)) {
                            return true;
                        }
                    }
                }
            } catch (Throwable $e) {
            }
        }

        return false;
    }

    private function shouldSkipChecking(string $packageName): bool
    {
        foreach ($this->skipPackages as $skipPackage) {
            if (str_starts_with($packageName, "{$skipPackage}@")) {
                return true;
            }
        }

        return false;
    }
}
