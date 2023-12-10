<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\ApiPlatform\Resource\Request\DuplicateActivity;

use Symfony\Component\Validator\Constraints as Assert;

class DuplicateActivityRequest
{
    #[Assert\NotBlank]
    private mixed $name;
    private ?DuplicateActivityCategoryRequest $category;
    #[Assert\Type('string')]
    private mixed $description;
    #[Assert\NotNull]
    private ?DuplicateActivityDataRequest $data;

    public function __construct(
        mixed $name = null,
        DuplicateActivityCategoryRequest $category = null,
        mixed $description = null,
        DuplicateActivityDataRequest $data = null
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->description = $description;
        $this->data = $data;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCategory(): ?DuplicateActivityCategoryRequest
    {
        return $this->category;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getData(): ?DuplicateActivityDataRequest
    {
        return $this->data;
    }
}
