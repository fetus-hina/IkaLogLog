<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\widgets;

use Yii;
use app\assets\UserMiniinfoAsset;
use app\components\i18n\Formatter;
use yii\base\Widget;
use yii\bootstrap\BootstrapAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class MiniinfoData extends Widget
{
    public $label;
    public $labelTitle;
    public $labelFormat = 'text';
    public $value;
    public $valueTitle;
    public $valueFormat = 'text';
    public $options = [
        'class' => 'col-xs-4',
    ];
    public $labelOptions = [
        'class' => 'user-label auto-tooltip',
    ];
    public $valueOptions = [
        'class' => 'user-number text-right auto-tooltip',
    ];
    public $formatter;

    public function init()
    {
        parent::init();
        if ($this->formatter === null) {
            $this->formatter = Formatter::class;
        }
        if (is_array($this->formatter) || is_string($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        if (!($this->formatter instanceof \yii\i18n\Formatter)) {
            $this->formatter = Yii::createObject(Formatter::class);
        }
    }

    public function run(): string
    {
        BootstrapAsset::register($this->view);
        UserMiniinfoAsset::register($this->view);

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        if (!isset($options['id'])) {
            $options['id'] = $this->id;
        }
        return Html::tag(
            $tag,
            $this->renderHeader() . $this->renderValue(),
            $options
        );
    }

    protected function renderHeader(): string
    {
        return $this->renderElement(
            $this->label,
            $this->labelTitle,
            $this->labelFormat,
            $this->labelOptions
        );
    }

    protected function renderValue(): string
    {
        return $this->renderElement(
            $this->value,
            $this->valueTitle,
            $this->valueFormat,
            $this->valueOptions
        );
    }

    protected function renderElement($value, $valueTitle, string $format, array $options): string
    {
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        if (!isset($options['title']) && $valueTitle != '') {
            $options['title'] = $valueTitle;
        }

        $f = $this->formatter;
        return Html::tag(
            $tag,
            $f->format($value, $format),
            $options
        );
    }
}
