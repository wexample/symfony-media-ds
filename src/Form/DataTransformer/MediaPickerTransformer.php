<?php

namespace Wexample\SymfonyMediaDs\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyMedia\Entity\Media;
use Wexample\SymfonyMedia\Repository\MediaRepository;

/**
 * Media to the ids the field posts, comma separated, and back. Several media
 * come back as a collection, which a ManyToMany takes as it is.
 */
class MediaPickerTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly bool $multiple,
    ) {
    }

    public function transform(mixed $value): string
    {
        $media = $this->multiple ? [...($value ?? [])] : array_filter([$value]);

        return implode(',', array_map(static fn (Media $item) => (string) $item->getId(), $media));
    }

    public function reverseTransform(mixed $value): Media|ArrayCollection|null
    {
        $media = [];

        foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $id) {
            $item = Uuid::isValid($id) ? $this->mediaRepository->find($id) : null;

            if (null === $item) {
                throw new TransformationFailedException(sprintf('No media "%s".', $id));
            }

            $media[] = $item;
        }

        if ($this->multiple) {
            return new ArrayCollection($media);
        }

        if (count($media) > 1) {
            throw new TransformationFailedException('This field takes one media.');
        }

        return $media[0] ?? null;
    }
}
