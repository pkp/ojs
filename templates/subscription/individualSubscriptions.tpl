{**
 * templates/subscription/individualSubscriptions.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of individual subscriptions in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.individualSubscriptions"}
{assign var="pageId" value="manager.individualSubscriptions"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="subscriptionsSummary"}">{translate key="manager.subscriptions.summary"}</a></li>
	<li class="current"><a href="{url op="subscriptions" path="individual"}">{translate key="manager.individualSubscriptions"}</a></li>
	<li><a href="{url op="subscriptions" path="institutional"}">{translate key="manager.institutionalSubscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
	<li><a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
</ul>

<form action="#">
<ul class="filter">
	<li>{translate key="manager.subscriptions.withStatus"}: <select name="filterStatus" onchange="location.href='{url|escape:"javascript" path="individual" searchField=$searchField searchMatch=$searchMatch search=$search dateSearchField=$dateSearchField dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth filterStatus="STATUS_ID" escape=false}'.replace('STATUS_ID', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options_translate options=$statusOptions selected=$filterStatus}</select></li>
</ul>
</form>

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" id="submit" action="{url op="subscriptions" path="individual"}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<br/>
	<select name="dateSearchField" size="1" class="selectMenu">
		{html_options_translate options=$dateFieldOptions selected=$dateSearchField}
	</select>
	{translate key="common.between"}
	{html_select_date prefix="dateFrom" time=$dateFrom all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+5"}
	{translate key="common.and"}
	{html_select_date prefix="dateTo" time=$dateTo all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+5"}
	<input type="hidden" name="dateToHour" value="23" />
	<input type="hidden" name="dateToMinute" value="59" />
	<input type="hidden" name="dateToSecond" value="59" />
	<br/>
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<div id="subscriptions">
<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="30%">{translate key="manager.subscriptions.user"}</td>
		<td width="25%">{translate key="manager.subscriptions.subscriptionType"}</td>
		<td width="10%">{translate key="subscriptions.status"}</td>
		<td width="10%">{translate key="manager.subscriptions.dateStart"}</td>
		<td width="10%">{translate key="manager.subscriptions.dateEnd"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=subscriptions item=subscription}
	{assign var=isNonExpiring value=$subscription->isNonExpiring()}
	<tr valign="top">
		<td>
			{assign var=emailString value=$subscription->getUserFullName()|concat:" <":$subscription->getUserEmail():">"}
			{url|assign:"redirectUrl" op="subscriptions" path="individual" escape=false}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$redirectUrl}
			{$subscription->getUserFullName()|escape}&nbsp;{icon name="mail" url=$url}
		</td>
		<td>{$subscription->getSubscriptionTypeName()|escape}</td>
		<td>{$subscription->getStatusString()|escape}</td>
		<td>{if $isNonExpiring}&nbsp;{else}{if $subscription->isExpired()}<span class="disabled">{$subscription->getDateStart()|date_format:$dateFormatShort}</span>{else}{$subscription->getDateStart()|date_format:$dateFormatShort}{/if}{/if}</td>
		<td>{if $isNonExpiring}{translate key="subscriptionTypes.nonExpiring"}{else}{if $subscription->isExpired()}<span class="disabled">{$subscription->getDateEnd()|date_format:$dateFormatShort}</span>{else}{$subscription->getDateEnd()|date_format:$dateFormatShort}{/if}{/if}</td>
		<td><a href="{url op="editSubscription" path="individual"|to_array:$subscription->getId()}" class="action">{translate key="common.edit"}</a>{if !$isNonExpiring}&nbsp;|&nbsp;<a href="{url op="renewSubscription" path="individual"|to_array:$subscription->getId()}" class="action">{translate key="manager.subscriptions.renew"}</a>{/if}&nbsp;|&nbsp;<a href="{url op="deleteSubscription" path="individual"|to_array:$subscription->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.subscriptions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="6" class="{if $subscriptions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $subscriptions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="manager.subscriptions.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$subscriptions}</td>
		<td colspan="4" align="right">{page_links anchor="subscriptions" name="subscriptions" iterator=$subscriptions searchField=$searchField searchMatch=$searchMatch search=$search dateSearchField=$dateSearchField dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth filterStatus=$filterStatus}</td>
	</tr>
{/if}
</table>
<a href="{url op="selectSubscriber" path="individual"}" class="action">{translate key="manager.subscriptions.create"}</a>
</div>

{include file="common/footer.tpl"}

