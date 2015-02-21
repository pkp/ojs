<?php 

/**
 * @defgroup plugins
 */
 
/**
 * @file plugins/paymethod/paypal/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins
 * @brief Wrapper for PayPal plugin.
 */
 
require_once('PayPalPlugin.inc.php'); 
return new PayPalPlugin();
 
?> 
