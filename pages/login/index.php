<?php

/**
 * @defgroup pages_login Login Pages
 */
 
/**
 * @file pages/login/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle login/logout requests.
 *
 * @ingroup pages_login
 */

switch ($op) {
	case 'index':
	case 'implicitAuthLogin':
	case 'implicitAuthReturn':
	case 'signIn':
	case 'signOut':
	case 'lostPassword':
	case 'requestResetPassword':
	case 'resetPassword':
	case 'changePassword':
	case 'savePassword':
	case 'signInAsUser':
	case 'signOutAsUser':
		define('HANDLER_CLASS', 'LoginHandler');
		import('pages.login.LoginHandler');
		break;
}

?>
