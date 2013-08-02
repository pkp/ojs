<?php

/**
 * @file plugins/importexport/mets/MetsExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 * @ingroup plugins
 *
 * @brief METS/MODS XML metadata export plugin
 */

import('classes.plugins.ImportExportPlugin');

class METSExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
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
		return 'METSExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.METSExport.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.METSExport.description');
	}

	function display(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$journal = $request->getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = $issueDao->getById($issueId);
					if (!$issue) $request->redirect();
					$issues[] = $issue;
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = $issueDao->getById($issueId);
				if (!$issue) $request->redirect();
				$issues = array($issue);
				$this->exportIssues($journal, $issues);
				break;
			case 'issues':
				// Display a list of issues for export
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getIssues($journal->getId(), Handler::getRangeInfo($this->getRequest(), 'issues'));

				$siteDao = DAORegistry::getDAO('SiteDAO');
				$site = $siteDao->getSite();
				$organization = $site->getLocalizedTitle();

				$templateMgr->assign('issues', $issues);
				$templateMgr->assign('organization', $organization);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			default:
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportIssues(&$journal, &$issues){
		$this->import('MetsExportDom');
		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'journal');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'J-'.$journal->getId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$headerNode =& MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $headerNode);
		MetsExportDom::generateJournalDmdSecDom($doc, $root, $journal);
		$fileSec =& XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrpOriginal =& XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrpOriginal, 'USE', 'original');
		$fileGrpDerivative =& XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrpDerivative, 'USE', 'derivative');
		foreach ($issues as $issue) {
			MetsExportDom::generateIssueDmdSecDom($doc, $root, $issue, $journal);
			MetsExportDom::generateIssueFileSecDom($doc, $fileGrpOriginal, $issue, $journal);
			MetsExportDom::generateIssueHtmlGalleyFileSecDom($doc, $fileGrpDerivative, $issue, $journal);
		}
		$amdSec =& MetsExportDom::createmetsamdSec($doc, $root, $journal);
		XMLCustomWriter::appendChild($root, $amdSec);
		XMLCustomWriter::appendChild($fileSec, $fileGrpOriginal);
		XMLCustomWriter::appendChild($fileSec, $fileGrpDerivative);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateStructMap($doc, $root, $journal, $issues);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"".$journal->getPath()."-mets.xml\"");
		XMLCustomWriter::printXML($doc);
		return true;
	}
}

?>
