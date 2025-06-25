<?php

namespace _99x\craftmediaflow\assetbundles\mediaflowimage;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MediaflowImageAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@_99x/craftmediaflow/assets/mediaflowimage";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/MediaflowImageField.js',
        ];

        $this->css = [
            'css/MediaflowImageField.css',
        ];

        parent::init();
    }
}
