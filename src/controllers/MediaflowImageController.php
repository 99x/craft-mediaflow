<?php

namespace _99x\craftmediaflow\controllers;

use _99x\craftmediaflow\Mediaflow;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class MediaflowImageController extends Controller
{
    protected array|bool|int $allowAnonymous = [
        'popup',
    ];

    public function actionPopup(): Response
    {
        $settings = Mediaflow::getInstance()->getSettings();
        $locale = $settings->language ?? 'en_US';

        return $this->renderTemplate('mediaflow/_components/popups/mediaflow-image-field/basic', [
            'client_id' => \craft\helpers\App::parseEnv($settings->clientId),
            'client_secret' => \craft\helpers\App::parseEnv($settings->clientSecret),
            'refresh_token' => \craft\helpers\App::parseEnv($settings->refreshToken),
            'locale' => $locale,
        ]);
    }

    public function actionCreateAsset(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        try {
            $mediaflowImageId = $this->request->getRequiredBodyParam('mediaflowImageId');
            $mediaflowImageUrl = $this->request->getRequiredBodyParam('mediaflowImageUrl');
            $filename = $this->request->getRequiredBodyParam('filename');
            $title = $this->request->getRequiredBodyParam('title');
            $volumeId = $this->request->getRequiredBodyParam('volumeId');
            $folderPath = $this->request->getRequiredBodyParam('folderPath');
            $entryUrl = $this->request->getRequiredBodyParam('entryUrl');
            $entryTitle = $this->request->getRequiredBodyParam('entryTitle');
            $altText = $this->request->getBodyParam('altText', '');

            if (!is_string($mediaflowImageId) || !is_string($mediaflowImageUrl) || !is_string($filename) || !is_string($title) || !is_numeric($volumeId) || !is_string($folderPath) || !is_string($entryUrl) || !is_string($entryTitle)) {
                throw new \RuntimeException('Invalid parameter types');
            }

            Craft::info("Creating asset from Mediaflow: {$mediaflowImageUrl}", __METHOD__);

            $plugin = Craft::$app->getPlugins()->getPlugin('mediaflow');
            if ($plugin === null) {
                throw new \RuntimeException('Mediaflow plugin not found');
            }

            $result = \_99x\craftmediaflow\Mediaflow::getInstance()->mediaflowImage->createAssetFromMediaflow(
                $mediaflowImageId,
                $mediaflowImageUrl,
                $filename,
                $title,
                (int)$volumeId,
                $folderPath,
                $entryUrl,
                $entryTitle,
                $altText
            );

            return $this->asJson([
                'success' => true,
                'elements' => [$result['id']],
                'details' => $result['details'],
            ]);
        } catch (\Exception $e) {
            Craft::error("Error creating image asset: " . $e->getMessage(), __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
