<?php

/**
 * EditCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Edit comment form.
 *
 * $Id$
 */

import('form.Form');

class EditCommentForm extends Form {

	/** @var object the article */
	var $article;

	/** @var ArticleComment the comment */
	var $comment;
	
	/** @var int the role of the comment author */
	var $roleId;
	
	/** @var User the user */
	var $user;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $comment object
	 */
	function EditCommentForm(&$article, &$comment) {
		parent::Form('submission/comment/editComment.tpl');
		
		$this->comment = $comment;
		$this->roleId = $comment->getRoleId();

		$this->article = $article;
		$this->user = &Request::getUser();
	}
	
	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		$comment = &$this->comment;
		$this->_data = array(
			'commentId' => $comment->getCommentId(),
			'commentTitle' => $comment->getCommentTitle(),
			'comments' => $comment->getComments(),
			'viewable' => $comment->getViewable(),
		);
	}	
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('comment', $this->comment);
		$templateMgr->assign('commentType', $this->comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW ? 'peerReview' : ''); // FIXME
		$templateMgr->assign('canEmail', $this->roleId == ROLE_ID_REVIEWER ? false : true);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->article->getArticleId(),
				'commentId' => $this->comment->getCommentId()
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'comments',
				'viewable'
			)
		);
	}
	
	/**
	 * Update the comment.
	 */
	function execute() {
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	
		// Update comment		
		$comment = $this->comment;
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setViewable($this->getData('viewable') ? 1 : 0);
		$comment->setDateModified(Core::getCurrentDate());
		
		$commentDao->updateArticleComment($comment);
	}
	
	/**
	 * UGLEEE function that gets the recipients for a comment.
	 * @return $recipients array of recipients (email address => name)
	 */
	function emailHelper() {
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		
		$recipients = array();
		
		// Get editor
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->article->getArticleId());
		if ($editAssignment != null && $editAssignment->getEditorId() != null) {
			$editor = &$userDao->getUser($editAssignment->getEditorId());
		} else {
			$editor = null;
		}
		
		// Get editors
		$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
		
		// Get proofreader
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($this->article->getArticleId());
		if ($proofAssignment != null && $proofAssignment->getProofreaderId() > 0) {
			$proofreader = &$userDao->getUser($proofAssignment->getProofreaderId());
		} else {
			$proofreader = null;
		}
	
		// Get layout editor
		$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &$layoutAssignmentDao->getLayoutAssignmentByArticleId($this->article->getArticleId());
		if ($layoutAssignment != null && $layoutAssignment->getEditorId() > 0) {
			$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		} else {
			$layoutEditor = null;
		}

		// Get copyeditor
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
		$copyAssignment = &$copyAssignmentDao->getCopyAssignmentByArticleId($this->article->getArticleId());
		if ($copyAssignment != null && $copyAssignment->getCopyeditorId() > 0) {
			$copyeditor = &$userDao->getUser($copyAssignment->getCopyeditorId());
		} else {
			$copyeditor = null;
		}
		
		// Get reviewer
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($this->comment->getAssocId());
		if ($reviewAssignment != null && $reviewAssignment->getReviewerId() != null) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		} else {
			$reviewer = null;
		}
		
		// Get author
		$author = &$userDao->getUser($this->article->getUserId());
	
		switch ($this->comment->getCommentType()) {
		case COMMENT_TYPE_PEER_REVIEW:
			if ($this->roleId == ROLE_ID_EDITOR) {
				// Then add reviewer
				if ($reviewer != null) {
					$recipients = array_merge($recipients, array($reviewer->getEmail() => $reviewer->getFullName()));
				}
			}
			break;

		case COMMENT_TYPE_EDITOR_DECISION:
			if ($this->roleId == ROLE_ID_EDITOR) {
				// Then add author
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			} else {
				// Then add editor
				
				// Check to ensure that there is a section editor assigned to this article.
				// If there isn't, add all editors.
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
			}
			break;

		case COMMENT_TYPE_COPYEDIT:
			if ($this->roleId == ROLE_ID_EDITOR) {
				// Then add copyeditor and author
				if ($copyeditor != null) {
					$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
				}
				
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else if ($this->roleId == ROLE_ID_COPYEDITOR) {
				// Then add editor and author
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
			
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else {
				// Then add editor and copyeditor
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($copyeditor != null) {
					$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
				}
			}
			break;
		case COMMENT_TYPE_LAYOUT:
			if ($this->roleId == ROLE_ID_EDITOR) {
				// Then add layout editor
				
				// Check to ensure that there is a layout editor assigned to this article.
				if ($layoutEditor != null) {
					$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
				}
			} else {
				// Then add editor
	
				// Check to ensure that there is a section editor assigned to this article.
				// If there isn't, I add all editors.
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
			}
			break;
		case COMMENT_TYPE_PROOFREAD:
			if ($this->roleId == ROLE_ID_EDITOR) {
				// Then add layout editor, proofreader and author
				if ($layoutEditor != null) {
					$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
				}
				
				if ($proofreader != null) {
					$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
				}
				
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else if ($this->roleId == ROLE_ID_LAYOUT_EDITOR) {
				// Then add editor, proofreader and author
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($proofreader != null) {
					$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
				}
			
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else if ($this->roleId == ROLE_ID_PROOFREADER) {
				// Then add editor, layout editor, and author
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($layoutEditor != null) {
					$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
				}
				
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else {
				// Then add editor, layout editor, and proofreader
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					while (!$editors->eof()) {
						$editor = &$editors->next();
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($layoutEditor != null) {
					$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
				}
				
				if ($proofreader != null) {
					$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
				}
			}
			break;
		}

		return $recipients;
	}
	
	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 */
	function email($recipients) {
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($this->article, 'SUBMISSION_COMMENT');

		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);
			$email->setSubject($this->article->getArticleTitle());

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'comments' => $this->getData('comments')	
			);
			$email->assignParams($paramArray);

			$email->send();
			$email->clearRecipients();
		}
	}
}

?>
