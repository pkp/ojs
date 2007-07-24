<?php

/**
 * @file index.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Wrapper for PubMed export plugin.
 *
 * @package plugins.importexport.pubmed
 *
 * $Id$
 */

require_once('PubMedExportPlugin.inc.php');

return new PubMedExportPlugin();

?>
