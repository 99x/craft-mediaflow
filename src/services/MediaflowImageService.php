<?php

namespace _99x\craftmediaflow\services;

use _99x\craftmediaflow\jobs\PingUsageJob;
use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use yii\web\BadRequestHttpException;

class MediaflowImageService extends Component
{
    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * Maximum file size (100MB)
     */
    private const MAX_FILE_SIZE = 104857600;

    /**
     * Allowed mime types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/tiff',
        'image/bmp',
        'image/x-icon',
    ];

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    /**
     * Creates an image asset from a Mediaflow URL
     *
     * @param string $mediaflowImageId The ID of the image in Mediaflow
     * @param string $mediaflowImageUrl The URL of the image in Mediaflow
     * @param string $filename The filename to use
     * @param string $title The title for the asset
     * @param int $volumeId The volume ID to save the asset in
     * @param string $folderPath The folder path within the volume
     * @param string $entryUrl The URL of the entry
     * @param string $entryTitle The title of the entry
     * @param string $altText The alt text for the asset
     * @return array{id: int, details: array{filename: string, url: string|null, mimeType: string, size: int|null, title: string|null}} The created asset details
     * @throws BadRequestHttpException
     */
    public function createAssetFromMediaflow(
        string $mediaflowImageId,
        string $mediaflowImageUrl,
        string $filename,
        string $title,
        int $volumeId,
        string $folderPath = '',
        string $entryUrl = '',
        string $entryTitle = '',
        string $altText = '',
    ): array {
        $tempPath = null;

        try {
            // Get and validate the folder
            $folder = $this->getAndValidateFolder($volumeId);
            if ($folder->id === null) {
                throw new BadRequestHttpException('Folder ID is null');
            }

            // Create and validate temp directory
            $tempDir = $this->createTempDirectory();
            $tempPath = $tempDir . '/' . uniqid('mediaflow_', true);

            // Download and validate the image
            $fileContent = $this->downloadAndValidateImage($mediaflowImageUrl);

            // Write to temp file
            if (!$this->writeToTempFile($tempPath, $fileContent)) {
                throw new BadRequestHttpException('Could not write temporary file');
            }

            // Validate mime type
            $mimeType = FileHelper::getMimeType($tempPath);
            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                throw new BadRequestHttpException('Invalid image type: ' . $mimeType);
            }

            // Create and save the image asset
            $asset = $this->createImageAsset([
                'title' => $title,
                'tempFilePath' => $tempPath,
                'filename' => AssetsHelper::prepareAssetName($filename),
                'folderId' => $folder->id,
                'folderPath' => $folderPath,
                'volumeId' => $volumeId,
            ]);

            // Fetch the asset again to ensure custom fields are attached
            $asset = Craft::$app->getAssets()->getAssetById($asset->id);

            // Set alt text if provided
            if ($altText && $asset) {
                $altSet = false;
                // Try native property (Craft 4+)
                if (property_exists($asset, 'alt')) {
                    $asset->alt = $altText;
                    $altSet = true;
                }
                // Try custom field (for older Craft or custom setups)
                elseif (method_exists($asset, 'setFieldValue') && array_key_exists('alt', $asset->getFieldValues())) {
                    $asset->setFieldValue('alt', $altText);
                    $altSet = true;
                }
                // Only save if we set alt
                if ($altSet) {
                    Craft::$app->getElements()->saveElement($asset);
                }
            }

            // Process image if applicable
            if (str_starts_with($mimeType, 'image/')) {
                $this->processImage($asset);
            }

            // Get the image asset with its URL
            if ($asset->id === null) {
                throw new BadRequestHttpException('Asset ID is null');
            }

            $newAsset = Craft::$app->getAssets()->getAssetById($asset->id);
            if ($newAsset === null) {
                throw new BadRequestHttpException('Could not retrieve created asset');
            }

            /** @var int $assetId */
            $assetId = $newAsset->id;
            /** @var string $assetFilename */
            $assetFilename = $newAsset->filename;
            /** @var string|null $assetUrl */
            $assetUrl = $newAsset->getUrl();
            /** @var int|null $assetSize */
            $assetSize = $newAsset->size;
            /** @var string|null $assetTitle */
            $assetTitle = $newAsset->title;

            // Call pingUsage after asset creation
            $usageData = [
                'contact' => (string)(Craft::$app->user->identity->email ?? 'unknown'),
                'project' => (string)Craft::$app->name,
                'date' => date('Y-m-d H:i:s'),
                'amount' => '1',
                'description' => (string)$assetTitle,
                'types' => ['web'],
                'web' => [
                    'page' => (string)$entryUrl,
                    'pageName' => (string)$entryTitle,
                ],
            ];

            Craft::$app->queue->push(new PingUsageJob([
                'mediaflowId' => $mediaflowImageId,
                'usageData' => $usageData,
            ]));

            return [
                'id' => $assetId,
                'details' => [
                    'filename' => $assetFilename,
                    'url' => $assetUrl,
                    'mimeType' => $mimeType,
                    'size' => $assetSize,
                    'title' => $assetTitle,
                ],
            ];
        } finally {
            // Always cleanup temp file
            $this->cleanupTempFile($tempPath);
        }
    }

    /**
     * Gets and validates a folder
     */
    private function getAndValidateFolder(int $volumeId): \craft\models\VolumeFolder
    {
        $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);
        if (!$volume) {
            throw new BadRequestHttpException('Invalid volume ID');
        }

        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volumeId);
        if (!$folder) {
            throw new BadRequestHttpException('Could not find root folder for volume');
        }
        return $folder;
    }

    /**
     * Creates a temporary directory
     */
    private function createTempDirectory(): string
    {
        $tempDir = Craft::$app->getPath()->getTempPath() . '/mediaflow-image';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new BadRequestHttpException('Could not create temp directory');
        }
        return $tempDir;
    }

    /**
     * Downloads and validates an image from a URL
     */
    private function downloadAndValidateImage(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Craft CMS/Mediaflow Image Plugin',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $headers = get_headers($url, true, $context);
        if ($headers === false) {
            throw new BadRequestHttpException('Could not fetch headers from URL');
        }

        $contentLength = $headers['Content-Length'] ?? null;
        if ($contentLength && (int)$contentLength > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException('Image size exceeds maximum allowed size');
        }

        $fileContent = file_get_contents($url, false, $context);
        if ($fileContent === false) {
            throw new BadRequestHttpException('Could not download image from Mediaflow');
        }

        return $fileContent;
    }

    /**
     * Writes content to a temporary file
     */
    private function writeToTempFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Creates an image asset
     *
     * @param array{title: string, tempFilePath: string, filename: string, folderId: int, folderPath: string, volumeId: int} $config The configuration for the asset
     * @return Asset The created asset
     * @throws BadRequestHttpException
     */
    private function createImageAsset(array $config): Asset
    {
        //Add timestamp to filename to avoid filename conflicts
        $filename = pathinfo($config['filename'], PATHINFO_FILENAME) . '-' . time() . '.' . pathinfo($config['filename'], PATHINFO_EXTENSION);

        $asset = new Asset();
        $asset->title = $config['title'];
        $asset->tempFilePath = $config['tempFilePath'];
        $asset->filename = $filename;
        $asset->newFolderId = $config['folderId'];
        $asset->folderPath = $config['folderPath'];
        $asset->volumeId = $config['volumeId'];
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            throw new BadRequestHttpException(implode(', ', $asset->getFirstErrors()));
        }

        return $asset;
    }

    /**
     * Processes an image asset
     */
    private function processImage(Asset $asset): void
    {
        $transforms = [
            'thumbnail' => [
                'mode' => 'fit',
                'width' => 300,
                'height' => 300,
                'quality' => 85,
                'format' => 'webp',
            ],
            'preview' => [
                'mode' => 'fit',
                'width' => 800,
                'height' => 600,
                'quality' => 85,
                'format' => 'webp',
            ],
            'large' => [
                'mode' => 'fit',
                'width' => 1920,
                'height' => 1080,
                'quality' => 85,
                'format' => 'webp',
            ],
        ];

        foreach ($transforms as $transformName => $transform) {
            try {
                $asset->setTransform($transform);
            } catch (\Exception $e) {
                Craft::error("Failed to create {$transformName} transform: " . $e->getMessage(), __METHOD__);
            }
        }
    }

    /**
     * Cleans up a temporary file
     */
    private function cleanupTempFile(?string $path): void
    {
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Ping Mediaflow File Usage
     */
    public function pingUsage(string $mediaflowId, array $usageData): void
    {
        $this->apiService->pingUsage($mediaflowId, $usageData);
    }
}
