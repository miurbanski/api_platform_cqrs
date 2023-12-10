<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\ApiPlatform\Resource\Request\DuplicateActivity;

use Symfony\Component\Validator\Constraints as Assert;

class DuplicateActivityCategoryRequest
{
    #[Assert\Type('integer')]
    #[Assert\NotNull]
    private mixed $id;

    public function __construct(mixed $id)
    {
        $this->id = $id;
    }

    public function getId(): mixed
    {
        return $this->id;
    }
}
