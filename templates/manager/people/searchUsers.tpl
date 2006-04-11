{**
 * searchUsers.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 * $Id$
 *
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<form name="disableUser" method="post" action="{url op="disableUser"}">
	<input type="hidden" name="reason" value=""/>
	<input type="hidden" name="userId" value=""/>
</form>

<script type="text/javascript">
{literal}
<!--
function confirmAndPrompt(userId) {
	var reason = prompt('{/literal}{translate|escape:"javascript" key="manager.people.confirmDisable"}{literal}');
	if (reason == null) return;

	document.disableUser.reason.value = reason;
	document.disableUser.userId.value = userId;

	document.disableUser.submit();
}
// -->
{/literal}
</script>

<form method="post" name="submit" action="{url op="enrollSearch"}">
<input type="hidden" name="roleId" value="{$roleId}"/>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="enrollSearch" searchInitial=$letter roleId=$roleId}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op="enrollSearch" roleId=$roleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<form name="enroll" action="{if $roleId}{url op="enroll" path=$roleId}{else}{url op="enroll"}{/if}" method="post">
{if !$roleId}
	<p>
	{translate key="manager.people.enrollUserAs"} <select name="roleId" size="1"  class="selectMenu">
		<option value=""></option>
		<option value="{$smarty.const.ROLE_ID_JOURNAL_MANAGER}">{translate key="user.role.manager"}</option>
		<option value="{$smarty.const.ROLE_ID_EDITOR}">{translate key="user.role.editor"}</option>
		<option value="{$smarty.const.ROLE_ID_SECTION_EDITOR}">{translate key="user.role.sectionEditor"}</option>
		<option value="{$smarty.const.ROLE_ID_LAYOUT_EDITOR}">{translate key="user.role.layoutEditor"}</option>
		<option value="{$smarty.const.ROLE_ID_REVIEWER}">{translate key="user.role.reviewer"}</option>
		<option value="{$smarty.const.ROLE_ID_COPYEDITOR}">{translate key="user.role.copyeditor"}</option>
		<option value="{$smarty.const.ROLE_ID_PROOFREADER}">{translate key="user.role.proofreader"}</option>
		<option value="{$smarty.const.ROLE_ID_SUBSCRIPTION_MANAGER}">{translate key="user.role.subscriptionManager"}</option>
		<option value="{$smarty.const.ROLE_ID_AUTHOR}">{translate key="user.role.author"}</option>
		<option value="{$smarty.const.ROLE_ID_READER}">{translate key="user.role.reader"}</option>
	</select>
	</p>
	<script type="text/javascript">
	<!--
	function enrollUser(userId) {ldelim}
		var fakeUrl = '{url op="enroll" path="ROLE_ID" userId="USER_ID"}';
		fakeUrl = fakeUrl.replace('ROLE_ID', document.enroll.roleId.options[document.enroll.roleId.selectedIndex].value);
		fakeUrl = fakeUrl.replace('USER_ID', userId);
		location.href = fakeUrl;
	{rdelim}
	// -->
	</script>
{/if}

<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="5%">&nbsp;</td>
	<td width="25%">{translate key="user.username"}</td>
	<td width="30%">{translate key="user.name"}</td>
	<td width="30%">{translate key="user.email"}</td>
	<td width="10%" align="right">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getUserId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><input type="checkbox" name="users[]" value="{$user->getUserId()}" /></td>
	<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getUsername()}</a></td>
	<td>{$user->getFullName(true)|escape}</td>
	<td class="nowrap">
		{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array}
		{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url=$url}
	</td>
	<td align="right" class="nowrap">
		{if $roleId}
		<a href="{url op="enroll" path=$roleId userId=$user->getUserId()}" class="action">{translate key="manager.people.enroll"}</a>
		{else}
		<a href="javascript:enrollUser({$user->getUserId()})" class="action">{translate key="manager.people.enroll"}</a>
		{/if}
		{if $thisUser->getUserId() != $user->getUserId()}
			{if $user->getDisabled()}
				|&nbsp;<a href="{url op="enableUser" path=$user->getUserId()}" class="action">{translate key="manager.people.enable"}</a>
			{else}
				|&nbsp;<a href="javascript:confirmAndPrompt({$user->getUserId()})" class="action">{translate key="manager.people.disable"}</a>
			{/if}
		{/if}
	</td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links name="users" iterator=$users searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth}</td>
	</tr>
{/if}
</table>

<input type="submit" value="{translate key="manager.people.enrollSelected"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" escape=false}'" />

</form>


{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
