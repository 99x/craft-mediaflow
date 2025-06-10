<?php

namespace _99x\craftmediaflow\fields;

use _99x\craftmediaflow\assetbundles\mediaflow\MediaflowAsset;
use _99x\craftmediaflow\Mediaflow;
use _99x\craftmediaflow\models\MediaflowModel;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\db\ExpressionInterface;
use yii\db\Schema;

/**
 * Mediaflow Field field type
 */
class MediaflowField extends Field
{
    public string $locale = 'en_US';
    public string $limitFileType = 'jpg,jpeg,tif,tiff,png,mp4';
    public bool $noCropButton = false;
    public bool $allowSelectFormat = true;
    public bool $setAltText = false;
    public bool $permanentURL = false;
    public bool $allowIframeVideo = true;
    public bool $allowJSVideo = false;

    public static function displayName(): string
    {
        return Craft::t('mediaflow', 'Mediaflow Field');
    }

    public static function icon(): string
    {
        return 'photo';
    }

    public static function phpType(): string
    {
        return 'mixed';
    }

    public static function dbType(): array|string|null
    {
        // Replace with the appropriate data type this field will store in the database,
        // or `null` if the field is managing its own data storage.
        return Schema::TYPE_TEXT;
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            // ...
        ]);
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'mediaflow/_components/fields/settings',
            [
                'field' => $this,
            ]
        );
    }


    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof MediaflowModel) {
            return [$value];
        }
        // its already an array of models
        if (is_array($value) && array_is_list($value)) {

            // Support JSON strings in array
            foreach ($value as $k => $val) {
                if (is_string($val) && Json::isJsonObject($val)) {
                    $value[$k] = new MediaflowModel(Json::decode($val));
                }
            }

            return array_filter($value, fn($image) => $image instanceof MediaflowModel);
        }

        if (is_string($value) && Json::isJsonObject($value)) {
            $json = Json::decode($value);
            if (array_is_list($json)) {
                $filtered = array_map(fn($image) => new MediaflowModel($image), array_filter($json, fn($image) => !empty($image)));
                return $filtered;
            }
        }

        return [new MediaflowModel($value)];
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
    {
        // If it's "arrayable", convert to array
        if (is_array($value)) {
            return array_map(fn($image) => $image->serialize(), $value);
        }

        return parent::serializeValue($value, $element);
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $settings = Mediaflow::$plugin->getSettings();

        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(MediaflowAsset::class);

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        $popupHtml = Craft::$app->getView()->renderTemplate(
            'mediaflow/_components/popups/default',
            [
                'client_id' => App::parseEnv($settings->clientId),
                'client_secret' => App::parseEnv($settings->clientSecret),
                'refresh_token' => App::parseEnv($settings->refreshToken),
                'namespace' => $namespacedId,
                'locale' => $this->locale,
                'limitFileType' => $this->limitFileType,
                'noCropButton' => $this->noCropButton,
                'allowSelectFormat' => $this->allowSelectFormat,
                'setAltText' => $this->setAltText,
                'permanentURL' => $this->permanentURL,
                'allowIframeVideo' => $this->allowIframeVideo,
                'allowJSVideo' => $this->allowJSVideo,
            ]
        );

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            'popupHtml' => $popupHtml,
        ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new Craft.MediaflowField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'mediaflow/_components/fields/input',
            [
                'name' => $this->handle,
                'id' => $id,
                'namespace' => $namespacedId,
                'value' => $value,
                'field' => $this,
            ]
        );
    }

    public function getElementValidationRules(): array
    {
        return [];
    }

    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return StringHelper::toString($value, ' ');
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

    public static function queryCondition(
        array $instances,
        mixed $value,
        array &$params,
    ): ExpressionInterface|array|string|false|null {
        return parent::queryCondition($instances, $value, $params);
    }
}
