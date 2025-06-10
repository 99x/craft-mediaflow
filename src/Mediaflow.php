<?php

namespace _99x\craftmediaflow;

use _99x\craftmediaflow\fields\MediaflowField;
use _99x\craftmediaflow\models\Settings;
use _99x\craftmediaflow\services\MediaflowService;
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
 * @author 99x <info@99x.no>
 * @copyright 99x
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read MediaflowService $mediaflow
 * @property-read Settings $settings
 */
class Mediaflow extends Plugin
{
    /**
     * @var Mediaflow
     */
    public static Mediaflow $plugin;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'mediaflow' => ['class' => MediaflowService::class],
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function () {
            // $response = Mediaflow::$plugin->mediaflow->testing();
            // dd($response);
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
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = MediaflowField::class;
        });
    }
}
