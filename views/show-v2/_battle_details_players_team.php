<?php
use app\assets\NameAnonymizerAsset;
use app\models\User;
use yii\bootstrap\Html;

$fmt = Yii::$app->formatter;

$totalK = 0;
$totalD = 0;
$totalP = 0;
$totalInked = 0;
$totalKA = 0;
$totalSP = 0;
$totalRatio = '';
$totalRate = '';
foreach ($players as $player) {
  $totalK = ($totalK === null || $player->kill === null) ? null : ($totalK + $player->kill);
  $totalD = ($totalD === null || $player->death === null) ? null : ($totalD + $player->death);
  if ($totalP === null || $player->point === null || $battle->is_win === null) {
    $totalP = null;
  }
  if ((bool)$battle->is_win === ($teamKey === 'my')) { // ボーナスがついているはず
    if ($player->point < $bonus) {
      $totalP = null;
    } else {
      $totalP += $player->point - $bonus;
    }
  } else {
    $totalP += $player->point;
  }
  if ($totalInked === null || !$hasRankedInked || $player->point === null) {
    $totalInked = null;
  } else {
    $totalInked += $player->point;
  }
  $totalKA = ($totalKA === null || $player->kill_or_assist === null) ? null : ($totalKA + $player->kill_or_assist);
  $totalSP = ($totalSP === null || $player->special === null) ? null : ($totalSP + $player->special);
}
if ($totalK !== null && $totalD !== null) {
  if ($totalD === 0) {
    if ($totalK === 0) {
      $totalRatio = 'N/A';
      $totalRate = 'N/A';
    } else {
      $totalRatio = $fmt->asDecimal(99.99, 2);
      $totalRate = $fmt->asPercent(1, 2);
    }
  } else {
    $totalRatio = $fmt->asDecimal($totalK / $totalD, 2);
    $totalRate = $fmt->asPercent($totalK / ($totalK + $totalD), 2);
  }
}

