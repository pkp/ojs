{**
 * users.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for users
 *
 * $Id$
 *
 *}
{if $subscriptionId}
{assign var="pageTitle" value="manager.subscriptions.selectSubscriber"}
{else}
{assign var="pageTitle" value="manager.subscriptions.select"}
{/if}
{include file="common/header.tpl"}

{if $subscriptionCreated}
<br/>{translate key="manager.subscriptions.subscriptionCreatedSuccessfully"}<br/>
{/if}

<p>{translate key="manager.subscriptions.selectSubscriber.desc"}</p>
<form method="post" name="submit" action="{if $subscriptionId}{url op="selectSubscriber" subscriptionId=$subscriptionId}{else}{url op="selectSubscriber" subscriptionId=$subscriptionId}{/if}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{if $subscriptionId}{url op="selectSubscriber" searchInitial=$letter subscriptionId=$subscriptionId}{else}{url op="selectSubscriber" searchInitial=$letter}{/if}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{if $subscriptionId}{url op="selectSubscriber" subscriptionId=$subscriptionId}{else}{url op="selectSubscriber"}{/if}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="users"></a>

<table width="100%" class="listing">
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="25%">{translate key="user.username"}</td>
	<td width="35%">{translate key="user.name"}</td>
	<td width="30%">{translate key="user.email"}</td>
	<td width="10%" align="right"></td>
</tr>
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getUserId()}
<tr valign="top">
	<td>{if $isJournalManager}<a class="action" href="{url op="userProfile" path=$userid}">{/if}{$user->getUsername()}{if $isJournalManager}</a>{/if}</td>
	<td>{$user->getFullName(true)|escape}</td>
	<td class="nowrap">
		{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array}
		{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url=$url}
	</td>
	<td align="right" class="nowrap">
		<a href="{if $subscriptionId}{url op="editSubscription" path=$subscriptionId userId=$user->getUserId()}{else}{url op="createSubscription" userId=$user->getUserId()}{/if}" class="action">{translate key="manager.subscriptions.subscribe"}</a>
	</td>
</tr>
<tr><td colspan="4" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="4" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="4" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth searchInitial=$searchInitial}</td>
	</tr>
{/if}
</table>

{url|assign:"selectSubscriberUrl" op="selectSubscriber"}
<a href="{url op="createUser" source=$selectSubscriberUrl}" class="action">{translate key="manager.people.createUser"}</a>

{include file="common/footer.tpl"}
