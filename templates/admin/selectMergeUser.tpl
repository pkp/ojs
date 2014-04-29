{**
 * templates/admin/selectMergeUser.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List users so the site administrator can choose users to merge.
 *
 *}
{strip}
{assign var="pageTitle" value="admin.mergeUsers"}
{include file="common/header.tpl"}
{/strip}
<div id="selectMergeUsers">
<p>{if !empty($oldUserIds)}{translate key="admin.mergeUsers.into.description"}{else}{translate key="admin.mergeUsers.from.description"}{/if}</p>
<div id="roles">
<h3>{translate key=$roleName}</h3>
<form method="post" action="{url path=$roleSymbolic oldUserIds=$oldUserIds}">
	<select name="roleSymbolic" class="selectMenu">
		<option {if $roleSymbolic=='all'}selected="selected" {/if}value="all">{translate key="admin.mergeUsers.allUsers"}</option>
		<option {if $roleSymbolic=='managers'}selected="selected" {/if}value="managers">{translate key="user.role.managers"}</option>
		<option {if $roleSymbolic=='editors'}selected="selected" {/if}value="editors">{translate key="user.role.editors"}</option>
		<option {if $roleSymbolic=='sectionEditors'}selected="selected" {/if}value="sectionEditors">{translate key="user.role.sectionEditors"}</option>
		<option {if $roleSymbolic=='layoutEditors'}selected="selected" {/if}value="layoutEditors">{translate key="user.role.layoutEditors"}</option>
		<option {if $roleSymbolic=='copyeditors'}selected="selected" {/if}value="copyeditors">{translate key="user.role.copyeditors"}</option>
		<option {if $roleSymbolic=='proofreaders'}selected="selected" {/if}value="proofreaders">{translate key="user.role.proofreaders"}</option>
		<option {if $roleSymbolic=='reviewers'}selected="selected" {/if}value="reviewers">{translate key="user.role.reviewers"}</option>
		<option {if $roleSymbolic=='authors'}selected="selected" {/if}value="authors">{translate key="user.role.authors"}</option>
		<option {if $roleSymbolic=='readers'}selected="selected" {/if}value="readers">{translate key="user.role.readers"}</option>
		<option {if $roleSymbolic=='subscriptionManagers'}selected="selected" {/if}value="subscriptionManagers">{translate key="user.role.subscriptionManagers"}</option>
	</select>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="10" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url path=$roleSymbolic oldUserIds=$oldUserIds searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url path=$roleSymbolic oldUserIds=$oldUserIds}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

{if not $roleId}
<ul>
	<li><a href="{url path="managers" oldUserIds=$oldUserIds}">{translate key="user.role.managers"}</a></li>
	<li><a href="{url path="editors" oldUserIds=$oldUserIds}">{translate key="user.role.editors"}</a></li>
	<li><a href="{url path="sectionEditors" oldUserIds=$oldUserIds}">{translate key="user.role.sectionEditors"}</a></li>
	<li><a href="{url path="layoutEditors" oldUserIds=$oldUserIds}">{translate key="user.role.layoutEditors"}</a></li>
	<li><a href="{url path="copyeditors" oldUserIds=$oldUserIds}">{translate key="user.role.copyeditors"}</a></li>
	<li><a href="{url path="proofreaders" oldUserIds=$oldUserIds}">{translate key="user.role.proofreaders"}</a></li>
	<li><a href="{url path="reviewers" oldUserIds=$oldUserIds}">{translate key="user.role.reviewers"}</a></li>
	<li><a href="{url path="authors" oldUserIds=$oldUserIds}">{translate key="user.role.authors"}</a></li>
	<li><a href="{url path="readers" oldUserIds=$oldUserIds}">{translate key="user.role.readers"}</a></li>
	<li><a href="{url path="subscriptionManagers" oldUserIds=$oldUserIds}">{translate key="user.role.subscriptionManagers"}</a></li>
</ul>

<br />
{else}
<p><a href="{url path="all" oldUserIds=$oldUserIds}" class="action">{translate key="admin.mergeUsers.allUsers"}</a></p>
{/if}
</div>
<div id="users">
{if !empty($oldUserIds)}
	{* Selecting target user; do not include checkboxes on LHS *}
	{assign var="numCols" value=4}
{else}
	{* Selecting user(s) to merge; include checkboxes on LHS *}
	{assign var="numCols" value=5}
       <form method="post" action="{url}">
{/if}
<table width="100%" class="listing">
	<tr>
		<td colspan="{$numCols}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		{if empty($oldUserIds)}
			<td width="5%">&nbsp;</td>
		{/if}
		<td>{translate key="user.username"}</td>
		<td width="29%">{translate key="user.name"}</td>
		<td width="29%">{translate key="user.email"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="{$numCols}" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=users item=user}
	{assign var=userExists value=1}
	<tr valign="top">
		{if empty($oldUserIds)}
			<td><input type="checkbox" name="oldUserIds[]" value="{$user->getId()|escape}" {if $thisUser->getId() == $user->getId()}disabled="disabled" {/if}/></td>
		{/if}
		<td>{$user->getUsername()|escape|wordwrap:15:" ":true}</td>
		<td>{$user->getFullName()|escape}</td>
		<td class="nowrap">
			{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
			{url|assign:"redirectUrl" path=$roleSymbolic}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$redirectUrl}
			{$user->getEmail()|truncate:15:"..."|escape}&nbsp;{icon name="mail" url=$url}
		</td>
		<td align="right">
			{if !empty($oldUserIds)}
				{if !in_array($user->getId(), $oldUserIds)}
					<a href="#" onclick="confirmAction('{url oldUserIds=$oldUserIds newUserId=$user->getId()}', '{translate|escape:"jsparam" key="admin.mergeUsers.confirm" oldAccountCount=$oldUserIds|@count newUsername=$user->getUsername()}')" class="action">{translate key="admin.mergeUsers.mergeUser"}</a>
				{/if}
			{elseif $thisUser->getId() != $user->getId()}
				<a href="{url oldUserIds=$user->getId()}" class="action">{translate key="admin.mergeUsers.mergeUser"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="{$numCols}" class="{if $users->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
		<td colspan="{$numCols}" class="nodata">{translate key="admin.mergeUsers.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="{$numCols}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{math equation="floor(numCols / 2)" numCols=$numCols}" align="left">{page_info iterator=$users}</td>
		<td colspan="{math equation="ceil(numCols / 2)" numCols=$numCols}" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth roleSymbolic=$roleSymbolic oldUserIds=$oldUserIds}</td>
	</tr>
{/if}
</table>
{if empty($oldUserIds)}
	<input type="submit" class="button defaultButton" value="{translate key="admin.mergeUsers"}" />
	</form>
{/if}
</div>
</div>
{include file="common/footer.tpl"}

