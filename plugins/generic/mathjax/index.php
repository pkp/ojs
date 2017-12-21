<?php

/**
 * @defgroup plugins_generic_mathjax
 */
 
/**
 * @file plugins/generic/mathjax/index.php
 *
 * Copyright (c) 2017 Vasyl Ostrovskyi
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_mathjax
 * @brief Wrapper for mathjax plugin.
 *
 */

// $Id$


require_once('MathJaxPlugin.inc.php');

return new MathJaxPlugin();

?> 
