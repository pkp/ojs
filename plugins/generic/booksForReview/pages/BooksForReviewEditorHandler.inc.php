<?php

/**
 * @file plugins/generic/booksForReview/pages/BooksForReviewEditorHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BooksForReviewEditorHandler
 * @ingroup plugins_generic_booksForReview
 *
 * @brief Handle requests for editor books for review functions.
 */

import('classes.handler.Handler');

class BooksForReviewEditorHandler extends Handler {

	/**
	 * Display books for review listing pages.
	 */
	function booksForReview($args = array(), &$request) {
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$mode = $bfrPlugin->getSetting($journalId, 'mode');
		$bfrPlugin->import('classes.BookForReview');
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');

		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}

		$path = !isset($args) || empty($args) ? null : $args[0];

		switch($path) {
			case 'available':
				$status = BFR_STATUS_AVAILABLE;
				$template = 'booksForReviewAvailable.tpl';
				break;
			case 'requested':
				$status = BFR_STATUS_REQUESTED;
				$template = 'booksForReviewRequested.tpl';
				break;
			case 'assigned':
				$status = BFR_STATUS_ASSIGNED;
				$template = 'booksForReviewAssigned.tpl';
				break;
			case 'mailed':
				$status = BFR_STATUS_MAILED;
				$template = 'booksForReviewMailed.tpl';
				break;
			case 'submitted':
				$status = BFR_STATUS_SUBMITTED;
				$template = 'booksForReviewSubmitted.tpl';
				break;
			default:
				$path = '';
				$status = null;
				$template = 'booksForReviewAll.tpl';
		}


		import('pages.editor.EditorHandler');
		$user =& $request->getUser();
		$filterEditorOptions = array(
			FILTER_EDITOR_ALL => AppLocale::Translate('editor.allEditors'),
			FILTER_EDITOR_ME => AppLocale::Translate('editor.me')
		);

		$filterEditor = $request->getUserVar('filterEditor');
		if ($filterEditor != '' && array_key_exists($filterEditor, $filterEditorOptions)) {
			$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
		} else {
			$filterEditor = $user->getSetting('filterEditor', $journalId);
			if ($filterEditor == null) {
				$filterEditor = FILTER_EDITOR_ALL;
				$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
			}
		}

		if ($filterEditor == FILTER_EDITOR_ME) {
			$editorId = $user->getId();
		} else {
			$editorId = null;
		}

		$rangeInfo =& Handler::getRangeInfo('booksForReview');
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		$booksForReview =& $bfrDao->getBooksForReviewByJournalId($journalId, $searchField, $search, $searchMatch, $status, null, $editorId, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('mode', $mode);
		$templateMgr->assign_by_ref('booksForReview', $booksForReview);
		$templateMgr->assign('filterEditor', $filterEditor);
		$templateMgr->assign('returnPage', $path);

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$fieldOptions = Array(
			BFR_FIELD_TITLE => 'plugins.generic.booksForReview.field.title',
			BFR_FIELD_PUBLISHER => 'plugins.generic.booksForReview.field.publisher',
			BFR_FIELD_YEAR => 'plugins.generic.booksForReview.field.year',
			BFR_FIELD_ISBN => 'plugins.generic.booksForReview.field.isbn',
			BFR_FIELD_DESCRIPTION => 'plugins.generic.booksForReview.field.description'
		);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign('editorOptions', $filterEditorOptions);
		$templateMgr->assign_by_ref('counts', $bfrDao->getStatusCounts($journalId));

		$templateMgr->display($bfrPlugin->getTemplatePath() . 'editor' . '/' . $template);
	}

	/**
	 * Create/edit book for review.
	 */
	function createBookForReview($args = array(), &$request) {
		$this->editBookForReview($args, $request);
	}

