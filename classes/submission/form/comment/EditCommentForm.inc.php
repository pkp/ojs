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


class EditCommentForm extends Form {

	/** @var int the ID of the article */
	var $articleId;

	/** @var int the ID of the comment */
	var $commentId;
	
	/** @var ArticleComment the comment */
	var $comment;
	
	/** @var int the role of the comment author */
	var $roleId;
	
	/** @var User the user */
	var $user;

	/**
	 * Constructor.
	 * @param $commentId int
	 */
	function EditCommentForm($commentId) {
		parent::Form('submission/comment/editComment.tpl');
		$this->commentId = $commentId;
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$this->comment = &$articleCommentDao->getArticleCommentById($commentId);
		$this->roleId = $this->comment->getRoleId();

		$this->articleId = $this->comment->getArticleId();
		$this->user = &Request::getUser();
	}
	
	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment = &$this->comment;
			$this->_data = array(
				'commentId' => $comment->getCommentId(),
				'commentTitle' => $comment->getCommentTitle(),
				'comments' => $comment->getComments()
			);
		}
	}	
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('comment', $this->comment);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId,
				'commentId' => $this->commentId
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
		$comment->setViewable($this->getData('viewable'));
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
		$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->articleId);
		if ($editAssignment != null && $editAssignment->getEditorId() != null) {
			$editor = &$userDao->getUser($editAssignment->getEditorId());
		} else {
			$editor = null;
		}
		
		// Get editors
		$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
		
		// Get proofreader
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($this->articleId);
		if ($proofAssignment != null && $proofAssignment->getProofreaderId() != null) {
			$proofreader = &$userDao->getUser($proofAssignment->getProofreaderId());
		} else {
			$proofreader = null;
		}
	
		// Get layout editor
		$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &$layoutAssignmentDao->getLayoutAssignmentByArticleId($this->articleId);
		if ($layoutAssignment != null && $layoutAssignment->getEditorId() != null) {
			$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		} else {
			$layoutEditor = null;
		}

		// Get copyeditor
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
		$copyAssignment = &$copyAssignmentDao->getCopyAssignmentByArticleId($this->articleId);
		if ($copyAssignment != null && $copyAssignment->getCopyeditorId() != null) {
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
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($this->articleId);
		$author = &$userDao->getUser($article->getUserId());
	
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
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			} else {
				// Then add editor
				
				// Check to ensure that there is a section editor assigned to this article.
				// If there isn't, add all editors.
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					foreach ($editors as $editor) {
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
					foreach ($editors as $editor) {
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
			
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else {
				// Then add editor and copyeditor
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					foreach ($editors as $editor) {
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
					foreach ($editors as $editor) {
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
				
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else if ($this->roleId == ROLE_ID_LAYOUT_EDITOR) {
				// Then add editor, proofreader and author
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					foreach ($editors as $editor) {
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($proofreader != null) {
					$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
				}
			
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else if ($this->roleId == ROLE_ID_PROOFREADER) {
				// Then add editor, layout editor, and author
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					foreach ($editors as $editor) {
						$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
					}
				}
				
				if ($layoutEditor != null) {
					$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
				}
				
				$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			
			} else {
				// Then add editor, layout editor, and proofreader
				if ($editor != null) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				} else {
					foreach ($editors as $editor) {
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

		echo "<pre>";
		print_r($recipients);
		exit();

		return $recipients;
	}
	
	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 */
	function email($recipients) {
		$email = &new ArticleMailTemplate($this->articleId, 'COMMENT_EMAIL');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		
		$article = &$articleDao->getArticle($this->articleId);
		
		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);
			$email->setSubject($article->getArticleTitle());

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'articleTitle' => $article->getArticleTitle(),
				'comments' => $this->getData('comments')	
			);
			$email->assignParams($paramArray);

			$email->send();
			$email->clearRecipients();
		}
	}
}

?>
