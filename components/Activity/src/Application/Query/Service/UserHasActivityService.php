<?php

declare(strict_types=1);

namespace App\Activity\Application\Query\Service;

use App\Activity\Application\Repository\ActivityRepositoryInterface;

class UserHasActivityService
{
    private ActivityRepositoryInterface $activityRepository;

    public function __construct(ActivityRepositoryInterface $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function has(int $activityId): bool
    {
        $activity = $this->activityRepository->find($activityId);
        return $activity !== null;
    }
}
