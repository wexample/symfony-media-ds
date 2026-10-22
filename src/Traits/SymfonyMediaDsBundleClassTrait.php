<?php

namespace Wexample\SymfonyMediaDs\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyMediaDs\WexampleSymfonyMediaDsBundle;

trait SymfonyMediaDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyMediaDsBundle::class;
    }
}
