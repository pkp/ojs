{**
 * importUsersResults.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the results of importing users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.importUsers"}
{include file="common/header.tpl"}

{translate key="manager.people.importUsers.usersWereImported"}:
<ul>
{foreach from=$importedUsers item=user}
	<li><strong>{$user->getFullName()}</strong> ({$user->getUsername()})</li>
{/foreach}
</ul>

{if $isError}
	<br />
	<span class="formError">{translate key="manager.people.importUsers.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$errors}
			<li>{$message}</li>
	{/foreach}
	</ul>
{/if}

<br />
&#187; <a href="{$pageUrl}/manager">{translate key="manager.journalManagement}</a>

{include file="common/footer.tpl"}
