<?php

/**
 * @defgroup plugins_implicitAuth_shibboleth Shibboleth Plugin
 */

/**
 * @file plugins/implicitAuth/shibboleth/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_implicitAuth_shibboleth
 * @brief Wrapper for the shibboletz plugin.
 *
 */

require_once('ShibAuthPlugin.inc.php');

return new ShibAuthPlugin();

?>
