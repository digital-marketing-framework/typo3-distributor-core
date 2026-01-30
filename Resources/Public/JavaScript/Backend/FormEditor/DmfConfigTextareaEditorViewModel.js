import $ from 'jquery';
import { renderTextareaEditor } from '@typo3/form/backend/form-editor/inspector-component.js'

/**
 * @private
 *
 * @var object
 */
let _formEditorApp = null;

/**
 * @private
 *
 * @return object
 */
function getFormEditorApp() {
  return _formEditorApp;
}

/**
 * @private
 *
 * @return object
 */
function getPublisherSubscriber() {
  return getFormEditorApp().getPublisherSubscriber();
}

/**
 * @private
 *
 * @return string
 */
function getFormIdentifier() {
  const urlParams = new URLSearchParams(window.location.search);
  const identifier = urlParams.get('formPersistenceIdentifier') || '';

  return identifier !== '' ? 'form:' + identifier : '';
}

/**
 * @private
 *
 * @return string
 */
function getFormName() {
  try {
    const label = getFormEditorApp().configuration.formDefinition.label;
    if (label) {
      return label;
    }
  } catch (e) {
    // Fall back to extracting name from persistence identifier
  }

  // Extract name from persistence identifier (e.g., "1:/form_definitions/contact.form.yaml" -> "contact")
  const urlParams = new URLSearchParams(window.location.search);
  const identifier = urlParams.get('formPersistenceIdentifier') || '';
  const match = identifier.match(/\/([^\/]+)\.form\.yaml$/);

  return match ? match[1] : '';
}

/**
 * @private
 *
 * @param array editorConfiguration
 * @param HTMLElement editorHtml
 * @param string collectionElementIdentifier
 * @param string collectionName
 *
 * @return void
 */
function handleDmfConfigEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
  if (editorConfiguration['templateName'] === 'Inspector-DmfConfigTextareaEditor') {
    renderTextareaEditor(
      editorConfiguration,
      editorHtml,
      collectionElementIdentifier,
      collectionName
    );
    const textarea = $('textarea', $(editorHtml))[0];
    const formId = getFormIdentifier();
    const formName = getFormName();
    textarea.dataset.contextIdentifier = formId;
    textarea.dataset.uid = formId;
    textarea.dataset.contextType = 'form';
    textarea.dataset.documentName = formName;
    textarea.dataset.app = 'true';
    document.dispatchEvent(new Event('dmf-configuration-editor-init'));
  }
}

/**
 * @private
 *
 * @return void
 */
function _subscribeEvents() {
  getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
    handleDmfConfigEditor(...args);
  });
}

/**
 * @public
 *
 * @param object formEditorApp
 * @return void
 */
export function bootstrap(formEditorApp) {
  _formEditorApp = formEditorApp;
  _subscribeEvents();
}
