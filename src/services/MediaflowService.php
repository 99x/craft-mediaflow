<?php

namespace _99x\craftmediaflow\services;

use craft\base\Component;

class MediaflowService extends Component
{
    /**
     * @var ApiService
     */
    private $apiService;

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    public function testing()
    {
        return $this->apiService->me();
    }
}
