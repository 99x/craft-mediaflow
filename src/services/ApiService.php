<?php

namespace _99x\craftmediaflow\services;

use _99x\craftmediaflow\Mediaflow;
use _99x\craftmediaflow\models\Settings;
use Craft;
use craft\base\Component;
use craft\helpers\App;
use GuzzleHttp\Client;

class ApiService extends Component
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var string
     */
    private $accessToken;

    public function __construct()
    {
        $this->settings = Mediaflow::getInstance()->getSettings();
    }

    /**
     * Example helper method to get the logged user
     */
    public function me()
    {
        return $this->request('GET', '/1/me');
    }

    /**
     * Get a temporary access token using authorization code
     */
    private function accessToken()
    {
        if (empty($this->settings->clientId) || empty($this->settings->clientSecret) || empty($this->settings->refreshToken)) {
            return null;
        }

        $client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(App::parseEnv($this->settings->clientId) . ':' . App::parseEnv($this->settings->clientSecret)),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $response = $client->request('POST', 'https://accounts.mediaflow.com/oauth2/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => App::parseEnv($this->settings->clientId),
                'refresh_token' => App::parseEnv($this->settings->refreshToken),
                'redirect_uri' => Craft::$app->request->getAbsoluteUrl(),
            ],
        ]);

        if ($response->getStatusCode() == 200 && $response->hasHeader('Content-Length')) {
            $body = $response->getBody()->getContents();
            $body = json_decode($body, true);

            if (isset($body['access_token'])) {
                $this->accessToken = $body['access_token'];
                return $body['access_token'];
            }
        }

        return null;
    }

    /**
     * Base API call helper
     *
     * @param string $method default GET
     * @param string $action The target endpoint
     * @param array $params Params to be included in the call
     * @return mixed
     **/
    private function request(string $method = 'GET', string $action = '', array $params = []): mixed
    {
        if (empty($this->accessToken)) {
            $this->accessToken = $this->accessToken();
        }

        $client = new Client([
            'base_uri' => 'https://api.mediaflow.com',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Api-Version' => '1.0',
            ],
        ]);

        $response = $client->request($method, $action, $params);

        if ($response->getStatusCode() == 200 && $response->hasHeader('Content-Length')) {
            return $response->getBody()->getContents();
        }

        return null;
    }
}
