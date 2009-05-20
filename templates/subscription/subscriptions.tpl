{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscriptions in journal management.
 *
 * $Id$
 *}
{strip}
{if $institutional}
	{assign var="pageTitle" value="manager.institutionalSubscriptions"}
	{assign var="pageId" value="manager.institutionalSubscriptions"}
{else}
	{assign var="pageTitle" value="manager.individualSubscriptions"}
	{assign var="pageId" value="manager.individualSubscriptions"}
{/if}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	{if $institutional}<li>{else}<li class="current">{/if}<a href="{url op="subscriptions" path="individual"}">{translate key="manager.individualSubscriptions"}</a></li>
	{if $institutional}<li class="current">{else}<li>{/if}<a href="{url op="subscriptions" path="institutional"}">{translate key="manager.institutionalSubscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
</ul>

<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" name="submit" action="{url op="subscriptions"}">
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
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="32%">{translate key="manager.subscriptions.user"}</td>
		<td width="25%">{translate key="manager.subscriptions.subscriptionType"}</td>
		<td width="15%">{translate key="manager.subscriptions.dateStart"}</td>
		<td width="15%">{translate key="manager.subscriptions.dateEnd"}</td>
		<td width="13%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=subscriptions item=subscription}
	<tr valign="top">
		<td>{$subscription->getUserFullName()|escape}</td>
		<td>{$subscription->getSubscriptionTypeName()|escape}</td>
		<td>{$subscription->getDateStart()|date_format:$dateFormatShort}</td>
		<td>{$subscription->getDateEnd()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="editSubscription" path=$subscription->getSubscriptionId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSubscription" path=$subscription->getSubscriptionId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.subscriptions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $subscriptions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $subscriptions->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.subscriptions.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$subscriptions}</td>
		<td colspan="3" align="right">{page_links anchor="subscriptions" name="subscriptions" iterator=$subscriptions}</td>
	</tr>
{/if}
</table>
<a href="{url op="selectSubscriber"}" class="action">{translate key="manager.subscriptions.create"}</a>
</div>

{include file="common/footer.tpl"}