	/**
	 * Create/edit book for review.
	 */
	function editBookForReview($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$mode = $bfrPlugin->getSetting($journalId, 'mode');
		$bookId = !isset($args) || empty($args) ? null : (int) $args[0];
		$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is valid and for this journal
		if (($bookId != null && $bfrDao->getBookForReviewJournalId($bookId) == $journalId) || ($bookId == null)) {
			$bfrPlugin->import('classes.form.BookForReviewForm');

			$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$journalSettings =& $journalSettingsDao->getJournalSettings($journalId);

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$countries =& $countryDao->getCountries();

			// PHP4 Requires explicit instantiation-by-reference
			if (checkPhpVersion('5.0.0')) {
				$bfrForm = new BookForReviewForm(BOOKS_FOR_REVIEW_PLUGIN_NAME, $bookId);
			} else {
				$bfrForm =& new BookForReviewForm(BOOKS_FOR_REVIEW_PLUGIN_NAME, $bookId);
			}
			$bfrForm->initData();
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('mode', $mode);
			$templateMgr->assign('journalSettings', $journalSettings);
			$templateMgr->assign('returnPage', $returnPage);
			$templateMgr->assign_by_ref('countries', $countries);
			$bfrForm->display();
		} else {
			$request->redirect(null, 'editor', 'booksForReview', $returnPage);
		}
	}

	/**
	 * Update book for review.
	 */
	function updateBookForReview($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$mode = $bfrPlugin->getSetting($journalId, 'mode');
		$bfrPlugin->import('classes.form.BookForReviewForm');
		$bookId = $request->getUserVar('bookId') == null ? null : (int) $request->getUserVar('bookId');
		$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		if (($bookId != null && $bfrDao->getBookForReviewJournalId($bookId) == $journalId) || $bookId == null) {

			// PHP4 Requires explicit instantiation-by-reference
			if (checkPhpVersion('5.0.0')) {
				$bfrForm = new BookForReviewForm(BOOKS_FOR_REVIEW_PLUGIN_NAME, $bookId);
			} else {
				$bfrForm =& new BookForReviewForm(BOOKS_FOR_REVIEW_PLUGIN_NAME, $bookId);
			}
			$bfrForm->readInputData();

			// Add an author
			if ($request->getUserVar('addAuthor')) {
				$editData = true;
				$authors = $bfrForm->getData('authors');
				array_push($authors, array());
				$bfrForm->setData('authors', $authors);

			// Delete authors
			} else if (($delAuthor = $request->getUserVar('delAuthor')) && count($delAuthor) == 1) {
				$editData = true;
				list($delAuthor) = array_keys($delAuthor);
				$delAuthor = (int) $delAuthor;
				$authors = $bfrForm->getData('authors');
				if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
					$deletedAuthors = explode(':', $bfrForm->getData('deletedAuthors'));
					array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
					$bfrForm->setData('deletedAuthors', join(':', $deletedAuthors));
				}
				array_splice($authors, $delAuthor, 1);
				$bfrForm->setData('authors', $authors);

			// Change author order
			} else if ($request->getUserVar('moveAuthor')) {
				$editData = true;
				$moveAuthorDir = $request->getUserVar('moveAuthorDir');
				$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
				$moveAuthorIndex = (int) $request->getUserVar('moveAuthorIndex');
				$authors = $bfrForm->getData('authors');

				if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
					$tmpAuthor = $authors[$moveAuthorIndex];
					if ($moveAuthorDir == 'u') {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
						$authors[$moveAuthorIndex - 1] = $tmpAuthor;
					} else {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
						$authors[$moveAuthorIndex + 1] = $tmpAuthor;
					}
				}
				$bfrForm->setData('authors', $authors);
			}

