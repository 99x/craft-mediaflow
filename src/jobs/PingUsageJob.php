<?php

namespace _99x\craftmediaflow\jobs;

use _99x\craftmediaflow\Mediaflow;
use craft\queue\BaseJob;

class PingUsageJob extends BaseJob
{
    public string $mediaflowId;
    public array $usageData;

    public function execute($queue): void
    {
        Mediaflow::getInstance()->mediaflowImage->pingUsage($this->mediaflowId, $this->usageData);
    }

    protected function defaultDescription(): string
    {
        return 'Ping Mediaflow File (' . $this->mediaflowId . ') Usage';
    }
}
