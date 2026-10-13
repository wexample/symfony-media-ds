<?php

namespace Wexample\SymfonyMediaDs\Tests\Integration;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;
use Wexample\SymfonyMedia\Service\MediaStorage;
use Wexample\SymfonyMedia\Service\MediaThumbnailService;
use Wexample\SymfonyMediaDs\Twig\MediaWallExtension;

/**
 * What media_wall() hangs of a media: its thumbnails, its ratio, the way to
 * its file — the same a media-wall reads from the api's metadata.
 */
class MediaWallTest extends KernelTestCase
{
    private MediaWallExtension $extension;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        (new Filesystem())->remove(self::getContainer()->getParameter('wexample_symfony_media.storage_dir'));

        $this->extension = new MediaWallExtension(
            $this->createStub(ComponentsExtension::class),
            self::getContainer()->get('router'),
            self::getContainer()->get(MediaThumbnailService::class),
        );
    }

    private function file(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($path, $content);

        return $path;
    }

    private function png(
        int $width,
        int $height
    ): string {
        $image = imagecreatetruecolor($width, $height);
        $path = tempnam(sys_get_temp_dir(), 'media').'.png';
        imagepng($image, $path);

        return $path;
    }

    public function testAnImageHangsAtItsRatioWithItsThumbnails(): void
    {
        $media = self::getContainer()->get(MediaStorage::class)->import($this->png(400, 300), 'sea.png');
        $tile = $this->extension->tile($media);

        $this->assertSame(9, $tile['span']);
        $this->assertSame([400, 300, 'sea.png'], [$tile['width'], $tile['height'], $tile['caption']]);
        $this->assertStringContainsString('480', $tile['src']);
        $this->assertMatchesRegularExpression('~ 160w, .+ 480w$~', $tile['srcset']);
        $this->assertStringContainsString((string) $media->getId(), $tile['href']);
    }

    public function testAFileWithoutThumbnailStandsAsThePlaceholder(): void
    {
        $media = self::getContainer()->get(MediaStorage::class)->import($this->file('notes'), 'notes.txt');
        $tile = $this->extension->tile($media, ['href' => false, 'captions' => false]);

        $this->assertSame([null, null, null, null, 12], [$tile['src'], $tile['srcset'], $tile['href'], $tile['caption'], $tile['span']]);
    }
}
