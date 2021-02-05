<?php

/**
 * @defgroup plugins_generic_dublinCoreMeta DublinCoreMeta plugin
 */

/**
 * @file plugins/generic/dublinCoreMeta/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_dublinCoreMeta
 * @brief Wrapper for DublinCoreMeta plugin.
 *
 */

require_once('DublinCoreMetaPlugin.inc.php');

return new DublinCoreMetaPlugin();


