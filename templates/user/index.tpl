{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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
<h4><a href="{url page="user"}">{$siteTitle|escape}</a></h4>
<ul class="plain">
	<li>&#187; <a href="{url journal="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
	{call_hook name="Templates::User::Index::Site"}
</ul>
{/if}

{foreach from=$userJournals item=journal}
<h4><a href="{url journal=$journal->getPath() page="user"}">{$journal->getJournalTitle()|escape}</a></h4>
<ul class="plain">
{assign var="journalId" value=$journal->getJournalId()}
{section name=role loop=$userRoles[$journalId]}
	{if $userRoles[$journalId][role]->getRolePath() != 'reader'}
	<li>&#187; <a href="{url journal=$journal->getPath() page=$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
	{/if}
{/section}
	{call_hook name="Templates::User::Index::Journal" journal=$journal}
</ul>
{/foreach}

{else}
<h3>{$userJournal->getJournalTitle()}</h3>
<ul class="plain">
{if $isSiteAdmin && !$hasOtherJournals}
	<li>&#187; <a href="{url journal="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
{/if}
{assign var="journalId" value=$userJournal->getJournalId()}
{section name=role loop=$userRoles[$journalId]}
	{if $userRoles[$journalId][role]->getRolePath() != 'reader'}
	<li>&#187; <a href="{url journal=$userJournal->getPath() page=$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
	{/if}
{/section}
</ul>
{/if}

<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $hasOtherJournals}
	{if $showAllJournals}
	<li>&#187; <a href="{url journal="index" page="user" op="register"}">{translate key="user.registerForOtherJournals"}</a></li>
	{else}
	<li>&#187; <a href="{url journal="index" page="user"}">{translate key="user.showAllJournals"}</a></li>
	{/if}
	{/if}
	<li>&#187; <a href="{url page="user" op="profile"}">{translate key="user.editMyProfile"}</a></li>
	<li>&#187; <a href="{url page="user" op="changePassword"}">{translate key="user.changeMyPassword"}</a></li>
	<li>&#187; <a href="{url page="login" op="signOut"}">{translate key="user.logOut"}</a></li>
	{call_hook name="Templates::Admin::Index::MyAccount"}
</ul>

{include file="common/footer.tpl"}
