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
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected FormPersistenceManagerInterface $formPersistenceManager,
        protected ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        protected ExtFormConfigurationManagerInterface $extFormConfigurationManager,
        protected SiteFinder $siteFinder,
    ) {
    }

    /**
     * @param array{pluginId?:int} $dataSourceContext
     */
    protected function getPluginFlexForm(array $dataSourceContext): string
    {
        if (!isset($dataSourceContext['pluginId'])) {
            return '';
        }

        $uid = $dataSourceContext['pluginId'];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
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
     * @param array<string,mixed> $dataSourceContext
     *
     * @return array<string,mixed>
     */
    protected function overrideByFlexFormSettings(array $formDefinition, array $dataSourceContext): array
    {
        if (!isset($formDefinition['finishers'])) {
            return $formDefinition;
        }

        $flexFormData = $this->getPluginFlexForm($dataSourceContext);
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
     * @return array<string,mixed>
     */
    public function getFormDataSourceContext(?Request $request = null): array
    {
        $isFrontend = $request instanceof Request;
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            $context = [];
        } else {
            $typoScriptSettings = CliEnvironmentUtility::ensureBackendRequest(
                fn () => $this->extbaseConfigurationManager->getConfiguration(ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form')
            );
            // @phpstan-ignore-next-line TYPO3 version switch
            $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, $isFrontend);
            $context = [
                'typoScriptSettings' => [
                    'formDefinitionOverrides' => $typoScriptSettings['formDefinitionOverrides'] ?? [],
                ],
                'formSettings' => [
                    'persistenceManager' => $formSettings['persistenceManager'] ?? [],
                ],
            ];
        }

        if ($isFrontend) {
            $contentObjectData = $request->getAttribute('currentContentObject')->data ?? [];
            $pluginId = $contentObjectData['_LOCALIZED_UID'] ?? ($contentObjectData['uid'] ?? null);
            if ($pluginId !== null) {
                // TODO test and take into account:
                //      - workspace version
                //      - field-specific language fallback?
                //      - what if the form plugin was a reference?
                $context['pluginId'] = $pluginId;
            }
        }

        return $context;
    }

    /**
     * @param array<string,mixed> $dataSourceContext
     *
     * @return ?array<string,mixed>
     */
    public function getFormById(string $formId, array $dataSourceContext): ?array
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
            // @phpstan-ignore-next-line TYPO3 version switch
            $formDefinition = $this->formPersistenceManager->load(
                $formId,
                $dataSourceContext['formSettings'] ?? [],
                $dataSourceContext['typoScriptSettings'] ?? []
            );
        }

        return $this->overrideByFlexFormSettings($formDefinition, $dataSourceContext);
    }

    /**
     * @param array<string,mixed> $dataSourceContext
     *
     * @return array<string,array<string,mixed>>
     */
    public function getAllForms(array $dataSourceContext): array
    {
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            // @phpstan-ignore-next-line TYPO3 version switch
            $forms = $this->formPersistenceManager->listForms();
        } else {
            // @phpstan-ignore-next-line TYPO3 version switch
            $forms = $this->formPersistenceManager->listForms($dataSourceContext['formSettings'] ?? []);
        }

        $result = [];
        foreach ($forms as $form) {
            $id = $form['persistenceIdentifier'];
            $formDefinition = $this->getFormById($id, []);
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
     *
     * @return array<array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:string}>
     */
    protected function fetchAllFormPlugins(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        // For migration purposes, include hidden and time-restricted records.
        // Only exclude deleted records.
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        return $queryBuilder
            ->select('uid', 'pid', 'sys_language_uid', 'l18n_parent', 't3ver_wsid', 't3ver_oid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('form_formframework')),
                $queryBuilder->expr()->neq('pi_flexform', $queryBuilder->createNamedParameter(''))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Fetches a single tt_content record by UID with all fields needed for variant context.
     *
     * @return ?array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:string}
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

        return $rows[0] ?? null;
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
     * @param array{uid:int,pid:int,sys_language_uid:int,l18n_parent:int,t3ver_wsid:int,t3ver_oid:int,pi_flexform:string} $plugin
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

        $context = [
            'pluginId' => $plugin['uid'],
            'sheetIdentifier' => $sheetIdentifier,
            'pageId' => $plugin['pid'],
            'contentId' => $canonicalId,
            'languageId' => $plugin['sys_language_uid'],
            'languageName' => $this->resolveLanguageName($plugin['pid'], $plugin['sys_language_uid']),
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
     * @param array<string,mixed> $dataSourceContext
     *
     * @return array<array{formId:string,formDefinition:array<string,mixed>,dataSourceContext:array<string,mixed>}>
     */
    public function getAllFormPluginVariants(array $dataSourceContext): array
    {
        $forms = $this->getAllForms($dataSourceContext);
        $hashMap = $this->buildSheetHashMap($forms);

        $variants = [];
        foreach ($this->fetchAllFormPlugins() as $plugin) {
            $flexFormData = GeneralUtility::xml2array($plugin['pi_flexform']);
            if (!is_array($flexFormData) || !isset($flexFormData['data'])) {
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

                // Build form definition with this FlexForm override applied
                $setup = $sheetData['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';
                $formDefinition = $forms[$formId];
                foreach ($formDefinition['finishers'] ?? [] as $index => $finisher) {
                    if ($finisher['identifier'] === 'Digitalmarketingframework') {
                        $formDefinition['finishers'][$index]['options']['setup'] = $setup;
                        break;
                    }
                }

                $variants[] = [
                    'formId' => $formId,
                    'formDefinition' => $formDefinition,
                    'dataSourceContext' => $this->buildVariantContext($plugin, $sheetKey),
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
     * @param array<string,mixed> $dataSourceContext
     *
     * @return ?array{formId:string,formDefinition:array<string,mixed>,dataSourceContext:array<string,mixed>}
     */
    public function getFormPluginVariant(string $formId, int $pluginId, array $dataSourceContext): ?array
    {
        $formDefinition = $this->getFormById($formId, $dataSourceContext);
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

        $flexFormData = GeneralUtility::xml2array($plugin['pi_flexform']);
        if (!is_array($flexFormData) || !isset($flexFormData['data'][$sheetIdentifier])) {
            return null;
        }

        // Apply FlexForm override to form definition
        $setup = $flexFormData['data'][$sheetIdentifier]['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';
        foreach ($formDefinition['finishers'] ?? [] as $index => $finisher) {
            if ($finisher['identifier'] === 'Digitalmarketingframework') {
                $formDefinition['finishers'][$index]['options']['setup'] = $setup;
                break;
            }
        }

        return [
            'formId' => $formId,
            'formDefinition' => $formDefinition,
            'dataSourceContext' => $this->buildVariantContext($plugin, $sheetIdentifier),
        ];
    }

    /**
     * Updates the DMF finisher configuration document in a form definition YAML file.
     *
     * @param array<string,mixed> $dataSourceContext
     */
    public function updateFormFinisherDocument(string $formId, string $document, array $dataSourceContext): void
    {
        $formDefinition = $this->getFormById($formId, []);
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
            // @phpstan-ignore-next-line TYPO3 version switch
            $this->formPersistenceManager->save(
                $formId,
                $formDefinition,
                $dataSourceContext['formSettings'] ?? []
            );
        }
    }

    /**
     * Updates the DMF finisher configuration document in a form plugin's FlexForm data.
     *
     * @param array{pluginId:int,sheetIdentifier:string} $dataSourceContext
     */
    public function updateFormPluginDocument(string $document, array $dataSourceContext): void
    {
        $pluginId = $dataSourceContext['pluginId'];
        $sheetIdentifier = $dataSourceContext['sheetIdentifier'];

        $flexFormXml = $this->getPluginFlexForm($dataSourceContext);
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
