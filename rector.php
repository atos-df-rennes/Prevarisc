<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\ValueObject\PhpVersion;
use Utils\Rector\Rector\DynamicViewPropertyToAssignRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/application',
    ])
    ->withSkipPath(__DIR__.'/application/cache/UnserializedMemcache.php')
    ->withRootFiles()
    ->withPhpVersion(PhpVersion::PHP_71)
    ->withPhpSets(php71: true)
    ->withPhpPolyfill()
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(50)
    ->withCodeQualityLevel(50)
    ->withImportNames(removeUnusedImports: true)
    ->withPreparedSets(codingStyle: true, privatization: true, earlyReturn: true)
    ->withSkip([
        RemoveUnusedPromotedPropertyRector::class,
        CompleteDynamicPropertiesRector::class,
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__.'/application/services/Descriptif.php',
        ],
    ])
    ->withRules([
        DynamicViewPropertyToAssignRector::class,
    ])
;
