<?php

namespace _99x\craftmediaflow\assetbundles\mediaflow;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MediaflowAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@_99x/craftmediaflow/assetbundles/mediaflow/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Mediaflow.js',
        ];

        $this->css = [
            'css/Mediaflow.css',
        ];

        parent::init();
    }
}