			if (!isset($editData) && $bfrForm->validate()) {
				$bfrForm->execute();

				if ($bookId == null) {
					$notificationType = NOTIFICATION_TYPE_BOOK_CREATED;
				} else {
					$notificationType = NOTIFICATION_TYPE_BOOK_UPDATED;
				}
				$user =& $request->getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), $notificationType);
				if ($request->getUserVar('createAnother')) {
					$request->redirect(null, 'editor', 'createBookForReview', null, array('returnPage' => $returnPage));
				} else {
					$request->redirect(null, 'editor', 'booksForReview', $returnPage);
				}
			} else {
				$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
				$journalSettings =& $journalSettingsDao->getJournalSettings($journal->getId());
				$countryDao =& DAORegistry::getDAO('CountryDAO');
				$countries =& $countryDao->getCountries();

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('mode', $mode);
				$templateMgr->assign('journalSettings', $journalSettings);
				$templateMgr->assign('returnPage', $returnPage);
				$templateMgr->assign_by_ref('countries', $countries);
				$bfrForm->display();
			}
		} else {
			$request->redirect(null, 'editor');
		}
	}

	/**
	 * Delete book for review.
	 */
	function deleteBookForReview($args = array(), &$request) {
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);

		if (!empty($args)) {
			$bookId = (int) $args[0];
			$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');

			if ($returnPage != null) {
				$validPages =& $this->getValidReturnPages();
				if (!in_array($returnPage, $validPages)) {
					$returnPage = null;
				}
			}

			$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

			// Ensure book for review is for this journal
			if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
				$bfrDao->deleteBookForReviewById($bookId);
				$user =& $request->getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_DELETED);
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Update book for review settings.
	 */
	function booksForReviewSettings($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$bfrPlugin->import('classes.form.BooksForReviewSettingsForm');
		$templateMgr =& TemplateManager::getManager();

		// PHP4 Requires explicit instantiation-by-reference
		if (checkPhpVersion('5.0.0')) {
			$form = new BooksForReviewSettingsForm($bfrPlugin, $journalId);
		} else {
			$form =& new BooksForReviewSettingsForm($bfrPlugin, $journalId);
		}

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		$templateMgr->assign_by_ref('counts', $bfrDao->getStatusCounts($journalId));
		if ($request->getUserVar('save')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
				$user =& $request->getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED);

				$request->redirect(null, 'editor', 'booksForReviewSettings');
			} else {
				$form->display();
			}
		} else {
			$form->initData();
			$form->display();
		}
	}

	/**
	 * Display a list of authors from which to choose a book reviewer.
	 */
	function selectBookForReviewAuthor($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$bookId = (int) $args[0];
		$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) != $journalId) {
			$request->redirect(null, 'editor', 'booksForReview', $returnPage);
		}

		$templateMgr =& TemplateManager::getManager();
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_AUTHOR, $journalId, $searchType, $search, $searchMatch, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		import('classes.security.Validation');
		$templateMgr->assign('isJournalManager', Validation::isJournalManager());

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));

		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'journal.roles.author');
		$templateMgr->assign('bookId', $bookId);
		$templateMgr->assign('returnPage', $returnPage);
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->display($bfrPlugin->getTemplatePath() . 'editor' . '/' . 'authors.tpl');
	}

	/**
	 * Display a list of submissions from which to choose a book review submission.
	 */
	function selectBookForReviewSubmission($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$bookId = (int) $args[0];
		$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) != $journalId) {
			$request->redirect(null, 'editor', 'booksForReview', $returnPage);
		}

		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
		$templateMgr =& TemplateManager::getManager();

		$searchField = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}

		$user =& $request->getUser();
		$editorId = $user->getId();
		$rangeInfo = Handler::getRangeInfo('submissions');

		import('lib.pkp.classes.db.DAO');
		$submissions =& $editorSubmissionDao->getEditorSubmissions(
			$journalId,
			0,
			$editorId,
			$searchField,
			$searchMatch,
			$search,
			null,
			null,
			null,
			$rangeInfo,
			'id',
			SORT_DIRECTION_DESC
		);

		$templateMgr->assign('searchField', $searchField);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);

		$templateMgr->assign('fieldOptions', array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author'
		));

		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('helpTopicId', 'journal.roles.editor');
		$templateMgr->assign('bookId', $bookId);
		$templateMgr->assign('returnPage', $returnPage);
		$templateMgr->display($bfrPlugin->getTemplatePath() . 'editor' . '/' . 'submissions.tpl');
	}

	/**
	 * Assign a book for review submission.
	 */
	function assignBookForReviewSubmission($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$bookId = (int) $args[0];
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			$book =& $bfrDao->getBookForReview($bookId);
			$articleId = (int) $request->getUserVar('articleId');

			// Ensure article is for this journal and update book for review
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			if ($articleDao->getArticleJournalId($articleId) == $journalId) {
				$book->setArticleId($articleId);
				$book->setStatus(BFR_STATUS_SUBMITTED);
				$bfrDao->updateObject($book);
				$user =& $request->getUser();

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED);
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Assign a book for review author.
	 */
	function assignBookForReviewAuthor($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$bookId = (int) $args[0];
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			$book =& $bfrDao->getBookForReview($bookId);
			$status = $book->getStatus();

			// Book was never requested by an author
			if ($status == BFR_STATUS_AVAILABLE) {
				$userId = (int) $request->getUserVar('userId');
				$userDao =& DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getUser($userId);
				$userName = $user->getFullName();
				$userEmail = $user->getEmail();
				$userMailingAddress = $user->getMailingAddress();
				$userCountryCode = $user->getCountry();
			// Book has already been requested by author
			} else {
				$userId = $book->getUserId();
				$userName = $book->getUserFullName();
				$userEmail = $book->getUserEmail();
				$userMailingAddress = $book->getUserMailingAddress();
				$userCountryCode = $book->getUserCountry();
			}

			// Ensure user is an author for this journal
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			if ($roleDao->userHasRole($journalId, $userId, ROLE_ID_AUTHOR)) {
				import('classes.mail.MailTemplate');
				$email = new MailTemplate('BFR_BOOK_ASSIGNED');
				$send = $request->getUserVar('send');

				// Editor has filled out mail form or skipped mail
				if ($send && !$email->hasErrors()) {
					// Update book for review
					$dueWeeks = $bfrPlugin->getSetting($journalId, 'dueWeeks');
					$dueDateTimestamp = time() + ($dueWeeks * 7 * 24 * 60 * 60);
					$dueDate = date('Y-m-d H:i:s', $dueDateTimestamp);

					$book->setUserId($userId);
					$book->setStatus(BFR_STATUS_ASSIGNED);
					$book->setDateAssigned(Core::getCurrentDate());
					$book->setDateDue($dueDate);
					$bfrDao->updateObject($book);

					$email->send();
					$user =& $request->getUser();

					import('classes.notification.NotificationManager');
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED);

					$request->redirect(null, 'editor', 'booksForReview', $returnPage);

				// Display mail form for editor
				} else {
					if (!$request->getUserVar('continued')) {
						$dueWeeks = $bfrPlugin->getSetting($journalId, 'dueWeeks');
						$dueDateTimestamp = time() + ($dueWeeks * 7 * 24 * 60 * 60);

						if (empty($userMailingAddress)) {
							$userMailingAddress = __('plugins.generic.booksForReview.editor.noMailingAddress');
						} else {
							$countryDao =& DAORegistry::getDAO('CountryDAO');
							$countries =& $countryDao->getCountries();
							$userCountry = $countries[$userCountryCode];
							$userMailingAddress .= "\n" . $userCountry;
						}

						$paramArray = array(
							'authorName' => strip_tags($userName),
							'authorMailingAddress' => String::html2text($userMailingAddress),
							'bookForReviewTitle' => '"' . strip_tags($book->getLocalizedTitle()) . '"',
							'bookForReviewDueDate' => date('l, F j, Y', $dueDateTimestamp),
							'userProfileUrl' => $request->url(null, 'user', 'profile'),
							'submissionUrl' => $request->url(null, 'author', 'submit'),
							'editorialContactSignature' => String::html2text($book->getEditorContactSignature())
						);

						$email->addRecipient($userEmail, $userName);
						$email->setReplyTo($book->getEditorEmail(), $book->getEditorFullName());
						$email->assignParams($paramArray);
					}
					$returnUrl = $request->url(null, 'editor', 'assignBookForReviewAuthor', $bookId, array('returnPage' => $returnPage, 'userId' => $userId));
					$email->displayEditForm($returnUrl);
				}
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Deny a book for review request.
	 */
	function denyBookForReviewAuthor($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$bookId = (int) $args[0];
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate('BFR_BOOK_DENIED');
			$send = $request->getUserVar('send');

			// Editor has filled out mail form or skipped mail
			if ($send && !$email->hasErrors()) {
				// Update book for review
				$book =& $bfrDao->getBookForReview($bookId);

				$book->setStatus(BFR_STATUS_AVAILABLE);
				$book->setUserId(null);
				$book->setDateRequested(null);
				$bfrDao->updateObject($book);

				$email->send();
				$user =& $request->getUser();

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED);

				$request->redirect(null, 'editor', 'booksForReview', $returnPage);

			// Display mail form for editor
			} else {
				if (!$request->getUserVar('continued')) {
					$book =& $bfrDao->getBookForReview($bookId);
					$userFullName = $book->getUserFullName();
					$userEmail = $book->getUserEmail();

					$paramArray = array(
						'authorName' => strip_tags($userFullName),
						'bookForReviewTitle' => '"' . strip_tags($book->getLocalizedTitle()) . '"',
						'submissionUrl' => $request->url(null, 'author', 'submit'),
						'editorialContactSignature' => String::html2text($book->getEditorContactSignature())
					);

					$email->addRecipient($userEmail, $userFullName);
					$email->setReplyTo($book->getEditorEmail(), $book->getEditorFullName());
					$email->assignParams($paramArray);
				}
				$returnUrl = $request->url(null, 'editor', 'denyBookForReviewAuthor', $bookId, array('returnPage' => $returnPage));
				$email->displayEditForm($returnUrl);
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Mark a book for review as mailed.
	 */
	function notifyBookForReviewMailed($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$bookId = (int) $args[0];
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate('BFR_BOOK_MAILED');
			$send = $request->getUserVar('send');

			// Editor has filled out mail form or skipped mail
			if ($send && !$email->hasErrors()) {
				// Update book for review
				$book =& $bfrDao->getBookForReview($bookId);

				$book->setStatus(BFR_STATUS_MAILED);
				$book->setDateMailed(date('Y-m-d H:i:s', time()));
				$bfrDao->updateObject($book);

				$email->send();
				$user =& $request->getUser();

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_MAILED);

				$request->redirect(null, 'editor', 'booksForReview', $returnPage);

			// Display mail form for editor
			} else {
				if (!$request->getUserVar('continued')) {
					$book =& $bfrDao->getBookForReview($bookId);

					$userFullName = $book->getUserFullName();
					$userEmail = $book->getUserEmail();
					$userMailingAddress = $book->getUserMailingAddress();
					$userCountryCode = $book->getUserCountry();

					if (empty($userMailingAddress)) {
						$userMailingAddress = __('plugins.generic.booksForReview.editor.noMailingAddress');
					} else {
						$countryDao =& DAORegistry::getDAO('CountryDAO');
						$countries =& $countryDao->getCountries();
						$userCountry = $countries[$userCountryCode];
						$userMailingAddress .= "\n" . $userCountry;
					}

					$paramArray = array(
						'authorName' => strip_tags($userFullName),
						'authorMailingAddress' => String::html2text($userMailingAddress),
						'bookForReviewTitle' => '"' . strip_tags($book->getLocalizedTitle()) . '"',
						'submissionUrl' => $request->url(null, 'author', 'submit'),
						'editorialContactSignature' => String::html2text($book->getEditorContactSignature())
					);

					$email->addRecipient($userEmail, $userFullName);
					$email->setReplyTo($book->getEditorEmail(), $book->getEditorFullName());
					$email->assignParams($paramArray);
				}
				$returnUrl = $request->url(null, 'editor', 'notifyBookForReviewMailed', $bookId, array('returnPage' => $returnPage));
				$email->displayEditForm($returnUrl);
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Remove book reviewer and reset book for review.
	 */
	function removeBookForReviewAuthor($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$bookId = (int) $args[0];
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate('BFR_REVIEWER_REMOVED');
			$send = $request->getUserVar('send');

			// Editor has filled out mail form or skipped mail
			if ($send && !$email->hasErrors()) {
				// Update book for review
				$book =& $bfrDao->getBookForReview($bookId);

				$book->setStatus(BFR_STATUS_AVAILABLE);
				$book->setUserId(null);
				$book->setDateRequested(null);
				$book->setDateAssigned(null);
				$book->setDateDue(null);
				$book->setDateMailed(null);
				$book->setDateSubmitted(null);
				$book->setArticleId(null);
				$bfrDao->updateObject($book);

				$email->send();
				$user =& $request->getUser();

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED);

				$request->redirect(null, 'editor', 'booksForReview', $returnPage);

			// Display mail form for editor
			} else {
				if (!$request->getUserVar('continued')) {
					$book =& $bfrDao->getBookForReview($bookId);

					$userFullName = $book->getUserFullName();
					$userEmail = $book->getUserEmail();

					$paramArray = array(
						'authorName' => strip_tags($userFullName),
						'bookForReviewTitle' => '"' . strip_tags($book->getLocalizedTitle()) . '"',
						'editorialContactSignature' => String::html2text($book->getEditorContactSignature())
					);

					$email->addRecipient($userEmail, $userFullName);
					$email->setReplyTo($book->getEditorEmail(), $book->getEditorFullName());
					$email->assignParams($paramArray);
				}
				$returnUrl = $request->url(null, 'editor', 'removeBookForReviewAuthor', $bookId, array('returnPage' => $returnPage));
				$email->displayEditForm($returnUrl);
			}
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Remove book for review cover page image.
	 */
	function removeBookForReviewCoverPage($args = array(), &$request) {
		$this->setupTemplate();

		if (empty($args) || count($args) < 2) {
			$request->redirect(null, 'editor');
		}

		$bookId = (int) $args[0];
		$formLocale = $args[1];

		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, 'editor');
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$returnPage = $request->getUserVar('returnPage');

		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			$bfrDao->removeCoverPage($bookId, $formLocale);
			$request->redirect(null, 'editor', 'editBookForReview', $bookId, array('returnPage' => $returnPage));
		}
		$request->redirect(null, 'editor', 'booksForReview', $returnPage);
	}

	/**
	 * Return valid landing/return pages
	 */
	function &getValidReturnPages() {
		$validPages = array(
			'available',
			'requested',
			'assigned',
			'mailed',
			'submitted'
		);
		return $validPages;
	}

	/**
	 * Ensure that we have a journal, plugin is enabled, and user is editor.
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);

		if (!isset($bfrPlugin)) return false;

		if (!$bfrPlugin->getEnabled()) return false;

		if (!Validation::isEditor($journal->getId())) Validation::redirectLogin();;

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'editor'),
				'user.role.editor'
			)
		);

		if ($subclass) {
			$returnPage = Request::getUserVar('returnPage');

			if ($returnPage != null) {
				$validPages =& $this->getValidReturnPages();
				if (!in_array($returnPage, $validPages)) {
					$returnPage = null;
				}
			}

			$pageCrumbs[] = array(
				Request::url(null, 'editor', 'booksForReview', $returnPage),
				AppLocale::Translate('plugins.generic.booksForReview.displayName'),
				true
			);
		}
		$templateMgr->assign('pageHierarchy', $pageCrumbs);

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $bfrPlugin->getStyleSheet());
	}
}

?>
