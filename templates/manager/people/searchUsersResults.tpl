{**
 * searchUsersResults.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show user enrollment search results.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<form action="{$pageUrl}/manager/enroll" method="post">
<input type="hidden" name="enroll" value="1" />
<input type="hidden" name="roleId" value="{$roleId}" />

<table width="100%">
<tr class="heading">
	<td></td>
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
</tr>
{foreach from=$users item=user}
<tr class="{cycle values="row,rowAlt"}">
	<td><input type="checkbox" name="users[]" value="{$user->getUserId()}" />
	<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
	<td width="100%">{$user->getFullName()}</td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noMatchingUsers"}</td>
</tr>
{/foreach}
</table>

<input type="submit" value="{translate key="manager.people.enrollSelected"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager'" />

</form>

{include file="common/footer.tpl"}
