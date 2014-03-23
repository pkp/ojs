<?php
import('classes.plugins.ImportExportPlugin');
import('file.ArticleFileManager');
class vinnipoohPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
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
		return 'vinnipoohPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.vinnipoohPlugin.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.vinnipoohPlugin.description');
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$journal =& $request->getJournal();
		switch (array_shift($args)) {
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$this->exportIssue($journal, $issue);
				break;
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));
				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportIssue(&$journal, &$issue, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument(NULL, NULL, NULL);
		$doc->formatOutput=true;
		$issueNode =& NativeExportDom::generateIssueDom($doc, $journal, $issue);
		XMLCustomWriter::appendChild($doc, $issueNode);
		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"issue-" . $issue->getId() . ".xml\"");
			//$doc->schemaValidate('plugins/importexport/vinnipoohPlugin/shema.xsd');
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		$returner =& $parser->parse($fileName);
		return $returner;
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}
}
?>
