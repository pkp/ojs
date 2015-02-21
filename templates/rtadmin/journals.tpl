{**
 * templates/rtadmin/journals.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin journal list
 *
 *}
{strip}
{assign var="pageTitle" value="rt.readingTools"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="user.myJournals"}</h3>

<ul>
	{foreach from=$journals item=journal}
		<li><a href="{url journal=$journal->getPath() page="rtadmin"}">{$journal->getLocalizedTitle()|escape}</a></li>
	{/foreach}
</ul>

{include file="common/footer.tpl"}
