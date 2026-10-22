<?php

namespace Wexample\SymfonyMediaDs\Tests\Integration;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Wexample\SymfonyDesignSystem\Event\UploadCompletedEvent;
use Wexample\SymfonyDesignSystem\Service\UploadTokenService;
use Wexample\SymfonyMedia\Repository\MediaRepository;
use Wexample\SymfonyMedia\Service\MediaLibraryService;
use Wexample\SymfonyMediaDs\EventSubscriber\MediaUploadSubscriber;
use Wexample\SymfonyMediaDs\Twig\MediaLibraryExtension;

/**
 * A file landing in the library's intake directory becomes a media of the
 * folder its address names; anywhere else it is none of the library's
 * business.
 */
class UploadTest extends KernelTestCase
{
    private string $intake;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        (new Filesystem())->remove(self::getContainer()->getParameter('wexample_symfony_media.storage_dir'));
        $this->intake = self::getContainer()->get(MediaLibraryExtension::class)->getIntakeDirectory();
        mkdir($this->intake, 0775, true);
    }

    private function signIn(string ...$roles): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(new InMemoryUser('someone', null, $roles), 'main', $roles)
        );
    }

    private function arrive(
        string $directory,
        array $query = []
    ): UploadCompletedEvent {
        $path = $directory.'/report.txt';
        file_put_contents($path, 'a report');
        $event = new UploadCompletedEvent($directory, $path, 'report.txt', new Request($query));

        self::getContainer()->get(MediaUploadSubscriber::class)->onUploadCompleted($event);

        return $event;
    }

    public function testALibraryAcceptingImagesTakesNothingElse(): void
    {
        $this->signIn('ROLE_ADMIN');
        $extension = self::getContainer()->get(MediaLibraryExtension::class);
        $tokens = self::getContainer()->get(UploadTokenService::class);
        $token = fn (array $options) => parse_url($extension->options($options)['uploadUrl'], PHP_URL_PATH);
        $rules = fn (array $options) => $tokens->rules(basename($token($options)));

        $this->assertTrue($rules(['accept' => 'image/*'])->allowsMimeType('image/png'));
        $this->assertFalse($rules(['accept' => 'image/*'])->allowsMimeType('text/plain'));
        $this->assertTrue($rules([])->allowsName('report.txt'));
    }

    public function testAnUploadBecomesAMediaOfTheFolderNamed(): void
    {
        $this->signIn('ROLE_ADMIN');
        $folder = self::getContainer()->get(MediaLibraryService::class)->createFolder('Reports');

        $event = $this->arrive($this->intake, ['folder' => (string) $folder->getId()]);

        $media = self::getContainer()->get(MediaRepository::class)->findAll();
        $this->assertCount(1, $media);
        $this->assertSame($folder, $media[0]->getFolder());
        $this->assertSame('report.txt', $event->getData()['media']['name']);
        $this->assertFileDoesNotExist($this->intake.'/report.txt');
    }

    public function testWhoMayNotManageIsRefused(): void
    {
        $this->signIn('ROLE_USER');

        $event = $this->arrive($this->intake);

        $this->assertSame(403, $event->getRefusalStatus());
        $this->assertNotNull($event->getRefusal());
    }

    public function testAnotherDirectoryIsLeftAlone(): void
    {
        $this->signIn('ROLE_ADMIN');
        $elsewhere = sys_get_temp_dir().'/media-ds-elsewhere';
        @mkdir($elsewhere);

        $event = $this->arrive($elsewhere);

        $this->assertSame([], $event->getData());
        $this->assertFileExists($elsewhere.'/report.txt');
        $this->assertSame(0, self::getContainer()->get(MediaRepository::class)->count([]));
    }
}
