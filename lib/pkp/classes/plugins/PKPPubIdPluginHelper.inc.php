<?php

/**
 * @file classes/plugins/PKPPubIdPluginHelper.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPubIdPluginHelper
 * @ingroup plugins
 *
 * @brief Helper class for public identifiers plugins
 */


class PKPPubIdPluginHelper {

	/**
	 * Validate the additional form fields from public identifier plugins.
	 * @param $contextId integer
	 * @param $form object PKPPublicIdentifiersForm
	 * @param $pubObject object
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function validate($contextId, $form, $pubObject) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$fieldNames = $pubIdPlugin->getFormFieldNames();
				foreach ($fieldNames as $fieldName) {
					$fieldValue = $form->getData($fieldName);
					$errorMsg = '';
					if(!$pubIdPlugin->verifyData($fieldName, $fieldValue, $pubObject, $contextId, $errorMsg)) {
						$form->addError($fieldName, $errorMsg);
					}
				}
			}
		}
	}

	/**
	 * Set form link actions.
	 * @param $contextId integer
	 * @param $form object PKPPublicIdentifiersForm
	 * @param $pubObject object
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function setLinkActions($contextId, $form, $pubObject) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$linkActions = $pubIdPlugin->getLinkActions($pubObject);
				foreach ($linkActions as $linkActionName => $linkAction) {
					$form->setData($linkActionName, $linkAction);
					unset($linkAction);
				}
			}
		}
	}

	/**
	 * Add pub id plugins JavaScripts.
	 * @param $contextId integer
	 * @param $request PKPRequest
	 * @param $templateMgr PKPTemplateManager
	 */
	function addJavaScripts($contextId, $request, $templateMgr) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$pubIdPlugin->addJavaScript($request, $templateMgr);
			}
		}
	}

	/**
	 * Init the additional form fields from public identifier plugins.
	 * @param $contextId integer
	 * @param $form object PKPPublicIdentifiersForm|CatalogEntryFormatMetadataForm
	 * @param $pubObject object
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function init($contextId, $form, $pubObject) {
		if (isset($pubObject)) {
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
			if (is_array($pubIdPlugins)) {
				foreach ($pubIdPlugins as $pubIdPlugin) {
					$fieldNames = $pubIdPlugin->getFormFieldNames();
					foreach ($fieldNames as $fieldName) {
						$form->setData($fieldName, $pubObject->getData($fieldName));
					}
				}
			}
		}
	}

	/**
	 * Read the additional input data from public identifier plugins.
	 * @param $contextId integer
	 * @param $form object PKPPublicIdentifiersForm
	 */
	function readInputData($contextId, $form) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$form->readUserVars($pubIdPlugin->getFormFieldNames());
				$form->readUserVars(array($pubIdPlugin->getAssignFormFieldName()));
			}
		}
	}

	/**
	 * Read the the public identifiers' assign form field data.
	 * @param $form object Form containing the assign check box
	 * 	PKPAssignPublicIdentifiersForm
	 * 	OJS IssueEntryPublicationMetadataForm
	 */
	function readAssignInputData($form) {
		$application = Application::getApplication();
		$request = $application->getRequest();
		$context = $request->getContext();
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$form->readUserVars(array($pubIdPlugin->getAssignFormFieldName()));
			}
		}
	}

	/**
	 * Set the additional data from public identifier plugins.
	 * @param $contextId integer
	 * @param $form object PKPPublicIdentifiersForm
	 * @param $pubObject object
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function execute($contextId, $form, $pubObject) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				// Public ID data can only be changed as long
				// as no ID has been generated.
				$storedId = $pubObject->getStoredPubId($pubIdPlugin->getPubIdType());
				if (!$storedId) {
					$fieldNames = $pubIdPlugin->getFormFieldNames();
					foreach ($fieldNames as $fieldName) {
						$data = $form->getData($fieldName);
						$pubObject->setData($fieldName, $data);
					}
					if ($form->getData($pubIdPlugin->getAssignFormFieldName())) {
						$pubId = $pubIdPlugin->getPubId($pubObject);
						$pubObject->setStoredPubId($pubIdPlugin->getPubIdType(), $pubId);
					}
				}
			}
		}
	}

	/**
	 * Assign public identifier.
	 * @param $contextId integer
	 * @param $form object
	 * @param $pubObject object
	 * @param $save boolean Whether the pub id shall be saved here
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function assignPubId($contextId, $form, $pubObject, $save = false) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($form->getData($pubIdPlugin->getAssignFormFieldName())) {
					$pubId = $pubIdPlugin->getPubId($pubObject);
					if ($save) {
						$pubIdPlugin->setStoredPubId($pubObject, $pubId);
					} else {
						$pubObject->setStoredPubId($pubIdPlugin->getPubIdType(), $pubId);
					}
				}
			}
		}
	}

	/**
	 * Clear a pubId from a pubObject.
	 * @param $contextId integer
	 * @param $pubIdPlugInClassName string
	 * @param $pubObject object
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	function clearPubId($contextId, $pubIdPlugInClassName, $pubObject) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if (get_class($pubIdPlugin) == $pubIdPlugInClassName) {
					// clear the pubId:
					// delete the pubId from the DB
					$dao = $pubObject->getDAO();
					$pubObjectId = $pubObject->getId();
					if (is_a($pubObject, 'SubmissionFile')) {
						$pubObjectId = $pubObject->getFileId();
					}
					$dao->deletePubId($pubObjectId, $pubIdPlugin->getPubIdType());
					// set the object setting/data 'pub-id::...' to null, in order
					// not to be considered in the DB object update later in the form
					$settingName = 'pub-id::'.$pubIdPlugin->getPubIdType();
					$pubObject->setData($settingName, null);
				}
			}
		}
	}

}

?>
