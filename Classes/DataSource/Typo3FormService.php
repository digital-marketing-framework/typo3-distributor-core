<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

use DigitalMarketingFramework\Typo3\Core\Utility\CliEnvironmentUtility;
use InvalidArgumentException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class Typo3FormService
{
    /**
     * @var ?array{formSettings:array<string,mixed>,typoScriptSettings:array<string,mixed>}
     */
    private ?array $formSettings = null;

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected FormPersistenceManagerInterface $formPersistenceManager,
        protected ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        protected ExtFormConfigurationManagerInterface $extFormConfigurationManager,
        protected SiteFinder $siteFinder,
    ) {
    }

    /**
     * Lazily loads and returns the form persistence settings needed by FormPersistenceManager.
     * On TYPO3 12 returns empty arrays. On TYPO3 13+ loads from Extbase/Form YAML configuration.
     *
     * @return array{formSettings:array<string,mixed>,typoScriptSettings:array<string,mixed>}
     */
    protected function getFormSettings(): array
    {
        if ($this->formSettings === null) {
            $typo3Version = new Typo3Version();
            if ($typo3Version->getMajorVersion() <= 12) {
                $this->formSettings = [
                    'formSettings' => [],
                    'typoScriptSettings' => [],
                ];
            } else {
                $typoScriptSettings = CliEnvironmentUtility::ensureBackendRequest(
                    fn () => $this->extbaseConfigurationManager->getConfiguration(
                        ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
                        'form'
                    )
                );
                // @phpstan-ignore-next-line TYPO3 version switch
                $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
                $this->formSettings = [
                    'formSettings' => [
                        'persistenceManager' => $formSettings['persistenceManager'] ?? [],
                    ],
                    'typoScriptSettings' => [
                        'formDefinitionOverrides' => $typoScriptSettings['formDefinitionOverrides'] ?? [],
                    ],
                ];
            }
        }

        return $this->formSettings;
    }

    /**
     * Parses the FlexForm XML string in a plugin record into an array.
     *
     * @param array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:string} $plugin
     *
     * @return array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:array<string,mixed>}
     */
    protected function preparePluginRecord(array $plugin): array
    {
        $flexFormData = GeneralUtility::xml2array($plugin['pi_flexform']);
        $plugin['pi_flexform'] = is_array($flexFormData) ? $flexFormData : [];

        return $plugin;
    }

    protected function getPluginFlexForm(?int $pluginId): string
    {
        if ($pluginId === null) {
            return '';
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $pluginId))
            ->setMaxResults(1);

        $rows = $queryBuilder->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return '';
        }

        return $rows[0]['pi_flexform'] ?? '';
    }

    /**
     * @param array<string,mixed> $formDefinition
     *
     * @see FormFrontendController::getFlexformSheetIdentifier()
     */
    protected function getFlexformSheetIdentifier(array $formDefinition, string $prototypeName, string $finisherIdentifier): string
    {
        return md5(
            implode('', [
                $formDefinition['persistenceIdentifier'],
                $prototypeName,
                $formDefinition['identifier'],
                $finisherIdentifier,
            ])
        );
    }

    /**
     * @param array<string,mixed> $formDefinition
     *
     * @return array<string,mixed>
     */
    protected function overrideByFlexFormSettings(array $formDefinition, ?int $pluginId = null): array
    {
        if (!isset($formDefinition['finishers'])) {
            return $formDefinition;
        }

        $flexFormData = $this->getPluginFlexForm($pluginId);
        if ($flexFormData !== '') {
            $flexFormData = GeneralUtility::xml2array($flexFormData);
        }

        if (!is_array($flexFormData) || $flexFormData === []) {
            return $formDefinition;
        }

        $persistenceIdentifier = $flexFormData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] ?? '';
        if ($persistenceIdentifier !== '') {
            $formDefinition['persistenceIdentifier'] = $persistenceIdentifier;
        }

        if (!($flexFormData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] ?? false)) {
            return $formDefinition;
        }

        $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
        foreach ($formDefinition['finishers'] as $index => $formFinisherDefinition) {
            $finisherIdentifier = $formFinisherDefinition['identifier'];
            if ($finisherIdentifier === 'Digitalmarketingframework') {
                $sheetIdentifier = $this->getFlexformSheetIdentifier($formDefinition, $prototypeName, $finisherIdentifier);
                if (isset($flexFormData['data'][$sheetIdentifier])) {
                    $setup = $flexFormData['data'][$sheetIdentifier]['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';
                    $formDefinition['finishers'][$index]['options']['setup'] = $setup;
                }
            }
        }

        return $formDefinition;
    }

    /**
     * @deprecated Use getCurrentFormDataSourceVariantIdentifier() instead
     *
     * @return array<string,mixed>
     */
    public function getFormDataSourceContext(?Request $request = null): array
    {
        $context = [];

        if ($request instanceof Request) {
            $contentObjectData = $request->getAttribute('currentContentObject')->data ?? [];
            $pluginId = $contentObjectData['_LOCALIZED_UID'] ?? ($contentObjectData['uid'] ?? null);
            if ($pluginId !== null) {
                $context['pluginId'] = $pluginId;
            }
        }

        return $context;
    }

    /**
     * Builds the full data source variant identifier for the current form submission context.
     * Includes the plugin ID suffix when a content element override is present.
     */
    public function getCurrentFormDataSourceVariantIdentifier(string $dataSourceId, ?Request $request = null): string
    {
        $identifier = $dataSourceId;

        if ($request instanceof Request) {
            $contentObjectData = $request->getAttribute('currentContentObject')->data ?? [];
            $pluginId = $contentObjectData['_LOCALIZED_UID'] ?? ($contentObjectData['uid'] ?? null);
            if ($pluginId !== null) {
                $identifier .= ':' . $pluginId;
            }
        }

        return $identifier;
    }

    /**
     * @return ?array<string,mixed>
     */
    public function getFormById(string $formId, ?int $pluginId = null): ?array
    {
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            // @phpstan-ignore-next-line TYPO3 version switch
            if (!$this->formPersistenceManager->exists($formId)) {
                return null;
            }

            // @phpstan-ignore-next-line TYPO3 version switch
            $formDefinition = $this->formPersistenceManager->load($formId);
        } else {
            $settings = $this->getFormSettings();
            // @phpstan-ignore-next-line TYPO3 version switch
            $formDefinition = $this->formPersistenceManager->load(
                $formId,
                $settings['formSettings'],
                $settings['typoScriptSettings']
            );
        }

        return $this->overrideByFlexFormSettings($formDefinition, $pluginId);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getAllForms(): array
    {
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            // @phpstan-ignore-next-line TYPO3 version switch
            $forms = $this->formPersistenceManager->listForms();
        } else {
            $settings = $this->getFormSettings();
            // @phpstan-ignore-next-line TYPO3 version switch
            $forms = $this->formPersistenceManager->listForms($settings['formSettings']);
        }

        $result = [];
        foreach ($forms as $form) {
            $id = $form['persistenceIdentifier'];
            $formDefinition = $this->getFormById($id);
            if ($formDefinition === null) {
                continue;
            }

            // Only include forms that have the Anyrel finisher
            $hasAnyrelFinisher = false;
            foreach ($formDefinition['finishers'] ?? [] as $finisher) {
                if ($finisher['identifier'] === 'Digitalmarketingframework') {
                    $hasAnyrelFinisher = true;
                    break;
                }
            }

            if ($hasAnyrelFinisher) {
                $result[$id] = $formDefinition;
            }
        }

        return $result;
    }

    /**
     * Builds a mapping from FlexForm sheet identifier (MD5 hash) to form persistence identifier.
     * Used to associate FlexForm sheets with their corresponding form definitions.
     *
     * @param array<string,array<string,mixed>> $forms Form definitions keyed by persistence identifier
     *
     * @return array<string,string> Map of sheetIdentifier => formPersistenceIdentifier
     */
    protected function buildSheetHashMap(array $forms): array
    {
        $map = [];
        foreach ($forms as $formId => $formDefinition) {
            $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
            // FormPersistenceManager::load() does not set persistenceIdentifier
            // on the returned array, but getFlexformSheetIdentifier() needs it.
            // $formId is the persistence identifier (key from getAllForms()).
            $formDefinition['persistenceIdentifier'] = $formId;
            $hash = $this->getFlexformSheetIdentifier($formDefinition, $prototypeName, 'Digitalmarketingframework');
            $map[$hash] = $formId;
        }

        return $map;
    }

    /**
     * Fetches all tt_content records that are form plugins with non-empty FlexForm data.
     * FlexForm XML is parsed into arrays via preparePluginRecord().
     *
     * @return array<array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:array<string,mixed>}>
     */
    protected function fetchAllFormPlugins(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        // For migration purposes, include hidden and time-restricted records.
        // Only exclude deleted records.
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $rows = $queryBuilder
            ->select('uid', 'pid', 'sys_language_uid', 'l18n_parent', 't3ver_wsid', 't3ver_oid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('form_formframework')),
                $queryBuilder->expr()->neq('pi_flexform', $queryBuilder->createNamedParameter(''))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->preparePluginRecord(...), $rows);
    }

    /**
     * Fetches a single tt_content record by UID with all fields needed for variant context.
     * FlexForm XML is parsed into an array via preparePluginRecord().
     *
     * @return ?array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:array<string,mixed>}
     */
    protected function fetchFormPluginRecord(int $uid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $rows = $queryBuilder
            ->select('uid', 'pid', 'sys_language_uid', 'l18n_parent', 't3ver_wsid', 't3ver_oid', 'pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return null;
        }

        return $this->preparePluginRecord($rows[0]);
    }

    /**
     * Resolves a sys_language_uid to a human-readable language title via site configuration.
     */
    protected function resolveLanguageName(int $pageId, int $languageId): string
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $language = $site->getLanguageById($languageId);

            return $language->getTitle();
        } catch (SiteNotFoundException|InvalidArgumentException) {
            return (string)$languageId;
        }
    }

    /**
     * Builds the dataSourceContext array for a form plugin variant.
     *
     * @param array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:array<string,mixed>} $plugin
     *
     * @return array<string,mixed>
     */
    protected function buildVariantContext(array $plugin, string $sheetIdentifier): array
    {
        // Determine the canonical content element UID:
        // translation → l18n_parent, workspace version → t3ver_oid, otherwise → uid
        $canonicalId = $plugin['uid'];
        if ($plugin['l18n_parent'] !== 0) {
            $canonicalId = $plugin['l18n_parent'];
        } elseif ($plugin['t3ver_oid'] !== 0) {
            $canonicalId = $plugin['t3ver_oid'];
        }

        $flexFormData = $plugin['pi_flexform'];
        $selectedFormId = $flexFormData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] ?? '';
        $overrideFinishers = (bool)($flexFormData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] ?? false);

        $context = [
            'pluginId' => $plugin['uid'],
            'sheetIdentifier' => $sheetIdentifier,
            'pageId' => $plugin['pid'],
            'contentId' => $canonicalId,
            'languageId' => $plugin['sys_language_uid'],
            'languageName' => $this->resolveLanguageName($plugin['pid'], $plugin['sys_language_uid']),
            'selectedFormId' => $selectedFormId,
            'overrideFinishers' => $overrideFinishers,
        ];

        if ($plugin['t3ver_wsid'] !== 0) {
            $context['workspaceId'] = $plugin['t3ver_wsid'];
        }

        return $context;
    }

    /**
     * Discovers all form plugin variants from tt_content records.
     *
     * For each form plugin content element, parses the FlexForm XML and finds all sheets
     * containing DMF finisher configuration. This includes stale sheets from previously
     * selected forms that still persist in the FlexForm data.
     *
     * Each sheet is matched to its form definition via MD5 hash lookup.
     *
     * @return array<array{formId:string,formDefinition:array<string,mixed>,dataSourceContext:array<string,mixed>,overrideDocument:string}>
     */
    public function getAllFormPluginVariants(): array
    {
        $forms = $this->getAllForms();
        $hashMap = $this->buildSheetHashMap($forms);

        $variants = [];
        foreach ($this->fetchAllFormPlugins() as $plugin) {
            $flexFormData = $plugin['pi_flexform'];
            if ($flexFormData === [] || !isset($flexFormData['data'])) {
                continue;
            }

            foreach ($flexFormData['data'] as $sheetKey => $sheetData) {
                if ($sheetKey === 'sDEF') {
                    continue;
                }

                $formId = $hashMap[$sheetKey] ?? null;
                if ($formId === null) {
                    // Sheet belongs to a form that no longer exists
                    continue;
                }

                $overrideDocument = $sheetData['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';

                $variants[] = [
                    'formId' => $formId,
                    'formDefinition' => $forms[$formId],
                    'dataSourceContext' => $this->buildVariantContext($plugin, $sheetKey),
                    'overrideDocument' => $overrideDocument,
                ];
            }
        }

        return $variants;
    }

    /**
     * Fetches a single form plugin variant by form ID and plugin UID.
     *
     * Loads the plugin's FlexForm, computes the expected sheet hash for the form,
     * and extracts the DMF setup from that sheet.
     *
     * @return ?array{formId:string,formDefinition:array<string,mixed>,dataSourceContext:array<string,mixed>,overrideDocument:?string}
     */
    public function getFormPluginVariant(string $formId, int $pluginId): ?array
    {
        $formDefinition = $this->getFormById($formId);
        if ($formDefinition === null) {
            return null;
        }

        $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
        $formDefinition['persistenceIdentifier'] = $formId;
        $sheetIdentifier = $this->getFlexformSheetIdentifier($formDefinition, $prototypeName, 'Digitalmarketingframework');

        $plugin = $this->fetchFormPluginRecord($pluginId);
        if ($plugin === null) {
            return null;
        }

        $flexFormData = $plugin['pi_flexform'];
        if ($flexFormData === [] || !isset($flexFormData['data'][$sheetIdentifier])) {
            $overrideDocument = null;
        } else {
            $overrideDocument = $flexFormData['data'][$sheetIdentifier]['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';
        }

        return [
            'formId' => $formId,
            'formDefinition' => $formDefinition,
            'dataSourceContext' => $this->buildVariantContext($plugin, $sheetIdentifier),
            'overrideDocument' => $overrideDocument,
        ];
    }

    /**
     * Updates the DMF finisher configuration document in a form definition YAML file.
     */
    public function updateFormFinisherDocument(string $formId, string $document): void
    {
        $formDefinition = $this->getFormById($formId);
        if ($formDefinition === null) {
            return;
        }

        foreach ($formDefinition['finishers'] ?? [] as $index => $finisher) {
            if ($finisher['identifier'] === 'Digitalmarketingframework') {
                $formDefinition['finishers'][$index]['options']['setup'] = $document;
                break;
            }
        }

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            // @phpstan-ignore-next-line TYPO3 version switch
            $this->formPersistenceManager->save($formId, $formDefinition);
        } else {
            $settings = $this->getFormSettings();
            // @phpstan-ignore-next-line TYPO3 version switch
            $this->formPersistenceManager->save(
                $formId,
                $formDefinition,
                $settings['formSettings']
            );
        }
    }

    /**
     * Updates the DMF finisher configuration document in a form plugin's FlexForm data.
     */
    public function updateFormPluginDocument(string $document, int $pluginId, string $sheetIdentifier): void
    {
        $flexFormXml = $this->getPluginFlexForm($pluginId);
        if ($flexFormXml === '') {
            return;
        }

        $flexFormData = GeneralUtility::xml2array($flexFormXml);
        if (!is_array($flexFormData) || !isset($flexFormData['data'][$sheetIdentifier])) {
            return;
        }

        $flexFormData['data'][$sheetIdentifier]['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] = $document;

        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $updatedXml = $flexFormTools->flexArray2Xml($flexFormData);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder
            ->update('tt_content')
            ->set('pi_flexform', $updatedXml)
            ->where($queryBuilder->expr()->eq('uid', $pluginId))
            ->executeStatement();
    }
}
