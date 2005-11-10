{**
 * memberships.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of groups in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.groups.membership"}
{assign var="pageId" value="manager.groups"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/manager/editGroup/{$group->getGroupId()}">{translate key="manager.groups.editTitle"}</a></li>
	<li class="current"><a href="{$pageUrl}/manager/groupMembership/{$group->getGroupId()}">{translate key="manager.groups.membership}</a></li>
</ul>

<br/>

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="85%">{translate key="user.name"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=memberships item=membership}
	{assign var=user value=$membership->getUser()}
	<tr valign="top">
		<td>{$user->getFullName()|escape}</td>
		<td>
			<a href="{$pageUrl}/manager/deleteMembership/{$membership->getGroupId()}/{$membership->getUserId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.groups.membership.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			<a href="{$pageUrl}/manager/moveMembership?d=u&amp;groupId={$group->getGroupId()}&amp;userId={$user->getUserId()}">&uarr;</a>
			<a href="{$pageUrl}/manager/moveMembership?d=d&amp;groupId={$group->getGroupId()}&amp;userId={$user->getUserId()}">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $memberships->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $memberships->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.groups.membership.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$memberships}</td>
		<td align="right">{page_links name="membership" iterator=$memberships}</td>
	</tr>
{/if}
</table>

<a href="{$pageUrl}/manager/addMembership/{$group->getGroupId()}" class="action">{translate key="manager.groups.membership.addMember"}</a>

{include file="common/footer.tpl"}
