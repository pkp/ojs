{**
 * templates/article/header.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for article pages.
 *}
{strip}
{if $article}
	{assign var="pageTitleTranslated" value=$article->getLocalizedTitle()|escape}
{/if}
{include file="common/header.tpl"}
{/strip}