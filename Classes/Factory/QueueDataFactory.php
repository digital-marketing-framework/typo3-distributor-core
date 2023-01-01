<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Factory;

use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactory as OriginalQueueDataFactory;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSetInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;

class QueueDataFactory extends OriginalQueueDataFactory
{
    const KEY_PID = 'pid';
    const DEFAULT_PID = 0;

    protected function createJob(): JobInterface
    {
        return new Job();
    }

    public function convertSubmissionToJob(SubmissionDataSetInterface $submission, string $route, int $pass, int $status = QueueInterface::STATUS_QUEUED): JobInterface
    {
        /** @var Job $job */
        $job = parent::convertSubmissionToJob($submission, $route, $pass, $status);
        $job->setPid($submission->getConfiguration()->getWithRoutePassOverride(static::KEY_PID, $route, $pass, static::DEFAULT_PID));
        return $job;
    }
}
