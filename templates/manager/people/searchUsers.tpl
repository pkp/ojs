{**
 * searchUsers.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 * $Id$
 *
 *}

{assign var="start" value="A"|ord}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<form name="submit" action="{$requestPageUrl}/enrollSearch">
<input type="hidden" name="roleId" value="{$roleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains">{translate key="form.contains"}</option>
		<option value="is">{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{section loop=26 name=letters}<a href="{$requestPageUrl}/enrollSearch?search_initial={$smarty.section.letters.index+$start|chr}&roleId={$roleId}">{$smarty.section.letters.index+$start|chr}</a> {/section}</p>

<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator"></tr>
<tr class="heading" valign="bottom">
	<td width="5%">&nbsp;</td>
	<td width="25%">{translate key="user.username"}</td>
	<td width="30%">{translate key="user.name"}</td>
	<td width="30%">{translate key="user.email"}</td>
	<td width="10%" align="right">{translate key="common.action"}</td>
</tr>
<form action="{$requestPageUrl}/enroll" method="post">
<input type="hidden" name="roleId" value="{$roleId}">
<tr><td colspan="5" class="headseparator"></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getUserId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><input type="checkbox" name="users[]" value="{$user->getUserId()}" /></td>
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userid}">{$user->getUsername()}</a></td>
	<td>{$user->getFullName(true)}</td>
	<td>{$user->getEmail(true)}</td>
	<td><nobr>
		<a href="{$requestPageUrl}/enroll?userId={$user->getUserId()}&roleId={$roleId}" class="action">{translate key="manager.people.enroll"}</a>
		{if $thisUser->getUserId() != $user->getUserId()}
			{if $user->getDisabled()}
				<a href="{$pageUrl}/manager/enableUser/{$user->getUserId()}" class="action">{translate key="manager.people.enable"}</a>
			{else}
				<a href="{$pageUrl}/manager/disableUser/{$user->getUserId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmDisable"}')" class="action">{translate key="manager.people.disable"}</a>
			{/if}
		{/if}
	</nobr></td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator"></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator"></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links name="users" iterator=$users}</td>
	</tr>
{/if}
</table>

<input type="submit" value="{translate key="manager.people.enrollSelected"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager'" />

</form>


{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