// チーム合計
$teamId = trim($teamKey === 'my' ? $battle->my_team_id : $battle->his_team_id);
echo Html::tag(
  'tr',
  '  ' . implode("\n  ", [
    Html::tag(
      'th',
      trim(implode(' ', [
        Html::encode(
          ($battle->my_team_fest_theme_id !== null && $battle->his_team_fest_theme_id !== null)
            ? Yii::t('app', 'Team {theme}', [
              'theme' => ($teamKey === 'my')
                ? $battle->myTeamFestTheme->name
                : $battle->hisTeamFestTheme->name,
            ])
            : Yii::t('app', ($teamKey === 'my') ? 'Good Guys' : 'Bad Guys')
        ),
        $teamId == ''
          ? ''
          : Html::tag(
            'code',
            Html::encode($teamId),
            [
              'class' => 'auto-tooltip',
              'title' => Yii::t('app', 'Team ID'),
              'style' => [
                'font-weight' => '400',
              ],
            ]
          ),
      ])),
      ['colspan' => 2]
    ),
    !$hasName ? '' : Html::tag('td', ''),
    Html::tag('td', ''),
    $hideRank ? '' : Html::tag('td', ''),
    $hidePoint ? '' : Html::tag('td', Html::encode($totalP === null ? '' : $fmt->asInteger($totalP)), ['class' => 'text-right']),
    !$hasRankedInked ? '' : Html::tag(
      'td',
      Html::encode($totalInked === null ? '' : $fmt->asInteger($totalInked)),
      ['class' => 'text-right']
    ),
    Html::tag('td', '', ['class' => 'text-center']),
    !$hasKD ? '' : Html::tag('td', Html::encode(sprintf(
      '%s / %s',
      $totalK === null ? '?' : $fmt->asInteger($totalK),
      $totalD === null ? '?' : $fmt->asInteger($totalD)
    )), ['class' => 'text-center']),
    !$hasKD ? '' : Html::tag('td', Html::encode($totalRatio), ['class' => 'text-right']),
    !$hasKD ? '' : Html::tag('td', Html::encode($totalRate), ['class' => 'text-right']),
  ]),
  ['class' => 'bg-' . $teamKey]
) . "\n";
foreach ($players as $i => $player) {
  echo Html::tag(
    'tr',
    '  ' . implode("\n  ", [
      Html::tag(
        'td',
        $player->is_me
          ? Html::tag('span', '', ['class' => 'fas fa-fw fa-rotate-90 fa-level-up-alt'])
          : '',
        ['class' => ['bg-' . $teamKey, 'text-center']]
      ),
      $hasName
        ? Html::tag(
          'td',
          (function () use ($battle, $player, $teamKey) {
            $user = $player->getUser();
            $innerHtml = trim(implode(' ', [
              // identicon {{{
              (function () use ($player) : string {
                if (!$url = $player->iconUrl) {
                  return '';
                }
                return Html::img(
                  $url,
                  [
                    'class' => 'auto-tooltip',
                    'title' => (trim($player->splatnet_id) !== '')
                      ? sprintf('ID: %s', $player->splatnet_id)
                      : '',
                    'style' => [
                      'width' => '1.2em',
                      'height' => 'auto',
                    ],
                  ]
                );
              })(),
              // }}}
              // top player {{{
              (function () use ($player) : string {
                if (!$player->top_500) {
                  return '';
                }

                return Html::tag('span', '', [
                  'class' => 'fas fa-fw fa-chess-queen',
                ]);
              })(),
              // }}}
              // name {{{
              (function () use ($battle, $player, $teamKey) : string {
                $anonymize = false;
                if ($player->is_me) {
                  // "me" always shown
                  $anonymize = false;
                } elseif (trim($player->name) === '') {
                  // We can only show an anonymized name
                  $anonymize = true;
                } else {
                  $user = Yii::$app->user;
                  if (!$user->isGuest && $user->identity->id == $battle->user_id) {
                    // Logged in user is also battle owner.
                    // All users' name will be shown
                    $anonymize = false;
                  } else {
                    // respect user's setting
                    $blackoutMode = $battle->user->blackout_list ?? 'always';
                    switch ($blackoutMode) {
                      case User::BLACKOUT_NOT_BLACKOUT:
                        $anonymize = false;
                        break;

                      case User::BLACKOUT_NOT_PRIVATE:
                        if (($battle->lobby->key ?? '') === 'private' || ($battle->mode->key ?? '') === 'private') {
                          $anonymize = false;
                        } else {
                          $anonymize = true;
                        }
                        break;

                      case User::BLACKOUT_NOT_FRIEND:
                        if (($battle->lobby->key ?? '') === 'private' || ($battle->mode->key ?? '') === 'private') {
                          $anonymize = false;
                        } elseif ($battle->isGachi && ($battle->lobby->key ?? '') === 'squad_4' && ($teamKey === 'my')) {
                          $anonymize = false;
                        } else {
                          $anonymize = true;
                        }
                        break;

                      case User::BLACKOUT_ALWAYS:
                      default:
                        $anonymize = true;
                        break;
                    }
                  }
                }
                if (!$anonymize && trim($player->name) !== '') {
                  return Html::encode(trim($player->name));
                } else {
                  NameAnonymizerAsset::register($this);
                  return Html::tag(
                    'span',
                    Html::encode(str_repeat('*', 10)),
                    [
                      'title' => Yii::t('app', 'Anonymized'),
                      'class' => 'auto-tooltip anonymize',
                      'data' => [
                        'anonymize' => (function (string $raw) : string {
                          return substr(
                            hash(
                              'sha256',
                              (preg_match('/^([0-9a-f]{2}+)[0-9a-f]?$/', $raw, $match))
                                ? hex2bin($match[1])
                                : $raw
                            ),
                            0,
                            40
                          );
                        })($player->anonymizeSeed),
                      ],
                    ]
                  );
                }
              })(),
              // }}}
            ]));
            return $user
              ? Html::a(
                $innerHtml,
                ['show-user/profile', 'screen_name' => $user->screen_name]
              )
              : $innerHtml;
          })(),
          ['class' => 'col-name']
        ) : '',
      Html::tag(
        'td',
        $player->weapon
          ? implode(' ', [
            $hasName
              ? ''
              : (function () use ($player) : string {
                // top player {{{
                if (!$player->top_500) {
                  return '';
                }

                return Html::tag('span', '', [
                  'class' => 'fas fa-fw fa-chess-queen',
                ]);
                // }}}
              })(),
            Html::tag('span', Html::encode(Yii::t('app-weapon2', $player->weapon->name)), [
              'class' => 'auto-tooltip',
              'title' => Html::encode(sprintf(
                '%s %s / %s %s',
                Yii::t('app', 'Sub:'),
                Yii::t('app-subweapon2', $player->weapon->subweapon->name ?? '?'),
                Yii::t('app', 'Special:'),
                Yii::t('app-special2', $player->weapon->special->name ?? '?')
              )),
            ]),
          ])
          : '',
        ['class' => 'col-weapon']
      ),
      Html::tag(
        'td',
        sprintf(
          '%2$s%1$s',
          Html::encode($player->level),
          (($player->star_rank ?? 0) > 0)
            ? Html::tag('span', Html::encode('★'), [
              'style' => [
                'vertical-align' => 'super',
                'font-size' => '0.75em',
              ],
              'class' => 'auto-tooltip',
              'title' => (string)$player->star_rank,
            ])
            : ''
        ),
        [
          'class' => ['col-level', 'text-right'],
        ]
      ),
      $hideRank ? '' : Html::tag('td', Html::encode(Yii::t('app-rank2', $player->rank->name ?? '')), ['class' => ['col-rank', 'text-center']]),
      $hidePoint
        ? ''
        : Html::tag(
          'td',
          implode('', [
            Html::tag(
              'span',  
              Html::encode($player->point === null ? '' : $fmt->asInteger($player->point)),
              ['class' => 'col-point-point']
            ),
            Html::tag(
              'span',  
              Html::encode(
                $player->point === null
                  ? ''
                  : $fmt->asInteger(
                    $player->point - ((bool)$battle->is_win === ($teamKey === 'my') ? 1000 : 0)
                  )
              ),
              ['class' => 'col-point-inked hidden', 'aria-hidden' => 'true']
            ),
          ]),
          ['class' => ['col-point', 'text-right']]
        ),
      $hasRankedInked
        ? Html::tag(
          'td',
          Html::tag(
            'span',  
            Html::encode(
              $player->point === null
                ? ''
                : $fmt->asInteger($player->point)
            ),
            ['class' => 'col-point-inked']
          ),
          ['class' => ['col-point', 'text-right']]
        )
        : '',
      Html::tag('td', Html::encode(sprintf(
        '%s %s / %s', 
        $player->kill_or_assist === null ? '?' : $fmt->asInteger($player->kill_or_assist),
        $player->kill_or_assist !== null && $player->kill !== null
          ? sprintf('(%s)', $fmt->asInteger($player->kill_or_assist - $player->kill))
          : '',
        $player->special === null ? '?' : $fmt->asInteger($player->special)
      )), ['class' => ['col-kasp', 'text-center']]),
      !$hasKD ? '' : Html::tag('td', Html::encode(sprintf(
        '%s / %s',
        $player->kill === null ? '?' : $fmt->asInteger($player->kill),
        $player->death === null ? '?' : $fmt->asInteger($player->death)
      )), ['class' => ['col-kd', 'text-center']]),
      !$hasKD ? '' : Html::tag('td', Html::encode($player->getFormattedKillRatio()), ['class' => ['col-kd', 'text-right']]),
      !$hasKD ? '' : Html::tag('td', Html::encode($player->getFormattedKillRate()), ['class' => ['col-kd', 'text-right']]),
    ]),
    ['class' => [
      $player->is_me ? 'its-me' : '',
      $player->isDisconnected ? 'disconnected' : '',
    ]]
  ) . "\n";
}
