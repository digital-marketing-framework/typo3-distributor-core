<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
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
                $setup = $flexFormData['data'][$sheetIdentifier]['lDEF']['settings.finishers.Digitalmarketingframework.setup']['vDEF'] ?? '';

                if ($setup !== '') {
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
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() <= 12) {
            $context = [];
        } else {
            $typoScriptSettings = $this->extbaseConfigurationManager->getConfiguration(ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
            // @phpstan-ignore-next-line TYPO3 version switch
            $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, true);
            $context = [
                'typoScriptSettings' => [
                    'formDefinitionOverrides' => $typoScriptSettings['formDefinitionOverrides'] ?? [],
                ],
                'formSettings' => [
                    'persistenceManager' => $formSettings['persistenceManager'] ?? [],
                ],
            ];
        }

        if ($request instanceof Request) {
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
            if ($formDefinition !== null) {
                $result[$id] = $formDefinition;
            }
        }

        return $result;
    }
}
