<?php

declare(strict_types=1);

use app\components\db\Migration;
use yii\helpers\StringHelper;

/**
 * @var bool $inTransaction
 * @var string $className
 * @var string|null $downCode
 * @var string|null $extraCode
 * @var string $namespace
 * @var string|null $upCode
 * @var string[]|null $traits
 */

$inTransaction = (isset($inTransaction) && $inTransaction);
$upFuncName = $inTransaction ? 'safeUp' : 'up';
$downFuncName = $inTransaction ? 'safeDown' : 'down';

$codeFmt = function (?string $code, int $indent): string {
    if ($code === null || $code === '') {
        return '';
    }

    return implode(
        "\n",
        array_map(
            function (string $line) use ($indent): string {
                return str_repeat(' ', $indent) . $line;
            },
            explode("\n", $code)
        )
    ) . "\n";
};

$uses = [
    Migration::class,
];
if (isset($traits)) {
    $traits = array_unique((array)$traits);
    $uses = array_merge($uses, array_map(
        fn (string $trait): string => ltrim($trait, '\\'),
        $traits,
    ));
}
sort($uses);
$uses = array_unique($uses);

echo "<?php\n";
echo "\n";
echo "/**\n";
echo " * @copyright Copyright (C) 2015-" . date('Y', time()) . " AIZAWA Hina\n";
echo " * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT\n";
echo " * @author AIZAWA Hina <hina@fetus.jp>\n";
echo " */\n";
echo "\n";
echo "declare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\n";
    echo "namespace {$namespace};\n";
}
?>

<?php foreach ($uses as $use) { ?>
use <?= $use ?>;
<?php } ?>

class <?= $className ?> extends Migration
{
<?php if (isset($traits) && $traits) { ?>
<?php foreach ($traits as $trait) { ?>
    use <?= StringHelper::basename($trait) ?>;
<?php } ?>

<?php } ?>
    public function <?= $upFuncName ?>()
    {
<?= $codeFmt($upCode ?? null, 8) ?>
    }

    public function <?= $downFuncName ?>()
    {
<?= $codeFmt($downCode ?? null, 8) ?>
    }
<?php if (isset($extraCode)) { ?>

<?= $codeFmt($extraCode, 4) ?>
<?php } ?>
}
