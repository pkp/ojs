{**
 * templates/common/header.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *}
{capture assign="deprecatedThemeStyles"}
	{* FIXME: This should eventually be moved into a theme plugin. *}
	<link rel="stylesheet" type="text/css" media="all" href="{$baseUrl}/lib/pkp/styles/themes/default/theme.css" />
	<link rel="stylesheet" type="text/css" media="all" href="{$baseUrl}/lib/pkp/styles/lib/selectBox/jquery.selectBox.css" />
{/capture}
{include file="core:common/header.tpl"}
