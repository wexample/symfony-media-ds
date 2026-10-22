<?php

namespace Wexample\SymfonyMediaDs\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyDesignSystem\Event\UploadCompletedEvent;
use Wexample\SymfonyMedia\Repository\MediaFolderRepository;
use Wexample\SymfonyMedia\Security\MediaVoter;
use Wexample\SymfonyMedia\Service\MediaLibraryService;
use Wexample\SymfonyMedia\Service\MediaStorage;
use Wexample\SymfonyMediaDs\Twig\MediaLibraryExtension;

/**
 * Takes a file uploaded into the library's intake directory into the library,
 * in the folder the upload address names (`?folder=<id>`), and answers with
 * the media it became.
 */
class MediaUploadSubscriber implements EventSubscriberInterface
{
    public const string QUERY_FOLDER = 'folder';

    public function __construct(
        private readonly MediaLibraryExtension $libraryOptions,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly MediaFolderRepository $folderRepository,
        private readonly MediaStorage $storage,
        private readonly MediaLibraryService $library,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [UploadCompletedEvent::class => 'onUploadCompleted'];
    }

    public function onUploadCompleted(UploadCompletedEvent $event): void
    {
        if (rtrim($event->directory, '/') !== rtrim($this->libraryOptions->getIntakeDirectory(), '/')) {
            return;
        }

        // The address was signed for a manager; who sends the last piece
        // still has to be one.
        if (! $this->authorizationChecker->isGranted(MediaVoter::MANAGE)) {
            $event->refuse('Adding to the media library is not allowed.', 403);

            return;
        }

        $folderId = $event->request->query->getString(self::QUERY_FOLDER);
        $folder = '' !== $folderId && Uuid::isValid($folderId) ? $this->folderRepository->find($folderId) : null;

        if ('' !== $folderId && null === $folder) {
            $event->refuse('This folder of the media library does not exist.', 404);

            return;
        }

        $media = $this->storage->import($event->path, $event->fileName, $folder);
        $event->setData(['media' => $this->library->describe($media)]);
    }
}
