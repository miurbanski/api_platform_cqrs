<?php

declare(strict_types=1);

namespace App\Activity\Application\Service\DuplicateActivity;

use App\Activity\Application\Command\DuplicateActivity;
use App\Activity\Application\Entity\Activity;
use App\Activity\Application\Repository\ActivityRepositoryInterface;
use App\Activity\Application\Status\Status;
use App\Application\CurrentUserInterface;
use App\Category\Application\Entity\Category;
use App\Category\Application\Repository\CategoryRepositoryInterface;
use App\CustomField\Application\Repository\CustomFieldRepositoryInterface;
use App\Entity\ActivityComment;
use App\Entity\ActivityCustomFields;
use App\Entity\ActivityProtocol;
use App\Entity\ActivityProtocolItem;
use App\Entity\ActivityProtocolSections;
use App\Entity\ActivitySettlementProtocol;
use App\Entity\ActivitySettlementProtocolItem;
use App\Entity\ProtocolsItem;
use App\Entity\SettlementProtocol;
use App\EntityManager\EntityManagerInterface;
use App\Protocol\Application\Entity\Protocols;
use App\Protocol\Infrastructure\Doctrine\Repository\ProtocolsRepository;
use App\Repository\SettlementProtocol\SettlementProtocolRepository;

class DuplicateActivityService
{
    private CategoryRepositoryInterface $categoryRepository;
    private CustomFieldRepositoryInterface $customFieldsRepository;
    private CurrentUserInterface $currentUser;
    private EntityManagerInterface $entityManager;
    private ActivityRepositoryInterface $activityRepository;
    private SettlementProtocolRepository $settlementProtocolRepository;
    private ProtocolsRepository $protocolsRepository;

    public function __construct(
        CategoryRepositoryInterface    $categoryRepository,
        CustomFieldRepositoryInterface $customFieldsRepository,
        CurrentUserInterface           $currentUser,
        EntityManagerInterface         $entityManager,
        ActivityRepositoryInterface    $activityRepository,
        SettlementProtocolRepository   $settlementProtocolRepository,
        ProtocolsRepository            $protocolsRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->customFieldsRepository = $customFieldsRepository;
        $this->currentUser = $currentUser;
        $this->entityManager = $entityManager;
        $this->activityRepository = $activityRepository;
        $this->settlementProtocolRepository = $settlementProtocolRepository;
        $this->protocolsRepository = $protocolsRepository;
    }

    public function duplicate(DuplicateActivity $command): void
    {
        /** @var $activity Activity */
        $activity = $this->activityRepository->find($command->getId());
        $newActivity = new Activity();
        $activityName = $activity->getName();
        if ($command->getName()) {
            $activityName = $this->prepareTemplateString($command->getName(), $activity->getName() ?? $command->getName());
        }
        $newActivity->setName($activityName);
        $newActivity->setAddress($activity->getAddress());
        $newActivity->setLocation($activity->getLocation());
        $description = $activity->getDescription();
        if ($command->getDescription()) {
            $description = $this->prepareTemplateString($command->getDescription(), $activity->getDescription() ?? $command->getDescription());
        }
        $newActivity->setDescription($description);
        $newActivity->setStatus(Status::STATUS_NEW);
        $newActivity->setCity($activity->getCity());
        $newActivity->setPcode($activity->getPcode());
        $newActivity->setCountry($activity->getCountry());
        $newActivity->setDuration($activity->getDuration());
        $newActivity->setPosition($activity->getPosition());
        $newActivity->setDueDate($activity->getDueDate());
        $newActivity->setFlatNo($activity->getFlatNo());
        $newActivity->setGroupName($activity->getGroupName());
        $newActivity->setGroupPosition($activity->getGroupPosition());
        $newActivity->setRealizationDate($activity->getRealizationDate());
        $newActivity->setRealizationDateClose($activity->getRealizationDateClose());
        $newActivity->setRealizationStartdate($activity->getRealizationStartdate());
        $newActivity->setDueTime($activity->getDueTime());
        $newActivity->setWorkStartdate($activity->getWorkStartdate());
        $newActivity->setIsAllDay($activity->getIsAllDay());
        $newActivity->setOwner($this->currentUser->getUser());
        $newActivity->setOwnerName($this->currentUser->getUserEmail());
        $newActivity->setType($activity->getType());
        $newActivity->setObjects($activity->getObjects());
        if ($command->getData()->isLinkToOriginal()) {
            $newActivity->setSourceActivity($activity);
        }
        $newActivity->setCategory($activity->getCategory());
        $category = $this->getCategory($command);
        if ($category) {
            $newActivity->setCategory($category);
        }

        $this->addTags($activity, $newActivity);
        $this->addEquipments($activity, $newActivity);
        $this->addCustomFields($command, $activity, $newActivity);
        $this->addComments($activity, $newActivity);
        $this->addSettlementProtocol($command, $activity, $newActivity);
        $this->addProtocols($command, $activity, $newActivity);

        $this->addSubtasks($command, $activity, $newActivity);
        $this->entityManager->save($newActivity);

        $command->setId($newActivity->getId());
    }

