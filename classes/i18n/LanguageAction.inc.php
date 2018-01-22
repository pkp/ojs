<?php

/**
 * @file classes/i18n/LanguageAction.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageAction
 * @ingroup i18n
 *
 * @brief LanguageAction class.
 */

define('LANGUAGE_PACK_DESCRIPTOR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/locales.xml');
define('LANGUAGE_PACK_TAR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/%s.tar.gz');

import('lib.pkp.classes.i18n.PKPLanguageAction');

class LanguageAction extends PKPLanguageAction {
}

?>
