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
    $('textarea', $(editorHtml))[0].dataset.app="true";
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
