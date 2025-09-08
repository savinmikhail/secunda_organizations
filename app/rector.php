<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use SavinMikhail\AddNamedArgumentsRector\AddNamedArgumentsRector;
use SavinMikhail\EnforceAaaPatternRector\EnforceAaaPatternRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/var/rector')
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        SetList::DEAD_CODE,
        SetList::PHP_82,
        SetList::CODE_QUALITY,
    ])
    ->withRules([
        AddNamedArgumentsRector::class,
        EnforceAaaPatternRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSkip([
        RemoveDeadStmtRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
    ])
    ->withComposerBased(phpunit: true, laravel: true);
