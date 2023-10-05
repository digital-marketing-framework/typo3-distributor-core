<?php

use DigitalMarketingFramework\Typo3\Distributor\Core\Backend\DataHandler\MetaDataHandler;
use DigitalMarketingFramework\Typo3\Distributor\Core\Form\Element\JsonFieldElement;
use DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler\QueueCleanupFieldProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler\QueueCleanupTask;
use DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler\QueueProcessorFieldProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler\QueueProcessorTask;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function () {
    $typoScripts = [
        'plugin.tx_form.settings.yamlConfigurations.1673273385 = EXT:digitalmarketingframework_distributor/Configuration/Yaml/FormSetup.yaml',
        'module.tx_form.settings.yamlConfigurations.1673273385 = EXT:digitalmarketingframework_distributor/Configuration/Yaml/FormSetup.yaml',
    ];
    ExtensionManagementUtility::addTypoScriptSetup(implode(PHP_EOL, $typoScripts));
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][QueueProcessorTask::class] = [
        'extension' => 'digitalmarketingframework_distributor',
        'title' => 'Digital Marketing Framework - Distributor - Queue Worker',
        'description' => 'Processes the next batch of form submissions using the digital-marketing-framework/distributor',
        'additionalFields' => QueueProcessorFieldProvider::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][QueueCleanupTask::class] = [
        'extension' => 'digitalmarketingframework_distributor',
        'title' => 'Digital Marketing Framework - Distributor - Queue Cleanup',
        'description' => 'Removes old submissions from the database to be compliant with data protection regulations',
        'additionalFields' => QueueCleanupFieldProvider::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = MetaDataHandler::class;
    // Add textarea with built-in json formatting
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1673431342] = [
        'nodeName' => 'digitalMarketingFrameworkJsonFieldElement',
        'priority' => 40,
        'class' => JsonFieldElement::class,
    ];
})();
