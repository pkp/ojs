<?php

/**
 * FileHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for files browser functions. 
 *
 * $Id$
 */

class FileHandler extends ManagerHandler {

	/**
	 * Display the files associated with a journal.
	 */
	function files($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array('manager', 'manager.journalManagement')));
		
		$base = implode($args, "/");
		 array_pop($args);
		$prev = implode($args, "/");
		if ($base != "") {
			$base .= "/";
		}
		$dir = Config::getVar('files', 'files_dir') . '/journals/' . $journal->getJournalId() .'/' . $base ;
		$files = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != ".") {
					if (($file != "..") || ($base != '')) {
						$info = array();
						$info["name"] = $file;
						$info["type"] = filetype($dir . $file);
						$files[] = $info;
					}
				}
			}
			closedir($dh);
		}
		
		$templateMgr->assign('files', $files);
		$templateMgr->assign('base', $base);
		$templateMgr->assign('prev', $prev);
		$templateMgr->display('manager/files/index.tpl');
	}
	
	
}
?>
