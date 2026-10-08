<?php

namespace Wexample\SymfonyMediaDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyDesignSystem\Attribute\MenuItem;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyMedia\Security\MediaVoter;
use Wexample\SymfonyMediaDs\Traits\SymfonyMediaDsBundleClassTrait;

/**
 * The media library, browsed by whoever may read it, arranged by whoever may
 * manage it.
 *
 * Opened by a media field in a modal or a panel, it is a picker
 * (`?picker=1&multiple=0|1&field=<id>`): what is chosen goes back to the
 * field named, and the page closes.
 */
#[Route(path: '/media/', name: 'media_library_')]
final class MediaLibraryController extends AbstractPagesController
{
    use SymfonyMediaDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    /**
     * The menu entry the library and its demo share.
     */
    final public const string MENU_GROUP = 'media';

    #[Route(path: '', name: self::ROUTE_INDEX)]
    #[MenuItem(self::MENU_GROUP, 0)]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted(MediaVoter::VIEW);

        return $this->renderPage(self::ROUTE_INDEX, [
            'picker' => $request->query->getBoolean('picker'),
            'multiple' => $request->query->getBoolean('multiple', true),
            'field' => $request->query->getString('field'),
        ]);
    }
}
