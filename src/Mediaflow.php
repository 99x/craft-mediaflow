<?php

namespace _99x\craftmediaflow;

use _99x\craftmediaflow\fields\MediaflowField;
use _99x\craftmediaflow\fields\MediaflowImageField;
use _99x\craftmediaflow\models\Settings;
use _99x\craftmediaflow\services\MediaflowImageService;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

/**
 * Mediaflow plugin
 *
 * @method static Mediaflow getInstance()
 * @method Settings getSettings()
 * @author 99x <alexandre.monteiro@99x.no>
 * @copyright 99x
 * @license MIT
 * @property-read MediaflowImageService $mediaflowImage
 */
class Mediaflow extends Plugin
{
    /**
     * @var Mediaflow
     */
    public static Mediaflow $plugin;
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'mediaflowImage' => MediaflowImageService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        // Register both field types using the event system
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MediaflowField::class;
                $event->types[] = MediaflowImageField::class;
            }
        );

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function () {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('mediaflow/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)
    }
}
