// Resources/Public/JavaScript/Backend/FormEditor/DmfConfigTextareaEditorViewModel.js

define([
  'jquery',
  'TYPO3/CMS/Form/Backend/FormEditor/Helper'
], function ($, Helper) {
  'use strict';

  return (function ($, Helper) {

    /**
     * @private
     *
     * @var object
     */
    var _formEditorApp = null;

    /**
     * @private
     *
     * @return object
     */
    function getFormEditorApp() {
      return _formEditorApp;
    };

    /**
     * @private
     *
     * @return object
     */
    function getPublisherSubscriber() {
      return getFormEditorApp().getPublisherSubscriber();
    };

    /**
     * @private
     *
     * @return object
     */
    function getUtility() {
      return getFormEditorApp().getUtility();
    };

    /**
     * @private
     *
     * @param object
     * @return object
     */
    function getHelper() {
      return Helper;
    };

    /**
     * @private
     *
     * @return object
     */
    function getCurrentlySelectedFormElement() {
      return getFormEditorApp().getCurrentlySelectedFormElement();
    };

    /**
     * @private
     *
     * @param mixed test
     * @param string message
     * @param int messageCode
     * @return void
     */
    function assert(test, message, messageCode) {
      return getFormEditorApp().assert(test, message, messageCode);
    };

    /**
     * @private
     *
     * @return void
     * @throws 1491643380
     */
    function _helperSetup() {
      assert('function' === $.type(Helper.bootstrap),
        'The view model helper does not implement the method "bootstrap"',
        1491643380
      );
      Helper.bootstrap(getFormEditorApp());
    };

    /**
     * @public
     *
     * @param object editorConfiguration
     * @param object editorHtml
     * @param string collectionElementIdentifier
     * @param string collectionName
     * @return void
     * @throws 1475412567
     * @throws 1475412568
     * @throws 1475416098
     * @throws 1475416099
     */
    function renderTextareaEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
      var propertyPath, propertyData;
      assert(
        'object' === $.type(editorConfiguration),
        'Invalid parameter "editorConfiguration"',
        1475412567
      );
      assert(
        'object' === $.type(editorHtml),
        'Invalid parameter "editorHtml"',
        1475412568
      );
      assert(
        getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
        'Invalid configuration "propertyPath"',
        1475416098
      );
      assert(
        getUtility().isNonEmptyString(editorConfiguration['label']),
        'Invalid configuration "label"',
        1475416099
      );

      propertyPath = getFormEditorApp()
        .buildPropertyPath(editorConfiguration['propertyPath'], collectionElementIdentifier, collectionName);

      getHelper()
        .getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration['label']);

      if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
        getHelper()
          .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
          .text(editorConfiguration['fieldExplanationText']);
      } else {
        getHelper()
          .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
          .remove();
      }

      propertyData = getCurrentlySelectedFormElement().get(propertyPath);
      $('textarea', $(editorHtml)).val(propertyData);

      $('textarea', $(editorHtml)).on('keyup paste', function() {
        getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
      });
    };

    function handleDmfConfigEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
      switch (editorConfiguration['templateName']) {
        case 'Inspector-DmfConfigTextareaEditor':
          renderTextareaEditor(
            editorConfiguration,
            editorHtml,
            collectionElementIdentifier,
            collectionName
          );
          $('textarea', $(editorHtml))[0].dataset.app="true";
          document.dispatchEvent(new Event('dmf-start-app'));
          break;
      }
    }

    /**
     * @private
     *
     * @return void
     */
    function _subscribeEvents() {
      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElement
       *              args[1] = template
       * @return void
       */
      getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
        handleDmfConfigEditor(...args);
      });
    };

    /**
     * @public
     *
     * @param object formEditorApp
     * @return void
     */
    function bootstrap(formEditorApp) {
      _formEditorApp = formEditorApp;
      _helperSetup();
      _subscribeEvents();
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      bootstrap: bootstrap
    };
  })($, Helper);
});
