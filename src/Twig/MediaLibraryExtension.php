<?php

namespace Wexample\SymfonyMediaDs\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyDesignSystem\Class\UploadRules;
use Wexample\SymfonyDesignSystem\Service\ChunkedUploadReceiver;
use Wexample\SymfonyDesignSystem\Service\UploadTokenService;
use Wexample\SymfonyMedia\Api\Controller\MediaController;
use Wexample\SymfonyMedia\Security\MediaVoter;

/**
 * What the media-library component is drawn with:
 * `vue(render_pass, '@WexampleSymfonyMediaDsBundle/components/media-library', media_library_options())`.
 * The addresses it calls, and — for whoever may arrange the library — those
 * that change it, an upload address among them, signed for the library's
 * intake directory.
 */
class MediaLibraryExtension extends AbstractExtension
{
    /**
     * Wide enough for a full screen, a fraction of what a camera takes.
     */
    public const int IMAGE_MAX_SIZE = 2560;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly UploadTokenService $uploadTokens,
        private readonly ChunkedUploadReceiver $uploadReceiver,
        #[Autowire(param: 'wexample_symfony_media.storage_dir')]
        private readonly string $storageDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_library_options', $this->options(...)),
            new TwigFunction('media_library_url', fn () => $this->urlGenerator->generate('media_library_index')),
        ];
    }

    /**
     * Where a finished upload lands before the library takes it
     * (MediaUploadSubscriber).
     */
    public function getIntakeDirectory(): string
    {
        return $this->storageDir.'/intake';
    }

    /**
     * @param array{multiple?: bool, state_id?: string|null, picker?: bool, field?: string, accept?: string, image_max_size?: int} $options
     *   `accept`, as the attribute: the media listed, and the only files an upload takes;
     *   `image_max_size`, the longest side an image is brought down to in the browser before it is sent, 0 for none
     *
     * @return array<string, mixed>
     */
    public function options(array $options = []): array
    {
        $canManage = $this->authorizationChecker->isGranted(MediaVoter::MANAGE);
        $accept = (string) ($options['accept'] ?? '');
        $route = static fn (string $name) => 'api_media_'.$name;

        return [
            'listUrl' => $this->urlGenerator->generate($route(MediaController::ROUTE_LIST)),
            'canManage' => $canManage,
            'manageUrls' => $canManage ? [
                'folder' => $this->urlGenerator->generate($route(MediaController::ROUTE_FOLDER)),
                'rename' => $this->urlGenerator->generate($route(MediaController::ROUTE_RENAME)),
                'move' => $this->urlGenerator->generate($route(MediaController::ROUTE_MOVE)),
                'delete' => $this->urlGenerator->generate($route(MediaController::ROUTE_DELETE)),
            ] : null,
            'uploadUrl' => $canManage ? $this->urlGenerator->generate('wexample_design_system_upload_chunk', [
                'token' => $this->uploadTokens->issue(
                    $this->getIntakeDirectory(),
                    rules: '' === $accept ? null : new UploadRules(array_map('trim', explode(',', $accept)))
                ),
            ]) : null,
            'chunkSize' => $this->uploadReceiver->chunkSize(),
            'multiple' => $options['multiple'] ?? true,
            'picker' => $options['picker'] ?? false,
            // The id of the field a picker answers.
            'field' => $options['field'] ?? '',
            'accept' => $accept,
            'imageMaxSize' => $options['image_max_size'] ?? self::IMAGE_MAX_SIZE,
            'stateId' => $options['state_id'] ?? 'media-library',
        ];
    }
}