    private function prepareTemplateString(string $inputString, string $replacement): string
    {
        if (!preg_match('/\[(.*?)\]/', $inputString)) {
            return $inputString;
        }
        $placeholders = ['[activity_name]', '[activity_description]'];

        foreach ($placeholders as $placeholder) {
            if (strpos($inputString, $placeholder) !== false) {
                $inputString = str_replace($placeholder, $replacement, $inputString);
                $inputString = str_replace(['[', ']'], '', $inputString);
            }
        }
        return $inputString;
    }

    private function prepareSubtask(DuplicateActivity $command, Activity $subtask): Activity
    {
        $newSubtask = new Activity();
        $activityName = $subtask->getName();
        $templatedNameString = $this->prepareTemplateString($command->getName(), $subtask->getName());
        if ($command->getName() && !empty($templatedNameString)) {
            $activityName = $templatedNameString;
        }
        $newSubtask->setName($activityName);
        $newSubtask->setAddress($subtask->getAddress());
        $newSubtask->setLocation($subtask->getLocation());
        $description = $subtask->getDescription();
        $templatedDescriptionString = $this->prepareTemplateString($command->getDescription(), $description);
        if ($command->getDescription() && !empty($templatedDescriptionString)) {
            $description = $templatedDescriptionString;
        }
        $newSubtask->setDescription($description);
        $newSubtask->setStatus($subtask->getStatus());
        $newSubtask->setCity($subtask->getCity());
        $newSubtask->setPcode($subtask->getPcode());
        $newSubtask->setCountry($subtask->getCountry());
        $newSubtask->setDuration($subtask->getDuration());
        $newSubtask->setPosition($subtask->getPosition());
        $newSubtask->setDueDate($subtask->getDueDate());
        $newSubtask->setFlatNo($subtask->getFlatNo());
        $newSubtask->setGroupName($subtask->getGroupName());
        $newSubtask->setGroupPosition($subtask->getGroupPosition());
        $newSubtask->setRealizationDate($subtask->getRealizationDate());
        $newSubtask->setRealizationDateClose($subtask->getRealizationDateClose());
        $newSubtask->setRealizationStartdate($subtask->getRealizationStartdate());
        $newSubtask->setDueTime($subtask->getDueTime());
        $newSubtask->setWorkStartdate($subtask->getWorkStartdate());
        $newSubtask->setIsAllDay($subtask->getIsAllDay());
        $newSubtask->setOwner($this->currentUser->getUser());
        $newSubtask->setOwnerName($this->currentUser->getUserEmail());
        $newSubtask->setType($subtask->getType());
        $category = $this->getCategory($command);
        $newSubtask->setCategory($subtask->getCategory());
        if ($category) {
            $newSubtask->setCategory($category);
        }

        return $newSubtask;
    }

    private function addCustomFields(DuplicateActivity $command, Activity $activity, Activity $newActivity): void
    {
        if (!$command->getData()->getCustomFields()) {
            return;
        }

        $category = $command->getCategory();
        $activityCustomFields = $activity->getActivityCustomFields();
        if ($category) {
            $customFields = $this->customFieldsRepository->findAllByCategory($category->getId());
            foreach ($customFields as $customField) {
                $activityCustomField = new ActivityCustomFields();
                $activityCustomField->setValue($customField->getValue());
                $activityCustomField->setCustomFields($customField);
                $newActivity->addActivityCustomField($activityCustomField);
            }
        } else {
            foreach ($activityCustomFields as $customField) {
                $activityCustomField = new ActivityCustomFields();
                $activityCustomField->setValue($customField->getValue());
                if ($customField->getCustomFields()) {
                    $customField = $this->customFieldsRepository->find($customField->getCustomFields()->getId());
                    $activityCustomField->setCustomFields($customField);
                }
                $newActivity->addActivityCustomField($activityCustomField);
            }
        }
    }

    private function addSettlementProtocol(DuplicateActivity $command, Activity $activity, Activity $newActivity): void
    {
        $activitySettlementProtocols = $activity->getActivitySettlementProtocols();

        /** @var ActivitySettlementProtocol $activitySettlementProtocol */
        foreach ($activitySettlementProtocols as $activitySettlementProtocol) {
            $newActivitySettlementProtocol = $this->prepareActivitySettlementProtocol($activity, $newActivity, $activitySettlementProtocol->getSettlementProtocol());
            foreach ($activitySettlementProtocol->getActivitySettlementProtocolItems() as $activitySettlementProtocolItem) {
                $newActivitySettlementProtocol->addActivitySettlementProtocolItem($this->prepareActivitySettlementProtocolItem($newActivitySettlementProtocol, $activitySettlementProtocolItem));
            }
            $newActivity->addActivitySettlementProtocol($newActivitySettlementProtocol);
        }
    }

