{**
 * groups.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of groups in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.groups"}
{assign var="pageId" value="manager.groups"}
{include file="common/header.tpl"}

<br/>

<form action="{$pageUrl}/manager/setBoardEnabled" method="post">
	{assign var=aboutEditorialTeamUrl value="$pageUrl/about/editorialTeam"}
	{assign var=peopleManagementUrl value="$pageUrl/manager/people/all"}
	{translate key="manager.groups.enableBoard.description" aboutEditorialTeamUrl=$aboutEditorialTeamUrl}<br/>
	<input type="radio" id="boardEnabledOff" {if !$boardEnabled}checked="checked" {/if}name="boardEnabled" value="0"/>&nbsp;<label for="boardEnabledOff">{translate key="manager.groups.disableBoard"}</label><br/>
	<input type="radio" id="boardEnabledOn" {if $boardEnabled}checked="checked" {/if}name="boardEnabled" value="1"/>&nbsp;<label for="boardEnabledOn">{translate key="manager.groups.enableBoard"}</label><br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="75%">{translate key="manager.groups.title"}</td>
		<td width="25%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=groups item=group}
	<tr valign="top">
		<td>{$group->getGroupTitle()|escape}</td>
		<td>
			<a href="{$pageUrl}/manager/editGroup/{$group->getGroupId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{$pageUrl}/manager/groupMembership/{$group->getGroupId()}" class="action">{translate key="manager.groups.membership"}</a>&nbsp;|&nbsp;<a href="{$pageUrl}/manager/deleteGroup/{$group->getGroupId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.groups.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{$pageUrl}/manager/moveGroup?d=u&amp;groupId={$group->getGroupId()}">&uarr;</a>&nbsp;|&nbsp;<a href="{$pageUrl}/manager/moveGroup?d=d&amp;groupId={$group->getGroupId()}">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $groups->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $groups->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.groups.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$groups}</td>
		<td colspan="3" align="right">{page_links name="groups" iterator=$groups}</td>
	</tr>
{/if}
</table>

<a href="{$pageUrl}/manager/createGroup" class="action">{translate key="manager.groups.create"}</a>

{include file="common/footer.tpl"}
