<?php

/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\helpers;

use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\models\Timezone;
use yii\helpers\StringHelper;

use const SORT_ASC;

class UserTimeZone
{
    public const COOKIE_KEY = 'timezone';

    public static function guess(bool $getDefault = true): ?Timezone
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);
            $methods = [
                [static::class, 'guessByCookie'],
                [static::class, 'guessByGeoIP'],
                [static::class, 'guessByAppLanguage'],
            ];

            foreach ($methods as $method) {
                if ($ret = call_user_func($method)) {
                    return $ret;
                }
            }

            if ($getDefault) {
                Yii::info("Returns default timezone, UTC", __METHOD__);
                return Timezone::findOne(['identifier' => 'Etc/UTC']);
            }

            return null;
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    public static function guessByCookie(): ?Timezone
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);
            $cookie = Yii::$app->request->cookies->get(static::COOKIE_KEY);
            if (!$cookie) {
                return null;
            }

            $tz = Timezone::findOne(['identifier' => $cookie->value]);
            if ($tz) {
                Yii::info(
                    "Detected timezone by cookie, " . $tz->identifier,
                    __METHOD__
                );
            }

            return $tz;
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    public static function guessByAppLanguage(): ?Timezone
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);

            $map = [
                'de*'   => 'Europe/Paris',
                'en-GB' => 'Europe/London',
                'en*'   => 'America/Los_Angeles',
                'es-MX' => 'America/Mexico_City',
                'es*'   => 'Europe/Paris',
                'fr-CA' => 'America/New_York',
                'fr*'   => 'Europe/Paris',
                'it*'   => 'Europe/Paris',
                'ja*'   => 'Asia/Tokyo',
                'nl*'   => 'Europe/Paris',
                'ru*'   => 'Europe/Moscow',
                'zh-TW' => 'Asia/Taipei',
                'zh*'   => 'Asia/Shanghai',
            ];

            $wildcardOptions = [
                'caseSensitive' => false,
                'filePath' => false,
            ];

            $lang = Yii::$app->language;

            foreach ($map as $match => $ourTZ) {
                if (StringHelper::matchWildcard($match, $lang, $wildcardOptions)) {
                    $tz = Timezone::findOne(['identifier' => $ourTZ]);
                    if ($tz) {
                        Yii::info(
                            "Detected language by application language, " . $tz->identifier,
                            __METHOD__
                        );
                        return $tz;
                    }
                }
            }

            return null;
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    public static function guessByGeoIP(): ?Timezone
    {
        if (!$result = static::guessByGeoIPEx()) {
            return null;
        }

        return $result[0];
    }

    public static function guessByGeoIPEx(): ?array
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);
            $ipAddr = Yii::$app->request->getUserIP();
            if (!$ipAddr) {
                return null;
            }

            if (!$identifier = static::consultGeoIPDB($ipAddr)) {
                return null;
            }

            $tz = Timezone::findOne(['identifier' => $identifier]);
            if ($tz) {
                Yii::info(
                    "Detected timezone by geoip, " . $tz->identifier,
                    __METHOD__
                );
                return [$tz, $identifier];
            }

            Yii::info(
                'There was no exact time zone match: ' . $identifier,
                __METHOD__
            );
            $tz = static::guessTimezoneByIdentifier($identifier);
            if ($tz) {
                Yii::info(
                    sprintf(
                        'Detected timezone by geoip %s, guessed our timezone %s',
                        $identifier,
                        $tz->identifier
                    ),
                    __METHOD__
                );
            }

            return [$tz, $identifier];
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    private static function consultGeoIPDB(string $ipAddr): ?string
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);

            if (!$city = Yii::$app->geoip->city($ipAddr)) {
                Yii::warning('Could not determinate user\'s city from IP ' . $ipAddr, __METHOD__);
                return null;
            }

            Yii::info(
                sprintf(
                    'GeoIP: country=%s, city=%s, geo=(%f, %f), timezone=%s',
                    $city->country->name ?? '?',
                    $city->city->name ?? '?',
                    $city->location->latitude ?? 0,
                    $city->location->longitude ?? 0,
                    $city->location->timeZone ?? '?',
                ),
                __METHOD__
            );

            if (!$location = $city->location) {
                Yii::warning('Could not determinate user\'s city from IP ' . $ipAddr, __METHOD__);
                return null;
            }

            $tz = trim((string)$location->timeZone);
            if ($tz === '') {
                Yii::warning(
                    'GeoIP\'s record has not a time zone information, IP ' . $ipAddr,
                    __METHOD__
                );
                return null;
            }

            return $tz;
        } catch (\Throwable $e) {
            Yii::warning(
                'Catch an exception: ' . $e->getMessage(),
                __METHOD__
            );
            return null;
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    private static function guessTimezoneByIdentifier(string $identifier): ?Timezone
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);
            try {
                $tz = new DateTimeZone($identifier);
            } catch (\Throwable $e) {
                return null;
            }
            if ($guessed = static::guessTimezoneByTimezone($tz)) {
                return $guessed;
            }

            return static::createUTCOffsetTimezone($tz);
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    private static function guessTimezoneByTimezone(DateTimeZone $tz1): ?Timezone
    {
        try {
            Yii::beginProfile(__FUNCTION__, __METHOD__);
            $t1 = new DateTimeImmutable('2019-01-01T00:00:00+00:00');
            $t2 = new DateTimeImmutable('2019-07-01T00:00:00+00:00');
            $timezones = Timezone::find()
                ->andWhere(['not like', 'timezone.identifier', 'Etc/%', false])
                ->orderBy(['timezone.order' => SORT_ASC])
                ->all();
            foreach ($timezones as $timezone) {
                $tz2 = new DateTimeZone($timezone->identifier);

                $val1 = $t1->setTimezone($tz1)->format('TP');
                $val2 = $t1->setTimezone($tz2)->format('TP');
                if ($val1 !== $val2) {
                    continue;
                }

                $val1 = $t2->setTimezone($tz1)->format('TP');
                $val2 = $t2->setTimezone($tz2)->format('TP');
                if ($val1 !== $val2) {
                    continue;
                }

                return $timezone;
            }

            return null;
        } finally {
            Yii::endProfile(__FUNCTION__, __METHOD__);
        }
    }

    private static function createUTCOffsetTimezone(DateTimeZone $tz): ?Timezone
    {
        $offsetSec = (new DateTimeImmutable('now', $tz))->format('Z');
        $offsetHours = (int)floor($offsetSec / 3600);
        $tzName = sprintf('Etc/GMT%+d', -$offsetHours);
        return Timezone::findOne(['identifier' => $tzName]);
    }
}
