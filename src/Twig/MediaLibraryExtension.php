<?php

namespace Wexample\SymfonyMediaDs\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
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
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly UploadTokenService $uploadTokens,
        private readonly ChunkedUploadReceiver $uploadReceiver,
        private readonly TranslatorInterface $translator,
        #[Autowire(param: 'wexample_symfony_media.storage_dir')]
        private readonly string $storageDir,
    ) {
    }

    /**
     * The wording of the library, resolved here and handed over as props:
     * the loader gives a template's translations to its first instance on a
     * page only, and the library may be drawn several times — once per field
     * picking media.
     */
    public const array LABELS = [
        'upload', 'new_folder', 'rename', 'save', 'cancel', 'choose',
        'delete.action', 'delete.title', 'delete.message',
    ];

    public const string LABELS_DOMAIN = 'WexampleSymfonyMediaDsBundle.components.media-library.media-library';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_library_options', $this->options(...)),
            new TwigFunction('media_library_url', fn () => $this->urlGenerator->generate('media_library_index')),
            new TwigFunction('media_picker_labels', fn () => $this->labels(
                'WexampleSymfonyMediaDsBundle.components.media-picker.media-picker',
                ['pick', 'replace', 'remove', 'library']
            )),
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
     * @param array{multiple?: bool, state_id?: string|null, picker?: bool, field?: string} $options
     *
     * @return array<string, mixed>
     */
    public function options(array $options = []): array
    {
        $canManage = $this->authorizationChecker->isGranted(MediaVoter::MANAGE);
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
                'token' => $this->uploadTokens->issue($this->getIntakeDirectory()),
            ]) : null,
            'chunkSize' => $this->uploadReceiver->chunkSize(),
            'multiple' => $options['multiple'] ?? true,
            'picker' => $options['picker'] ?? false,
            // The id of the field a picker answers.
            'field' => $options['field'] ?? '',
            'stateId' => $options['state_id'] ?? 'media-library',
            'labels' => $this->labels(self::LABELS_DOMAIN, self::LABELS),
        ];
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, string> by key, with their `%placeholders%` left for the browser
     */
    public function labels(
        string $domain,
        array $keys
    ): array {
        $labels = [];

        foreach ($keys as $key) {
            $labels[$key] = $this->translator->trans($domain.'::'.$key);
        }

        return $labels;
    }
}
