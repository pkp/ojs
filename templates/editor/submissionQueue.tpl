{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.userHome"}
{include file="common/header.tpl"}

{** Show unassigned articles *}
<table width="100%">
<tr class="heading">
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td></td>
	{if $roleId}
	<td></td>
	{/if}
</tr>
{foreach from=$unassignedSubmissions item=submission}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
	<td width="100%">{$user->getFullName()}</td>
	<td><a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="tableAction">{translate key="common.edit"}</a></td>
	{if $roleId}
	<td><a href="#" onclick="confirmAction('{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}', '{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="tableAction">{translate key="manager.people.unenroll"}</a></td>
	{/if}
</tr>
{foreachelse}
<tr>
<td colspan="{if $roleId}4{else}3{/if}" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
