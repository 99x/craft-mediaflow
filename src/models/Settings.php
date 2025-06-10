<?php

namespace _99x\craftmediaflow\models;

use craft\base\Model;

/**
 * Mediaflow settings
 */
class Settings extends Model
{
    /**
     * @var string
     */
    public string $clientId = '';

    /**
     * @var string
     */
    public string $clientSecret = '';

    /**
     * @var string
     */
    public string $refreshToken = '';

    /**
     * @var string
     */
    public string $language = 'no';
}
