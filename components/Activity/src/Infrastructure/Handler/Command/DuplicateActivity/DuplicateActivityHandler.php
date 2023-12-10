<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Handler\Command\DuplicateActivity;

use App\Activity\Application\Command\CreateActivity\CreateActivity;
use App\Activity\Application\Command\DuplicateActivity;
use App\Activity\Application\Service\DuplicateActivity\DuplicateActivityService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DuplicateActivityHandler implements MessageHandlerInterface
{
    private DuplicateActivityService $duplicateActivityService;

    public function __construct(DuplicateActivityService $duplicateActivityService)
    {
        $this->duplicateActivityService = $duplicateActivityService;
    }

    public function __invoke(DuplicateActivity $command): void
    {
        $this->duplicateActivityService->duplicate($command);
    }
}
