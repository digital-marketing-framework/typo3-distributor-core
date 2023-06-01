<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\IntegerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\RecordsContentObject;

class ContentElementDataProvider extends DataProvider
{
    protected const KEY_FIELD = 'field';
    protected const DEFAULT_FIELD = '';

    protected const KEY_CONTENT_ID = 'ttContentUid';
    protected const DEFAULT_CONTENT_ID = 0;

    protected function processContext(ContextInterface $context): void
    {
        $ttContentUid = $this->getConfig(static::KEY_CONTENT_ID);

        $uids = $ttContentUid === '' ? [] : explode(',', $ttContentUid);

        $content = $this->renderContentElements($uids);
        if ($content) {
            $this->submission->getContext()['content_element'] = $content;
        }
    }

    protected function process(): void
    {
        $field = $this->getConfig(static::KEY_FIELD);
        $content = $this->submission->getContext()['content_element'] ?? '';
        if ($field && $content) {
            $this->appendToField($field, $content, "\n");
        }
    }

    protected function renderContentElements(array $uids): string
    {
        $content = '';
        foreach ($uids as $uid) {
            $recordsContentObject = GeneralUtility::makeInstance(RecordsContentObject::class);
            $renderedElement = $recordsContentObject->render(
                [
                    'tables' => 'tt_content',
                    'source' => $uid,
                    'dontCheckPid' => 1,
                ]
            );
            $renderedElement = $this->prettyContent($renderedElement);
            if ($renderedElement === '') {
                continue;
            }
            if ($content !== '') {
                $content .= '\n';
            }
            $content .= $renderedElement;
        }
        return $content;
    }

    protected function prettyContent(string $content): string
    {
        return trim(strip_tags($content, '<a>'));
    }

    public static function getSchema(): SchemaInterface
    {
        /** @var ContainerSchema $schema */
        $schema = parent::getSchema();
        $schema->addProperty(static::KEY_FIELD, new StringSchema(static::DEFAULT_FIELD));
        $schema->addProperty(static::KEY_CONTENT_ID, new IntegerSchema(static::DEFAULT_CONTENT_ID));
        return $schema;
    }
}
