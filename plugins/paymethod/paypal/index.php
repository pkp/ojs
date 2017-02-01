<?php 

/**
 * @defgroup paypalPlugin PayPal Plugin
 * Implements a payment handling plugin using the PayPal service.
 */
 
/**
 * @file plugins/paymethod/paypal/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup paypalPlugin
 * @brief Wrapper for PayPal plugin.
 */
 
require_once('PayPalPlugin.inc.php'); 
return new PayPalPlugin();
 
?> 
