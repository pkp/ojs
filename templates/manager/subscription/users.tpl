{**
 * users.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for users
 *
 * $Id$
 *
 *}

{assign var="start" value="A"|ord}

{if $subscriptionId}
	{assign var="pageTitle" value="manager.subscriptions.selectSubscriber"}
{else}
	{assign var="pageTitle" value="manager.subscriptions.create"}
{/if}

{include file="common/header.tpl"}

{if $subscriptionCreated}
<br/>{translate key="manager.subscriptions.subscriptionCreatedSuccessfully"}<br/>
{/if}

<p>{translate key="manager.subscriptions.selectSubscriber.desc"}</p>
<form name="submit" action="{$requestPageUrl}/selectSubscriber{if $subscriptionId}?subscriptionId={$subscriptionId}{/if}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{section loop=26 name=letters}<a href="{$requestPageUrl}/selectSubscriber?searchInitial={$smarty.section.letters.index+$start|chr}{if $subscriptionId}&subscriptionId={$subscriptionId}{/if}">{if chr($smarty.section.letters.index+$start) == $searchInitial}<strong>{$smarty.section.letters.index+$start|chr}</strong>{else}{$smarty.section.letters.index+$start|chr}{/if}</a> {/section}<a href="{$requestPageUrl}/selectSubscriber{if $subscriptionId}?subscriptionId={$subscriptionId}{/if}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

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
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userid}">{$user->getUsername()}</a></td>
	<td>{$user->getFullName(true)|escape}</td>
	<td>
			{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			<nobr>{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url="`$requestPageUrl`/email?to[]=$emailStringEscaped"}</nobr>
	</td>
	<td align="right"><nobr>
		<a href="{$requestPageUrl}/{if $subscriptionId}editSubscription/{$subscriptionId}{else}createSubscription{/if}?userId={$user->getUserId()}" class="action">{translate key="manager.subscriptions.subscribe"}</a>
	</nobr></td>
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
		<td colspan="2" align="right">{page_links name="users" iterator=$users}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
