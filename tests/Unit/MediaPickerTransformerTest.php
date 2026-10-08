<?php

namespace Wexample\SymfonyMediaDs\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Wexample\SymfonyMedia\Entity\Media;
use Wexample\SymfonyMedia\Repository\MediaRepository;
use Wexample\SymfonyMediaDs\Form\DataTransformer\MediaPickerTransformer;

class MediaPickerTransformerTest extends TestCase
{
    /**
     * @param list<Media> $media
     */
    private function transformer(
        array $media,
        bool $multiple
    ): MediaPickerTransformer {
        $repository = $this->createStub(MediaRepository::class);
        $byId = [];
        foreach ($media as $item) {
            $byId[(string) $item->getId()] = $item;
        }
        $repository->method('find')->willReturnCallback(static fn ($id) => $byId[(string) $id] ?? null);

        return new MediaPickerTransformer($repository, $multiple);
    }

    public function testOneMedia(): void
    {
        $cover = new Media('cover.png');
        $transformer = $this->transformer([$cover], false);

        $this->assertSame((string) $cover->getId(), $transformer->transform($cover));
        $this->assertSame('', $transformer->transform(null));
        $this->assertSame($cover, $transformer->reverseTransform((string) $cover->getId()));
        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testSeveralMedia(): void
    {
        $a = new Media('a.png');
        $b = new Media('b.pdf');
        $transformer = $this->transformer([$a, $b], true);

        $ids = $a->getId().','.$b->getId();
        $this->assertSame($ids, $transformer->transform(new ArrayCollection([$a, $b])));
        $this->assertSame([$a, $b], $transformer->reverseTransform($ids)->toArray());
    }

    public function testAnUnknownMediaIsRefused(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer([], false)->reverseTransform('01234567-89ab-7def-8123-456789abcdef');
    }
}
