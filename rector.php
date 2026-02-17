<?php

declare(strict_types=1);

use Mediatis\Typo3CodingStandards\Php\Typo3RectorSetup;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\CodeQuality\Rector\Class_\ConvertStaticToSelfRector;
use Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector;

return static function (RectorConfig $rectorConfig): void
{
    Typo3RectorSetup::setup($rectorConfig, __DIR__);

    $skip = [];

    // Version-specific rule exclusions.
    // Use FinalizePublicClassConstantRector as version indicator - it exists only in older rector.
    // (InstalledVersions::getVersion() returns null inside rector's config loading context)
    $isOldRector = class_exists(FinalizePublicClassConstantRector::class);
    if ($isOldRector) {
        // Rules that exist in older rector but were deprecated/removed in newer versions.
        $skip = [
            ...$skip,
            // Skip: We don't want to force constants to be final
            FinalizePublicClassConstantRector::class,
            // Skip: Misinterprets Drupal/TYPO3 multi-line PHPDoc format where
            // @return descriptions are on indented continuation lines
            RemoveUselessReturnTagRector::class,
            // Skip: Exception codes are unix timestamps, underscores don't improve readability
            AddLiteralSeparatorToNumberRector::class,
            // Skip: Old Rector can't infer array types from cross-package method calls,
            // suggests unnecessary is_countable() checks. Rule removed in Rector 2.x.
            CountOnNullRector::class,
            // TEMPORARY WORKAROUND: Rector bug - UnSpreadOperatorRector crashes on
            // first-class callable syntax ($this->method(...), Class::method(...)).
            // See: Typo3FormService::preparePluginRecord, JobRepository error mapping.
            // TODO: Remove this skip once Rector version with the fix is available.
            UnSpreadOperatorRector::class,
        ];
    } else {
        // Rules that exist only in newer rector (TYPO3 13+).
        $skip = [
            ...$skip,
            // Skip: Anyrel's registry pattern requires GeneralUtility::makeInstance() for lazy loading
            // of CMS-specific services that may not be available during early initialization.
            GeneralUtilityMakeInstanceToConstructorPropertyRector::class,
            // Skip: Anyrel uses static:: intentionally for late static binding in plugin classes
            ConvertStaticToSelfRector::class,
        ];
    }

    $rectorConfig->skip($skip);
};
