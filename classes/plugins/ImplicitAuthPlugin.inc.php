<?php

/**
 * @file classes/plugins/ImplicitAuthPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImplicitAuthPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for implicit authentication plugins
 *
 * Contributed by Dan Galewsky, University of Texas
 */

// $Id$


import('plugins.Plugin');

class ImplicitAuthPlugin extends Plugin {
	/**
	 * Authenticate a user based on some external conditions or system.
	 * Subclasses should implement this method.
	 * @return object User object for authenticated user, if authentication
	 * 	was successful; otherwise, the method should not return (i.e.
	 *	the request should be redirected to login or elsewhere).
	 */

	function implicitAuth() {
		die('ABSTRACT METHOD');
	}
}

?>
