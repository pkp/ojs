{**
 * plugins/generic/htmlArticleGalley/display.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a HTML galley.
 *}
{if $galley}
	{foreach from=$styleUrls item=styleUrl}
		<link href="{$styleUrl|escape}" media="all" type="text/css" rel="stylesheet"/>
	{/foreach}
	{$htmlGalleyContents}
{/if}
