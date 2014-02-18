{**
 * templates/controllers/page/header.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header div contents.
 *}
{assign var="logoImage" value="templates/images/structure/ojs_logo.png"}
{if $currentJournal && $multipleContexts}
	{url|assign:"homeUrl" journal="index" router=$smarty.const.ROUTE_PAGE}
{else}
	{url|assign:"homeUrl" page="index" router=$smarty.const.ROUTE_PAGE}
{/if}
{include file="core:controllers/page/header.tpl"}
