{**
 * templates/common/header.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *}
{capture assign="deprecatedThemeStyles"}
	<link rel="stylesheet" type="text/css" media="all" href="{$baseUrl}/lib/pkp/styles/lib/selectBox/jquery.selectBox.css" />
{/capture}
{include file="core:common/header.tpl"}
