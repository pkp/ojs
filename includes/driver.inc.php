<?php

/**
 * driver.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Core system initialization code.
 * This file is loaded before any others.
 * Any system-wide imports or initialization code should be placed here. 
 *
 * $Id$
 */


/**
 * Basic initialization (pre-classloading).
 */

// Useful for debugging purposes -- may want to disable for release version?
error_reporting(E_ALL);


// Update include path
define('ENV_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? ';' : ':');
if(!defined('DIRECTORY_SEPARATOR')) {
	// Older versions of PHP do not define this
	define('DIRECTORY_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? '\\' : '/');
}
define('BASE_SYS_DIR', dirname(dirname(__FILE__)));
ini_set('include_path', BASE_SYS_DIR . '/includes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/classes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/pages'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib/smarty'
	. ENV_SEPARATOR . ini_get('include_path')
);


// Seed random number generator
mt_srand(((double) microtime()) * 1000000);

// System-wide functions
require('functions.inc.php');


/**
 * System class imports.
 * Only classes used system-wide should be included here.
 */

import('core.Core');
import('core.Request');
import('core.DataObject');
import('core.Handler');
import('core.String');

import('config.Config');

import('db.DBConnection');
import('db.DAO');
import('db.XMLDAO');
import('db.DAORegistry'); 

import('form.Form');

import('i18n.Locale');
import('file.FileManager');

import('article.Article');
import('article.ArticleDAO');
import('article.Author');
import('article.AuthorDAO');
import('article.ArticleFile');
import('article.ArticleFileDAO');
import('article.SuppFile');
import('article.SuppFileDAO');

import('journal.Journal');
import('journal.JournalDAO');
import('journal.JournalSettingsDAO');
import('journal.Section');
import('journal.SectionDAO');
import('journal.SectionEditorsDAO');

import('security.Role');
import('security.RoleDAO');
import('security.Validation');

import('session.SessionManager');
import('session.Session');
import('session.SessionDAO');

import('site.Site');
import('site.SiteDAO');

import('user.User');
import('user.UserDAO');

import('template.TemplateManager');

import('mail.EmailTemplate');
import('mail.EmailTemplateDAO');
import('mail.Mail');
import('mail.MailTemplate');

import('submission.common.Action');
import('submission.sectionEditor.SectionEditorSubmission');
import('submission.sectionEditor.SectionEditorSubmissionDAO');
import('submission.sectionEditor.SectionEditorAction');
import('submission.editor.EditorSubmission');
import('submission.editor.EditorSubmissionDAO');
import('submission.editor.EditorAction');
import('submission.reviewAssignment.ReviewAssignment');
import('submission.reviewAssignment.ReviewAssignmentDAO');
import('submission.reviewer.ReviewerSubmission');
import('submission.reviewer.ReviewerSubmissionDAO');
import('submission.reviewer.ReviewerAction');
import('submission.author.AuthorSubmission');
import('submission.author.AuthorSubmissionDAO');
import('submission.author.AuthorAction');

/**
 * System initialization (post-classloading).
 */

// Initialize string wrapper library
String::init();

?>
