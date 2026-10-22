<?php

namespace Wexample\SymfonyMediaDs\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension;
use Wexample\SymfonyDesignSystem\Twig\WallExtension;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;
use Wexample\SymfonyMedia\Controller\MediaFileController;
use Wexample\SymfonyMedia\Entity\Media;
use Wexample\SymfonyMedia\Service\MediaThumbnailService;

/**
 * Media the page already holds, hung on the design system's wall:
 * `media_wall(medias, { captions, href, class })`. Each tile shows the
 * thumbnails the server draws and leads to the file unless `href` is false;
 * a media without thumbnail stands as the frame's placeholder. The vue twin,
 * media-wall, reads the library from its api as it is scrolled.
 */
class MediaWallExtension extends AbstractTemplateExtension
{
    /**
     * The width a tile takes, column by column as the wall counts them.
     */
    public const string TILE_SIZES = '(min-width: 62rem) 25vw, (min-width: 46rem) 33vw, (min-width: 30rem) 50vw, 100vw';

    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MediaThumbnailService $thumbnails,
    ) {
        parent::__construct($componentsExtension);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'media_wall',
                function (Environment $twig, $context, iterable $medias, array $options = []): string {
                    $items = [];

                    foreach ($medias as $media) {
                        $items[] = $this->tile($media, $options);
                    }

                    return $this->renderComponent(
                        $twig,
                        $context,
                        '@WexampleSymfonyDesignSystemBundle/components/wall',
                        [
                            'items' => $items,
                            'class' => $options['class'] ?? null,
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }

    /**
     * @return array<string, mixed> a tile of the wall, as media() takes it
     */
    public function tile(
        Media $media,
        array $options = []
    ): array {
        $sources = [];

        if ($this->thumbnails->supports($media)) {
            foreach ($this->thumbnails->getSizes() as $width) {
                $sources[$width] = $this->urlGenerator->generate(MediaFileController::ROUTE_THUMBNAIL_NAME, [
                    'id' => $media->getId(),
                    'width' => $width,
                ]);
            }
        }

        return [
            'src' => [] === $sources ? null : end($sources),
            'srcset' => [] === $sources ? null : implode(', ', array_map(
                static fn (int $width, string $url) => $url.' '.$width.'w',
                array_keys($sources),
                $sources
            )),
            'sizes' => [] === $sources ? null : self::TILE_SIZES,
            'alt' => $media->getName(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'href' => false === ($options['href'] ?? true)
                ? null
                : $this->urlGenerator->generate(MediaFileController::ROUTE_FILE_NAME, ['id' => $media->getId()]),
            'caption' => ($options['captions'] ?? true) ? $media->getName() : null,
            'span' => WallExtension::span($media->getWidth(), $media->getHeight()),
        ];
    }
}
