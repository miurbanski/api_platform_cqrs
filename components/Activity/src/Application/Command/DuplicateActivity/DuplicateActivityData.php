<?php

declare(strict_types=1);

namespace App\Activity\Application\Command\DuplicateActivity;

class DuplicateActivityData
{
    private bool $subtasks;
    private bool $comments;
    private bool $customFields;
    private bool $linkToOriginal;

    public function __construct(bool $subtasks, bool $comments, bool $customFields, bool $linkToOriginal)
    {
        $this->subtasks = $subtasks;
        $this->comments = $comments;
        $this->customFields = $customFields;
        $this->linkToOriginal = $linkToOriginal;
    }

    public function getSubtasks(): bool
    {
        return $this->subtasks;
    }

    public function getComments(): bool
    {
        return $this->comments;
    }

    public function getCustomFields(): bool
    {
        return $this->customFields;
    }

    public function isLinkToOriginal(): bool
    {
        return $this->linkToOriginal;
    }
}
