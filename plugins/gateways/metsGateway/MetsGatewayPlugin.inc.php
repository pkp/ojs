<?php

/**
 * MetsGatewayPlugin.inc.php
 *
 * Copyright (c) 2003-2005 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * A plugin to allow exposure of Journals in METS format for web service access
 *
 * $Id$
 */

import('classes.plugins.GatewayPlugin');

import('xml.XMLCustomWriter');

class METSGatewayPlugin extends GatewayPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'METSGatewayPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.gateways.metsGateway.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.gateways.metsGateway.description');
	}

	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if (!$this->getEnabled()) return $verbs;
		$verbs[] = array(
			'settings', Locale::translate('plugins.gateways.metsGateway.settings')
		);
		return $verbs;
	}

	function manage($verb, $args) {
		if (parent::manage($verb, $args)) return true;
		if (!$this->getEnabled()) return false;
		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();
				$this->import('SettingsForm');
				$form =& new SettingsForm($this, $journal->getJournalId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			default:
				return false;
		}
		return true;
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args)
	{
		if (!$this->getEnabled()) {
			return false;
		}

		if (empty($args)) {
			$errors = array();
		}
		else
		{
			$journal = &Request::getJournal();
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issueId = array_shift($args);
			if (!$issueId)
			{
				$issuesResultSet = &$issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));
				$issues = array();

				while (!$issuesResultSet->eof())
				{
					$issue = $issuesResultSet->next();
					$issues[] = &$issue;
				}
				$this->exportIssues($journal, $issues);
				return true;
			}
			else if ($issueId == 'current')
			{
				$issues = array();
				$issues[] = &$issueDao->getCurrentIssue($journal->getJournalId());
			}
			else
			{
				$issues = array();
				$issues[] = &$issueDao->getIssueById($issueId);
			}
			$this->exportIssues($journal, $issues);
			return true;
		}

		// Failure.
		header("HTTP/1.0 500 Internal Server Error");
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', 'plugins.gateways.metsGateway.errors.errorMessage');
		$templateMgr->display('common/message.tpl');
		exit;
	}

	function exportIssues(&$journal, &$issues){
		$journal =& Request::getJournal();
		$this->journalId = $journal->getJournalId();

		$this->import('MetsExportDom');
		$doc = &XMLCustomWriter::createDocument('', null);
		$root = &XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'journal');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'J-'.$journal->getJournalId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$HeaderNode = &MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $HeaderNode);
		MetsExportDom::generateJournalDmdSecDom($doc, $root, $journal);
		$fileSec = &XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrpOriginal = &XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrpOriginal, 'USE', 'original');    
		$fileGrpDerivative = &XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrpDerivative, 'USE', 'derivative');    
		foreach ($issues as $issue) {
			MetsExportDom::generateIssueDmdSecDom($doc, $root, $issue, $journal);
			MetsExportDom::generateIssueFileSecDom($doc, $fileGrpOriginal, $issue);
			MetsExportDom::generateIssueHtmlGalleyFileSecDom($doc, $fileGrpDerivative, $issue);
		}
		$amdSec = &MetsExportDom::createmetsamdSec($doc, $root, $journal);
		XMLCustomWriter::appendChild($root, $amdSec);
		XMLCustomWriter::appendChild($fileSec, $fileGrpOriginal);
		XMLCustomWriter::appendChild($fileSec, $fileGrpDerivative);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateStructMap($doc, $root, $journal, $issues);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		XMLCustomWriter::printXML($doc);
		return true;
	}
	
}
?>