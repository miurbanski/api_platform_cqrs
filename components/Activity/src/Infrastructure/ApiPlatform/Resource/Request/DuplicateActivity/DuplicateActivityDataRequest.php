<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\ApiPlatform\Resource\Request\DuplicateActivity;

use Symfony\Component\Validator\Constraints as Assert;

class DuplicateActivityDataRequest
{
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private mixed $subtasks;
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private mixed $comments;
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private mixed $customFields;
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private mixed $linkToOriginal;

    public function __construct(mixed $subtasks, mixed $comments, mixed $customFields, mixed $linkToOriginal)
    {
        $this->subtasks = $subtasks;
        $this->comments = $comments;
        $this->customFields = $customFields;
        $this->linkToOriginal = $linkToOriginal;
    }

    public function getSubtasks(): mixed
    {
        return $this->subtasks;
    }

    public function getComments(): mixed
    {
        return $this->comments;
    }

    public function getCustomFields(): mixed
    {
        return $this->customFields;
    }

    public function getLinkToOriginal(): mixed
    {
        return $this->linkToOriginal;
    }
}
