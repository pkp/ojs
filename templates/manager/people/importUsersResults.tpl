{**
 * importUsersResults.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the results of importing users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.importUsers"}
{include file="common/header.tpl"}

{translate key="manager.people.importUsers.usersWereImported"}:
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="user.username"}</td>
		<td width="25%">{translate key="user.name"}</td>
		<td width="30%">{translate key="user.email"}</td>
		<td width="30%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{foreach name=importedUsers from=$importedUsers item=user}
	<tr valign="top">
		<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
		<td>{$user->getFullName()}</td>
		<td>{$user->getEmail()}</td>
		<td align="right">
			<a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$pageUrl}/manager/signInAsUser/{$user->getUserId()}" class="action">{translate key="manager.people.signInAs"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.importedUsers.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>

{if $isError}
<p>
	<span class="formError">{translate key="manager.people.importUsers.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$errors}
		<li>{$message}</li>
	{/foreach}
	</ul>
</p>
{/if}

<p>&#187; <a href="{$pageUrl}/manager">{translate key="manager.journalManagement"}</a></p>

{include file="common/footer.tpl"}