    private function addProtocols(DuplicateActivity $command, Activity $activity, $newActivity): void
    {
        $activityProtocols = $activity->getActivityProtocols();

        foreach ($activityProtocols as $protocol) {
            $newActivityProtocol = $this->prepareActivityProtocol($protocol, $newActivity);
            foreach ($protocol->getActivityProtocolSections() as $section) {
                $newActivityProtocolSection = $this->prepareActivityProtocolSections($section);
                foreach ($section->getActivityProtocolItems() as $protocolItem) {
                    $newActivityProtocolItem = $this->prepareActivityProtocolSectionItem($protocolItem);
                    $newActivityProtocolSection->addActivityProtocolItem($newActivityProtocolItem);
                }
                $newActivityProtocol->addActivityProtocolSection($newActivityProtocolSection);
            }
            $newActivity->addActivityProtocol($newActivityProtocol);
        }
    }

    private function prepareActivityProtocol(ActivityProtocol $protocol, Activity $newActivity): ActivityProtocol
    {
        $newActivityProtocol = new ActivityProtocol();
        $newActivityProtocol->setCompany($protocol->getCompany());
        $newActivityProtocol->setActivity($newActivity);
        $newActivityProtocol->setName($protocol->getName());
        $newActivityProtocol->setSignedRequiredClient($protocol->getSignedRequiredClient());
        $newActivityProtocol->setSignedRequiredWorker($protocol->getSignedRequiredWorker());
        $newActivityProtocol->setIsSigned($protocol->getIsSigned());
        $newActivityProtocol->setProtocol($protocol->getProtocol());
        $newActivityProtocol->setPrintOnPdfProtocol($protocol->getPrintOnPdfProtocol());
        $newActivityProtocol->setPosition($protocol->getPosition());
        $newActivityProtocol->setFinalProtocol($protocol->getFinalProtocol());

        return $newActivityProtocol;
    }

    private function prepareActivityProtocolSections(ActivityProtocolSections $section): ActivityProtocolSections
    {
        $newActivityProtocolSection = new ActivityProtocolSections();
        $newActivityProtocolSection->setCompany($section->getCompany());
        $newActivityProtocolSection->setProtocolSection($section->getProtocolSection());
        $newActivityProtocolSection->setName($section->getName());
        $newActivityProtocolSection->setRequired($section->getRequired());
        $newActivityProtocolSection->setForEquipment($section->getForEquipment());
        $newActivityProtocolSection->setEquipmentId($section->getEquipmentId());
        $newActivityProtocolSection->setHiddenItemdefault($section->getHiddenItemdefault());
        $newActivityProtocolSection->setPosition($section->getPosition());

        return $newActivityProtocolSection;
    }

    private function prepareActivityProtocolSectionItem(ActivityProtocolItem $protocolItem): ActivityProtocolItem
    {
        $newActivityProtocolItem = new ActivityProtocolItem();
        $newActivityProtocolItem->setProtocolItem($protocolItem->getProtocolItem());
        $newActivityProtocolItem->setName($protocolItem->getName());
        $newActivityProtocolItem->setValue($protocolItem->getValue());
        $newActivityProtocolItem->setType($protocolItem->getType());
        $newActivityProtocolItem->setUnit($protocolItem->getUnit());
        $newActivityProtocolItem->setPrice($protocolItem->getPrice());
        $newActivityProtocolItem->setPosition($protocolItem->getPosition());
        $newActivityProtocolItem->setFactor($protocolItem->getFactor());
        $newActivityProtocolItem->setValues($protocolItem->getValues());
        $newActivityProtocolItem->setDescription($protocolItem->getDescription());
        $newActivityProtocolItem->setComment($protocolItem->getComment());
        $newActivityProtocolItem->setCheckbox($protocolItem->getCheckbox());
        $newActivityProtocolItem->setRequired($protocolItem->getRequired());
        $newActivityProtocolItem->setHidden($protocolItem->getHidden());
        $newActivityProtocolItem->setCanBeHidden($protocolItem->getCanBeHidden());
        $newActivityProtocolItem->setUuid($protocolItem->getUuid());
        $newActivityProtocolItem->setParentUuid($protocolItem->getParentUuid());
        $newActivityProtocolItem->setCompareType($protocolItem->getCompareType());
        $newActivityProtocolItem->setCompareArgs($protocolItem->getCompareArgs());

        return $newActivityProtocolItem;
    }

