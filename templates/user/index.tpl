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

<h3>{translate key="user.myJournals"}</h3>

{if $isSiteAdmin}
<h4><a href="{$pageUrl}/user">{$siteTitle}</a></h4>
<ul class="plain">
	<li>&#187; <a href="{$indexUrl}/index/{$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
</ul>
{/if}

{foreach from=$userJournals item=journal}
<h4><a href="{$indexUrl}/{$journal->getPath()}/user">{$journal->getSetting('journalTitle')}</a></h4>
<ul class="plain">
{assign var="journalId" value=$journal->getJournalId()}
{section name=role loop=$userRoles[$journalId]}
	<li>&#187; <a href="{$indexUrl}/{$journal->getPath()}/{$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a>
{/section}
</ul>
{/foreach}

{else}
<h3>{$userJournal->getSetting('journalTitle')}</h3>
<ul class="plain">
{assign var="journalId" value=$userJournal->getJournalId()}
{section name=role loop=$userRoles[$journalId]}
	<li>&#187; <a href="{$indexUrl}/{$userJournal->getPath()}/{$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
{/section}
</ul>
{/if}

<br />

<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $showAllJournals}
	<li>&#187; <a href="{$indexUrl}/index/user/register">{translate key="user.registerForOtherJournals"}</a></li>
	{else}
	<li>&#187; <a href="{$indexUrl}/index/user">{translate key="user.showAllJournals"}</a></li>
	{/if}
	<li>&#187; <a href="{$pageUrl}/user/profile">{translate key="user.editMyProfile"}</a></li>
	<li>&#187; <a href="{$pageUrl}/user/changePassword">{translate key="user.changeMyPassword"}</a></li>
	<li>&#187; <a href="{$pageUrl}/login/signOut">{translate key="user.signOut"}</a></li>
</ul>

{include file="common/footer.tpl"}
