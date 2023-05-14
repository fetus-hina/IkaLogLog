<?php

declare(strict_types=1);

use app\actions\show\v3\stats\SeasonXPowerAction;
use app\models\Rule3;
use app\models\Season3;
use app\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @phpstan-import-type DailyData from SeasonXPowerAction
 *
 * @var DailyData[] $dailyData
 * @var Rule3[] $rules
 * @var Season3 $season,
 * @var User $user
 * @var View $this
 */

$tz = new DateTimeZone('Etc/UTC');
$tStart = (new DateTimeImmutable($season->start_at))->setTimezone($tz);
$tEnd = (new DateTimeImmutable($season->end_at))->setTimezone($tz);
$tInterval = new DateInterval('P1D');

// format-pattern string for "month and day"
$dateFormat = match (true) {
  // PHP 8.1
  class_exists('IntlDatePatternGenerator') => IntlDatePatternGenerator::create(Yii::$app->locale)
    ?->getBestPattern('MMM d')
    ?? 'd MMM',
  // PHP 8.0 or lower
  default => match (Yii::$app->locale) {
    'de-DE' => 'd. MMM',
    'en-US' => 'MMM d',
    'ja-JP', 'ja-JP@calendar=japanese', 'zh-CN', 'zh-TW' => 'M月d日',
    'ko-KR' => 'MMM d일',
    default => 'd MMM',
  },
};

$data = ArrayHelper::index($dailyData, 'rule_id', 'date');

?>
<tbody>
<?php for ($date = $tStart; $date < $tEnd; $date = $date->add($tInterval)) { ?>
  <?= Html::tag(
    'tr',
    implode('', [
      Html::tag(
        'td',
        Html::tag(
          'time',
          Yii::$app->formatter->asDate($date, $dateFormat),
          ['datetime' => $date->format('Y-m-d')],
        ),
        ['class' => 'text-center'],
      ),
      Html::tag(
        'td',
        Html::tag(
          'time',
          Yii::$app->formatter->asDate($date, 'cccccc'),
          ['datetime' => $date->format('Y-m-d')],
        ),
        ['class' => 'text-center'],
      ),
      implode('', array_map(
        fn (Rule3 $rule): string => $this->render('tbody/rule', [
          'data' => ArrayHelper::getValue($data, [$date->format('Y-m-d'), $rule->id]),
          'date' => $date,
          'rule' => $rule,
          'season' => $season,
          'user' => $user,
        ]),
        $rules,
      )),
    ]),
    [
      'class' => sprintf('bg-dow-%s', $date->format('N')),
    ],
  ) . "\n" ?>
<?php } ?>
</tbody>
