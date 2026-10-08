<?php

namespace Wexample\SymfonyMediaDs\Tests\Fixtures\App;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Wexample\SymfonyMedia\WexampleSymfonyMediaBundle;
use Wexample\SymfonyMediaDs\Controller\Pages\MediaLibraryController;
use Wexample\SymfonyMediaDs\Tests\Fixtures\App\DependencyInjection\PublicServicesPass;
use Wexample\SymfonyMediaDs\WexampleSymfonyMediaDsBundle;
use Wexample\SymfonyTesting\Tests\Fixtures\AbstractFixtureKernel;

class AppKernel extends AbstractFixtureKernel
{
    protected function getFixtureDir(): string
    {
        return __DIR__;
    }

    protected function getExtraBundles(): iterable
    {
        return [
            new WexampleSymfonyMediaBundle(),
            new WexampleSymfonyMediaDsBundle(),
        ];
    }

    protected function getConfigFiles(): array
    {
        return [__DIR__.'/config/config.yaml'];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../../vendor/wexample/symfony-media/src/Controller', 'attribute');
        $routes->import(__DIR__.'/../../../vendor/wexample/symfony-media/src/Api/Controller', 'attribute');
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new PublicServicesPass(), PassConfig::TYPE_BEFORE_REMOVING);
        // The pages are drawn by symfony-loader, which this kernel leaves out:
        // what is tested here is the library's machinery. Removed before the
        // controllers' arguments are registered.
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->removeDefinition(MediaLibraryController::class);
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
