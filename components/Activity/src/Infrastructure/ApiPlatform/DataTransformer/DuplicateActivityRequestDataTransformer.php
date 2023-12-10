<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\ApiPlatform\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Activity\Application\Command\DuplicateActivity\DuplicateActivityCategory;
use App\Activity\Application\Command\DuplicateActivity\DuplicateActivityData;
use App\Activity\Application\Command\DuplicateActivity;
use App\Activity\Infrastructure\ApiPlatform\Resource\Request\DuplicateActivity\DuplicateActivityRequest;
use App\Application\Id\IdProviderInterface;

class DuplicateActivityRequestDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private IdProviderInterface $idProvider;

    public function __construct(IdProviderInterface $idProvider, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->idProvider = $idProvider;
    }

    /** @var $object DuplicateActivityRequest */
    public function transform($object, string $to, array $context = []): DuplicateActivity
    {
        $this->validate($object);
        $id = $this->idProvider->getId();
        $category = null;

        $name = $object->getName();
        if ($object->getCategory()) {
            $category = new DuplicateActivityCategory($object->getCategory()->getId());
        }
        $description = $object->getDescription();
        $data = new DuplicateActivityData($object->getData()->getSubtasks(), $object->getData()->getComments(), $object->getData()->getCustomFields(), $object->getData()->getLinkToOriginal());

        return new DuplicateActivity(
            $id,
            $name,
            $category,
            $description,
            $data
        );
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof DuplicateActivity) {
            return false;
        }

        return DuplicateActivity::class === $to && DuplicateActivityRequest::class === ($context['input']['class'] ?? null);
    }

    private function validate(DuplicateActivityRequest $object): void
    {
        $this->validator->validate($object);
        if ($object->getCategory()) {
            $this->validator->validate($object->getCategory());
        }
        $this->validator->validate($object->getData());
    }
}
