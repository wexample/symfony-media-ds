<?php

namespace Wexample\SymfonyMediaDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyMedia\Security\MediaVoter;
use Wexample\SymfonyMediaDs\Traits\SymfonyMediaDsBundleClassTrait;

/**
 * The media library, browsed by whoever may read it, arranged by whoever may
 * manage it.
 */
#[Route(path: '/media/', name: 'media_library_')]
final class MediaLibraryController extends AbstractPagesController
{
    use SymfonyMediaDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(MediaVoter::VIEW);

        return $this->renderPage(self::ROUTE_INDEX);
    }
}
