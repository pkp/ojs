{**
 * templates/header/index.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header div contents.
 *}
{assign var="logoImage" value="templates/images/structure/ojs_logo.png"}
{if $currentJournal && $multipleContexts}
	{url|assign:"homeUrl" journal="index"}
{else}
	{url|assign:"homeUrl" page="index"}
{/if}
{include file="core:header/index.tpl"}
