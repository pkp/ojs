{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.userHome"}
{include file="common/header.tpl"}

{if $showAllJournals}

{if $isSiteAdmin}
<div class="block">
	<a href="{$indexUrl}/index/{$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a>
</div>
{/if}

{foreach from=$userJournals item=journal}
<div class="blockTitle"><a href="{$indexUrl}/{$journal->getPath()}/user" class="blockTitle">{$journal->getTitle()}</a></div>
<div class="block">
	<ul>
	{assign var="journalId" value=$journal->getJournalId()}
	{section name=role loop=$userRoles[$journalId]}
		<li><a href="{$indexUrl}/{$journal->getPath()}/{$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a>
	{/section}
	</ul>
</div>
{/foreach}

{else}
<div class="blockTitle">{$userJournal->getTitle()}</div>
<div class="block">
	<ul>
	{assign var="journalId" value=$userJournal->getJournalId()}
	{section name=role loop=$userRoles[$journalId]}
		<li><a href="{$indexUrl}/{$userJournal->getPath()}/{$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
	{/section}
	</ul>
</div>

&#187; <a href="{$indexUrl}/index/user">{translate key="user.showAllJournals"}</a><br />
{/if}


&#187; <a href="{$pageUrl}/user/profile">{translate key="user.profile"}</a><br />
&#187; <a href="{$pageUrl}/login/signOut">{translate key="user.signOut"}</a><br />

{include file="common/footer.tpl"}
