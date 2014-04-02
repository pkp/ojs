{**
 * templates/manager/groups/memberships.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of groups in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.groups.membership"}
{assign var="pageId" value="manager.groups"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", {/literal}"{url op=moveMembership path=$group->getId()}"{literal}); });
{/literal}
</script>

<ul class="menu">
	<li><a href="{url op="editGroup" path=$group->getId()}">{translate key="manager.groups.editTitle"}</a></li>
	<li class="current"><a href="{url op="groupMembership" path=$group->getId()}">{translate key="manager.groups.membership}</a></li>
</ul>

<br/>

<div id="membership">
<table width="100%" class="listing" id="dragTable">
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
	<tr valign="top" class="data" id=membership-{$membership->getUserId()}>
		<td class="drag">{$user->getFullName()|escape}</td>
		<td>
			<a href="{url op="deleteMembership" path=$membership->getGroupId()|to_array:$membership->getUserId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.groups.membership.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveMembership" d=u path=$group->getId() id=$user->getId()}">&uarr;</a>&nbsp;<a href="{url op="moveMembership" d=d path=$group->getId() id=$user->getId()}">&darr;</a>
		</td>
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
	<tr><td colspan="2" class="endseparator">&nbsp;</td></tr>
	<tr>
		<td align="left">{page_info iterator=$memberships}</td>
		<td align="right">{page_links anchor="membership" name="memberships" iterator=$memberships}</td>
	</tr>
{/if}
</table>

<a href="{url op="addMembership" path=$group->getId()}" class="action">{translate key="manager.groups.membership.addMember"}</a>
</div>
{include file="common/footer.tpl"}

