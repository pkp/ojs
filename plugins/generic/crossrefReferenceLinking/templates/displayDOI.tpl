{**
 * plugins/generic/crossrefReferenceLinking/templates/displayDOI.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display reference DOI on the article metadata (backend) and article view page (frontend)
 *
 *}
 
DOI: <a href="{$crossrefFullUrl|escape}">{$crossrefFullUrl|escape}</a>
