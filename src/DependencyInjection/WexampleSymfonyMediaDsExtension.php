<?php

namespace Wexample\SymfonyMediaDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyMediaDsExtension extends AbstractWexampleSymfonyExtension
{
    public const string FORM_THEME = '@WexampleSymfonyMediaDsBundle/form/form_theme.html.twig';

    /**
     * The media field joins the application's form theme; themes are merged,
     * so this adds a block rather than replacing anything.
     */
    public function prepend(ContainerBuilder $container): void
    {
        parent::prepend($container);

        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'form_themes' => [self::FORM_THEME],
            ]);
        }
    }

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
