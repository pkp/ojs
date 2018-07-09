<?php

/**
 * @file plugins/pubIds/doi/DOIPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */


import('classes.plugins.PubIdPlugin');

class DOIPubIdPlugin extends PubIdPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled($mainContextId)) {
			HookRegistry::register('CitationStyleLanguage::citation', array($this, 'getCitationData'));
			HookRegistry::register('Submission::getProperties::summaryProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Submission::getProperties::fullProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Issue::getProperties::summaryProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Issue::getProperties::fullProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Galley::getProperties::summaryProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Galley::getProperties::fullProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Submission::getProperties::values', array($this, 'modifyObjectPropertyValues'));
			HookRegistry::register('Issue::getProperties::values', array($this, 'modifyObjectPropertyValues'));
			HookRegistry::register('Galley::getProperties::values', array($this, 'modifyObjectPropertyValues'));
		}
		return $success;
	}

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.doi.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.doi.description');
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @copydoc PKPPubIdPlugin::constructPubId()
	 */
	function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) {
		return $pubIdPrefix . '/' . $pubIdSuffix;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Digital Object Identifier';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($contextId, $pubId) {
		return 'https://doi.org/'.$this->_doiURLEncode($pubId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplateResource('doiSuffixEdit.tpl');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdAssignFile()
	 */
	function getPubIdAssignFile() {
		return $this->getTemplateResource('doiAssign.tpl');
	}

	/**
	 * @copydoc PKPPubIdPlugin::instantiateSettingsForm()
	 */
	function instantiateSettingsForm($contextId) {
		$this->import('classes.form.DOISettingsForm');
		return new DOISettingsForm($this, $contextId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('doiSuffix');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getAssignFormFieldName()
	 */
	function getAssignFormFieldName() {
		return 'assignDoi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPrefixFieldName()
	 */
	function getPrefixFieldName() {
		return 'doiPrefix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixFieldName()
	 */
	function getSuffixFieldName() {
		return 'doiSuffix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getLinkActions()
	 */
	function getLinkActions($pubObject) {
		$linkActions = array();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$request = Application::getRequest();
		$userVars = $request->getUserVars();
		$userVars['pubIdPlugIn'] = get_class($this);
		// Clear object pub id
		$linkActions['clearPubIdLinkActionDoi'] = new LinkAction(
			'clearPubId',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('plugins.pubIds.doi.editor.clearObjectsDoi.confirm'),
				__('common.delete'),
				$request->url(null, null, 'clearPubId', null, $userVars),
				'modal_delete'
			),
			__('plugins.pubIds.doi.editor.clearObjectsDoi'),
			'delete',
			__('plugins.pubIds.doi.editor.clearObjectsDoi')
		);

		if (is_a($pubObject, 'Issue')) {
			// Clear issue objects pub ids
			$linkActions['clearIssueObjectsPubIdsLinkActionDoi'] = new LinkAction(
				'clearObjectsPubIds',
				new RemoteActionConfirmationModal(
					$request->getSession(),
					__('plugins.pubIds.doi.editor.clearIssueObjectsDoi.confirm'),
					__('common.delete'),
					$request->url(null, null, 'clearIssueObjectsPubIds', null, $userVars),
					'modal_delete'
				),
				__('plugins.pubIds.doi.editor.clearIssueObjectsDoi'),
				'delete',
				__('plugins.pubIds.doi.editor.clearIssueObjectsDoi')
			);
		}

		return $linkActions;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixPatternsFieldNames()
	 */
	function getSuffixPatternsFieldNames() {
		return  array(
			'Issue' => 'doiIssueSuffixPattern',
			'Submission' => 'doiSubmissionSuffixPattern',
			'Representation' => 'doiRepresentationSuffixPattern'
		);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::doi');
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function isObjectTypeEnabled($pubObjectType, $contextId) {
		return (boolean) $this->getSetting($contextId, "enable${pubObjectType}Doi");
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function getNotUniqueErrorMsg() {
		return __('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
	}

	/**
	 * @copydoc PKPPubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		return preg_match('/^\d+(.\d+)+\//', $pubId);
	}

	/*
	 * Public methods
	 */
	/**
	 * Add DOI to citation data used by the CitationStyleLanguage plugin
	 *
	 * @see CitationStyleLanguagePlugin::getCitation()
	 * @param $hookname string
	 * @param $args array
	 * @return false
	 */
	public function getCitationData($hookname, $args) {
		$citationData = $args[0];
		$article = $args[2];
		$issue = $args[3];
		$journal = $args[4];

		if ($issue && $issue->getPublished()) {
			$pubId = $article->getStoredPubId($this->getPubIdType());
		} else {
			$pubId = $this->getPubId($article);
		}

		if (!$pubId) {
			return;
		}

		$citationData->DOI = $pubId;
	}


	/*
	 * Private methods
	 */
	/**
	 * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
	 * @param $pubId string
	 * @return string
	 */
	function _doiURLEncode($pubId) {
		$search = array ('%', '"', '#', ' ', '<', '>', '{');
		$replace = array ('%25', '%22', '%23', '%20', '%3c', '%3e', '%7b');
		$pubId = str_replace($search, $replace, $pubId);
		return $pubId;
	}

	/**
	 * Add DOI to submission, issue or galley properties
	 *
	 * @param $hookName string <Object>::getProperties::summaryProperties or
	 *  <Object>::getProperties::fullProperties
	 * @param $args array [
	 * 		@option $props array Existing properties
	 * 		@option $object Submission|Issue|Galley
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyObjectProperties($hookName, $args) {
		$props =& $args[0];

		$props[] = 'doi';
	}

	/**
	 * Add DOI submission, issue or galley values
	 *
	 * @param $hookName string <Object>::getProperties::values
	 * @param $args array [
	 * 		@option $values array Key/value store of property values
	 * 		@option $object Submission|Issue|Galley
	 * 		@option $props array Requested properties
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyObjectPropertyValues($hookName, $args) {
		$values =& $args[0];
		$object = $args[1];
		$props = $args[2];

		// DOIs are not supported for IssueGalleys
		if (get_class($object) === 'IssueGalley') {
			return;
		}

		if (in_array('doi', $props)) {
			$pubId = $this->getPubId($object);
			$values['doi'] = $pubId ? $pubId : null;
		}
	}
}

?>
