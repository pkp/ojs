<?php

/**
 * @file classes/submission/form/MetadataForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitterForm
 * @ingroup submission_form
 *
 * @brief Form to change submitter for a submission.
 */


import('lib.pkp.classes.form.Form');

class SubmitterForm extends Form {
	/** @var Article current article */
	var $article;

	/**
	 * Constructor.
	 */
	function SubmitterForm($article, $journal) {
		$this->article = $article;

		// check that user is allowed to edit

		parent::Form('submission/submitter/submitterEdit.tpl');
	}

        /**
         * Initialize data. Get list of authors. 
         */
        function initData() {
               if (isset($this->article)) {
			// general article info
                        $article =& $this->article;
			$submitter = $article->getUser();
                        $sessionUser =& Request::getUser();
			$roleDao =& DAORegistry::getDAO('RoleDAO');
                	$sessionUserIsJournalManager = $roleDao->roleExists($article->getJournalId(), $sessionUser->getId(), ROLE_ID_JOURNAL_MANAGER);
                        $this->_data = array(
                                'authors' => array(),
                                'title' => $article->getTitle(null), // Localized
				'submitterId' => $submitter->getUserId(),
				'submitterFirstName' => $submitter->getFirstName(),
				'submitterMiddleName' => $submitter->getMiddleName(),
				'submitterLastName' => $submitter->getLastName(),
				'submitterUsername' => $submitter->getUsername(),
				'sessionUserId' => $sessionUser->getId(),
				'sessionUserFirstName' => $sessionUser->getFirstName(),
				'sessionUserMiddleName' => $sessionUser->getMiddleName(),
				'sessionUserLastName' => $sessionUser->getLastName(),
				'sessionUsername' => $sessionUser->getUsername(),
				'sessionUserIsJournalManager' => $sessionUserIsJournalManager,
				'articleId' => $article->getArticleId()
			);
			// author info
                        $authors =& $article->getAuthors();
			$userDao =& DAORegistry::getDAO('UserDAO');
			$submitterIsAuthor = 0;
                        for ($i=0, $count=count($authors); $i < $count; $i++) {
				$userId = 0;
				$isSubmitter = 0;
				$authorEmail = trim($authors[$i]->getEmail());
				if($authorEmail) {
					$isRegisteredUser = ($userDao->userExistsByUsername($authorEmail) ? true : false);
					if($isRegisteredUser) {
						$user = ($userDao->getUserByUsername($authorEmail));	
						$userId = $user->getId();
					}
				}
				if($userId && $userId == $submitter->getUserId()) {
					$isSubmitter = 1;
					$submitterIsAuthor = 1;
				}
                                array_push(
                                        $this->_data['authors'],
                                        array(
                                                'authorId' => $authors[$i]->getId(),
						'userId' => $userId,
						'isSubmitter' => $isSubmitter,
                                                'firstName' => $authors[$i]->getFirstName(),
                                                'middleName' => $authors[$i]->getMiddleName(),
                                                'lastName' => $authors[$i]->getLastName(),
                                                'email' => trim($authors[$i]->getEmail())
                                        )
                                );
                                if ($authors[$i]->getPrimaryContact()) {
                                        $this->setData('primaryContact', $i);
                                }
                        }
			$this->setData('submitterIsAuthor', $submitterIsAuthor);
                }
                return parent::initData();
	}


        /**
         * Assign form data to user-submitted data.
         */
        function readInputData() {
                $this->readUserVars(
                        array(
                                'articleId',
				'submitter'
			)
		);
	}

	/**
	 * Save changes to submitter
	 */
	function execute(&$request) {
                $articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $this->article;
		$submitterId = $this->getData('submitter');

		// get old and new submitter info for log
		$userDao = DAORegistry::getDAO('UserDAO');
		$oldSubmitter = $userDao->getUser($article->getUserId());
		$newSubmitter = $userDao->getUser($submitterId);

		// update article
		$article->setUserId($submitterId);
		$articleDao->updateArticle($article);

		// Update search index
                import('classes.search.ArticleSearchIndex');
                ArticleSearchIndex::indexArticleMetadata($article);

		// update roles
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journal = Request::getJournal();
		$isAuthor = $roleDao->roleExists($journal->getId(), $submitterId, ROLE_ID_AUTHOR);
		if(!$isAuthor) {
			$role = new Role();
			$role->setUserId($submitterId);
			$role->setJournalId($journal->getId());
			$role->setRoleId(ROLE_ID_AUTHOR);
			$roleDao->insertRole($role);
		}

		// add log entry
                $user =& $request->getUser();
                import('classes.article.log.ArticleLog');
                import('classes.article.log.ArticleEventLogEntry');
                ArticleLog::logEvent($article->getId(), ARTICLE_LOG_SUBMITTER_UPDATE, ARTICLE_LOG_TYPE_DEFAULT, 0, 'log.editor.submitterModified', Array('oldSubmitter' => $oldSubmitter->getFullName(), 'newSubmitter' => $newSubmitter->getFullName(), 'editorName' => $user->getFullName()));

		return $article->getId();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		parent::display();
	}

}

?>