    private function prepareActivitySettlementProtocol(Activity $activity, Activity $newActivity, SettlementProtocol $settlementProtocol): ActivitySettlementProtocol
    {
        $newActivitySettlementProtocol = new ActivitySettlementProtocol();
        $newActivitySettlementProtocol->setCompany($activity->getCompany());
        $newActivitySettlementProtocol->setActivity($newActivity);
        $newActivitySettlementProtocol->setSettlementProtocol($settlementProtocol);
        $newActivitySettlementProtocol->setName($settlementProtocol->getName());
        $newActivitySettlementProtocol->setCurrency($settlementProtocol->getCurrency());
        $newActivitySettlementProtocol->setPosition($settlementProtocol->getPosition());
        $newActivitySettlementProtocol->setPrintSettlementProtocolOnPdf($settlementProtocol->getPrintSettlementProtocolOnPdf());

        return $newActivitySettlementProtocol;
    }

    private function prepareActivitySettlementProtocolItem(ActivitySettlementProtocol $activitySettlementProtocol, ActivitySettlementProtocolItem $activitySettlementProtocolItem): ActivitySettlementProtocolItem
    {
        $newActivitySettlementProtocolItem = new ActivitySettlementProtocolItem();
        $newActivitySettlementProtocolItem->setActivitySettlementProtocol($activitySettlementProtocol);
        $newActivitySettlementProtocolItem->setCompany($activitySettlementProtocolItem->getCompany());
        $newActivitySettlementProtocolItem->setName($activitySettlementProtocolItem->getName());
        $newActivitySettlementProtocolItem->setRequired($activitySettlementProtocolItem->getRequired());
        $newActivitySettlementProtocolItem->setValue($activitySettlementProtocolItem->getValue());
        $newActivitySettlementProtocolItem->setUnit($activitySettlementProtocolItem->getUnit());
        $newActivitySettlementProtocolItem->setPerWorker($activitySettlementProtocolItem->getPerWorker());
        $newActivitySettlementProtocolItem->setUserId($activitySettlementProtocolItem->getUserId());
        $newActivitySettlementProtocolItem->setTakeFromActivityRealization($activitySettlementProtocolItem->getTakeFromActivityRealization());
        $newActivitySettlementProtocolItem->setType($activitySettlementProtocolItem->getType());
        $newActivitySettlementProtocolItem->setValues($newActivitySettlementProtocolItem->getValues());
        $newActivitySettlementProtocolItem->setSettlementProtocolItem($activitySettlementProtocolItem->getSettlementProtocolItem());
        $newActivitySettlementProtocolItem->setRate($activitySettlementProtocolItem->getRate());
        $newActivitySettlementProtocolItem->setStartDate($activitySettlementProtocolItem->getStartDate());
        $newActivitySettlementProtocolItem->setEndDate($activitySettlementProtocolItem->getEndDate());
        $newActivitySettlementProtocolItem->setPosition($activitySettlementProtocolItem->getPosition());

        return $newActivitySettlementProtocolItem;
    }

    private function getCategory(DuplicateActivity $command): ?Category
    {
        return $command->getCategory() ? $this->categoryRepository->find($command->getCategory()->getId()) : null;
    }

    private function addEquipments(Activity $activity, Activity $newActivity): void
    {
        foreach ($activity->getEquipments() as $equipment) {
            $newActivity->addEquipment($equipment);
        }
    }

    private function addSubtasks(DuplicateActivity $command, Activity $activity, Activity $newActivity): void
    {
        if (!$command->getData()->getSubtasks()) {
            return;
        }

        foreach ($activity->getActivitySubtasks() as $subtask) {
            $newSubtask = $this->prepareSubtask($command, $subtask);
            $newActivity->addActivitySubtask($newSubtask);
        }
    }

    private function addTags(Activity $activity, Activity $newActivity): void
    {
        foreach ($activity->getTags() as $tag) {
            $newActivity->addTag($tag);
        }
    }

    private function prepareComment(ActivityComment $comment, Activity $newActivity): ActivityComment
    {
        $activityComment = new ActivityComment();
        $activityComment->setComment($comment->getComment());
        $activityComment->setDateComment($comment->getDateComment());
        $activityComment->setOwner($comment->getOwner());
        $activityComment->setActivity($newActivity);
        $activityComment->setVisibility($comment->getVisibility());
        $activityComment->setClient($comment->getClient());
        $activityComment->setOwnerName($comment->getOwnerName());

        return $activityComment;
    }

    private function addComments(Activity $activity, Activity $newActivity): void
    {
        $activityComments = $activity->getActivityComments();

        if (empty($activityComments)) {
            return;
        }
        foreach ($activityComments as $comment) {
            $activityComment = $this->prepareComment($comment, $newActivity);
            $this->entityManager->persist($activityComment);
        }
        $this->entityManager->flush();
    }
}
