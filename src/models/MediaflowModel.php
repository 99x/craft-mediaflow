<?php

namespace _99x\craftmediaflow\models;

use craft\base\Model;
use craft\base\Serializable;
use craft\helpers\Json;

/**
 * Mediaflow settings
 */
class MediaflowModel extends Model implements Serializable
{
    protected mixed $_json = null;

    public function __construct($json, $config = [])
    {
        $this->_json = Json::decodeIfJson($json, true);
        parent::__construct($config);
    }

    public function getUrl(): ?string
    {
        return $this->_json["url"] ?? null;
    }

    public function getName($lang = null): ?string
    {
        return $this->_json["name"] ?? null;
    }

    public function getFilename(): ?string
    {
        return $this->_json["filename"] ?? null;
    }

    public function getMediaId(): ?string
    {
        return $this->_json["mediaId"] ?? null;
    }

    public function getId(): ?string
    {
        return $this->_json["id"] ?? null;
    }

    public function getFolderId(): ?string
    {
        return $this->_json["folderId"] ?? null;
    }

    public function getDescription(): ?string
    {
        return $this->_json["description"] ?? null;
    }

    public function getBasetype(): ?string
    {
        return $this->_json["basetype"] ?? null;
    }

    public function getFiletype(): ?string
    {
        return $this->_json["filetype"] ?? null;
    }

    public function getEmbedMethod(): ?string
    {
        return $this->_json["embedMethod"] ?? null;
    }

    public function getAutoPlay(): ?bool
    {
        return $this->_json["autoPlay"] ?? null;
    }

    public function getStartTime(): ?int
    {
        return $this->_json["startTime"] ?? null;
    }

    public function getWidth(): ?string
    {
        return $this->_json["width"] ?? null;
    }

    public function getHeight(): ?string
    {
        return $this->_json["height"] ?? null;
    }

    public function getPhotographer(): ?string
    {
        return $this->_json["photographer"] ?? null;
    }

    public function getPoster(): ?string
    {
        return $this->_json["poster"] ?? null;
    }

    public function getAltText(): ?string
    {
        return $this->_json["altText"] ?? null;
    }

    public function getEmbedCode(): ?string
    {
        return $this->_json["embedCode"] ?? null;
    }

    public function getRaw(): ?string
    {
        return Json::encode($this->_json);
    }

    public function getJson(): mixed
    {
        return $this->_json;
    }

    public function getData(): ?string
    {
        return Json::encode($this->_json);
    }

    /**
     * Returns the object's serialized value.
     *
     * @return string|null The serialized value
     */
    public function serialize(): ?string
    {
        return Json::encode($this->_json);
    }

    public function __toString(): string
    {
        return $this->getUrl() ?? "";
    }
}
