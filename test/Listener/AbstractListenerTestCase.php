<?php

declare(strict_types=1);

namespace LaminasTest\ModuleManager\Listener;

use function dirname;

use Laminas\Loader\ModuleAutoloader;
use LaminasTest\ModuleManager\ResetAutoloadFunctionsTrait;
use PHPUnit\Framework\Attributes\Before;

use PHPUnit\Framework\TestCase;

/**
 * Common test methods for all AbstractListener children.
 */
class AbstractListenerTestCase extends TestCase
{
    use ResetAutoloadFunctionsTrait;

    #[Before]
    protected function registerTestAssetsOnModuleAutoloader(): void
    {
        $autoloader = new ModuleAutoloader([
            dirname(__DIR__) . '/TestAsset',
        ]);
        $autoloader->register();
    }
}
