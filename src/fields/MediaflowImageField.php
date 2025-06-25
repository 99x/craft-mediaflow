<?php

namespace _99x\craftmediaflow\fields;

use _99x\craftmediaflow\assetbundles\mediaflowimage\MediaflowImageAsset;
use Craft;
use craft\base\ElementInterface;
use craft\fields\Assets;

/**
 * Mediaflow Image Field Type
 */
class MediaflowImageField extends Assets
{
    /**
     * @var string|null The view mode of the field ('list' or 'large')
     */
    public ?string $viewMode = 'list';

    /**
     * @var string|null The source that files should be uploaded to by default
     */
    public ?string $defaultUploadLocationSource = null;

    /**
     * @var string|null The subpath that files should be uploaded to by default
     */
    public ?string $defaultUploadLocationSubpath = null;

    /**
     * @var string|null The transform to use for previews
     */
    public ?string $previewTransform = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Mediaflow Image';
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $html = parent::getInputHtml($value, $element);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(MediaflowImageAsset::class);

        // Prepare button data
        $volumeId = '';
        if ($this->defaultUploadLocationSource && str_starts_with($this->defaultUploadLocationSource, 'volume:')) {
            $volumeUid = substr($this->defaultUploadLocationSource, 7);
            $volume = Craft::$app->getVolumes()->getVolumeByUid($volumeUid);
            if ($volume) {
                $volumeId = $volume->id;
            }
        }
        $buttonData = [
            'fieldId' => $this->id,
            'fieldHandle' => $this->handle,
            'entryUrl' => $element ? $element->getUrl() : '',
            'entryTitle' => $element ? $element->title : '',
            'popupUrl' => $this->getPopupUrl(),
            'volumeId' => $volumeId,
            'folderPath' => $this->defaultUploadLocationSubpath ?? '',
            'maxRelations' => $this->maxRelations,
            'buttonLabel' => Craft::t('mediaflow', 'selectFromMediaflow'),
        ];
        $jsonButtonData = json_encode($buttonData);

        // Use the field's ID for robust selection and sync visibility with 'Add an asset' button
        $js = <<<JS
(function() {
    var data = $jsonButtonData;
    var flexDivs = document.querySelectorAll('#fields-{$this->handle} .flex');
    flexDivs.forEach(function(flexDiv) {
        if (!flexDiv.querySelector('.mediaflow-image-select')) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn submit mediaflow-image-select ml-2';
            btn.textContent = data.buttonLabel;
            btn.setAttribute('data-field-id', data.fieldId);
            btn.setAttribute('data-field-handle', data.fieldHandle);
            btn.setAttribute('data-entry-url', data.entryUrl);
            btn.setAttribute('data-entry-title', data.entryTitle);
            btn.setAttribute('data-popup-url', data.popupUrl);
            btn.setAttribute('data-volume-id', data.volumeId);
            btn.setAttribute('data-folder-path', data.folderPath);
            btn.setAttribute('data-max-relations', data.maxRelations);
            flexDiv.appendChild(btn);

            // Sync visibility with native 'Add an asset' button
            var addBtn = flexDiv.querySelector('.btn.add');
            if (addBtn) {
                // Initial state
                btn.style.display = window.getComputedStyle(addBtn).display;
                // Observe changes
                var observer = new MutationObserver(function() {
                    btn.style.display = window.getComputedStyle(addBtn).display;
                });
                observer.observe(addBtn, { attributes: true, attributeFilter: ['style', 'class'] });
            }
        }
    });
})();
JS;
        $view->registerJs($js, $view::POS_END);

        // Also register the MediaflowImageField JS for popup handling
        $view->registerJs(
            "new MediaflowImageField('{$this->id}', '{$this->handle}', '{$this->getCreateAssetUrl()}');",
            $view::POS_END
        );

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    protected function inputTemplateVariables(mixed $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);
        $variables['fieldId'] = $this->id;
        return $variables;
    }

    private function getPopupUrl(): string
    {
        return \craft\helpers\UrlHelper::actionUrl('mediaflow/mediaflow-image/popup');
    }

    private function getCreateAssetUrl(): string
    {
        return \craft\helpers\UrlHelper::actionUrl('mediaflow/mediaflow-image/create-asset');
    }
}
