<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Activity\Application\Command\DuplicateActivity;
use App\Activity\Application\Repository\ActivityRepositoryInterface;

class DuplicateActivityDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private ActivityRepositoryInterface $activityRepository;

    public function __construct(ActivityRepositoryInterface $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        return $this->activityRepository->find($id);
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return DuplicateActivity::class === $resourceClass;
    }
}
