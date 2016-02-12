<?php 

/**
 * @defgroup plugins
 */
 
/**
 * @file plugins/paymethod/dps/index.php
 *
 * Robert Carter <r.carter@auckland.ac.nz>
 *
 * Based on the work of these people:
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins
 * @brief Wrapper for DPS plugin.
 */
 
require_once('DpsPlugin.inc.php'); 
return new DPSPlugin();

 
?> 