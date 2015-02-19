<?php

/**
 * @file plugins/generic/sword/SwordHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SwordHandler
 * @ingroup plugins_generic_sword
 *
 * @brief Handle requests for author SWORD deposits
 */

import('classes.handler.Handler');

class SwordHandler extends Handler {
	/**
	 * Constructor
	 **/
	function SwordHandler() {
		parent::Handler();
	}

	/**
	 * Display index page.
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$articleId = (int) array_shift($args);
		$save = array_shift($args) == 'save';

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$article =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		if (	!$article || !$user || !$journal ||
			$article->getUserId() != $user->getId() ||
			$article->getJournalId() != $journal->getId()
		) {
			$request->redirect(null, 'index');
		}

		$swordPlugin =& $this->_getSwordPlugin();
		$swordPlugin->import('AuthorDepositForm');
		$authorDepositForm = new AuthorDepositForm($swordPlugin, $article);

		if ($save) {
			$authorDepositForm->readInputData();
			if ($authorDepositForm->validate()) {
				$authorDepositForm->execute($request);
				$request->redirect(null, 'author');
			} else {
				$authorDepositForm->display();
			}
		} else {
			$authorDepositForm->initData();
			$authorDepositForm->display();
		}
	}

	/**
	 * Get the SWORD plugin object
	 * @return object
	 */
	function &_getSwordPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', SWORD_PLUGIN_NAME);
		return $plugin;
	}
}

?>
