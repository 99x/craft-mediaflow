<?php

namespace _99x\craftmediaflow\models;

use craft\base\Model;

/**
 * Mediaflow settings
 */
class Settings extends Model
{
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $refreshToken = null;
    public ?string $language = null;
}
