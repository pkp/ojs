{**
 * templates/subscription/subscriptionTypes.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscription types in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.subscriptionTypes"}
{assign var="pageId" value="manager.subscriptionTypes"}
{include file="common/header.tpl"}
{/strip}
<script>
{literal}
$(document).ready(function() { setupTableDND("#subscriptionTypesTable", "moveSubscriptionType"); });
{/literal}
</script>

<ul class="menu">
	<li><a href="{url op="subscriptionsSummary"}">{translate key="manager.subscriptions.summary"}</a></li>
	<li><a href="{url op="subscriptions" path="individual"}">{translate key="manager.individualSubscriptions"}</a></li>
	<li><a href="{url op="subscriptions" path="institutional"}">{translate key="manager.institutionalSubscriptions"}</a></li>
	<li class="current"><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
	<li><a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
</ul>

<br />

<div id="subscriptionTypes">
<table class="listing" id="subscriptionTypesTable">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td>{translate key="manager.subscriptionTypes.name"}</td>
		<td>{translate key="manager.subscriptionTypes.subscriptions"}</td>
		<td>{translate key="manager.subscriptionTypes.duration"}</td>
		<td>{translate key="manager.subscriptionTypes.cost"}</td>
		<td>{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=subscriptionTypes item=subscriptionType}
	<tr id="subtype-{$subscriptionType->getTypeId()}" class="data">
		<td class="drag">{$subscriptionType->getSubscriptionTypeName()|escape}</td>
		<td class="drag">{if $subscriptionType->getInstitutional()}{translate key="manager.subscriptionTypes.institutional"}{else}{translate key="manager.subscriptionTypes.individual"}{/if}</td>
		<td class="drag">{$subscriptionType->getDurationYearsMonths()|escape}</td>
		<td class="drag">{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()})</td>
		<td><a href="{url op="moveSubscriptionType" id=$subscriptionType->getTypeId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveSubscriptionType" id=$subscriptionType->getTypeId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{url op="editSubscriptionType" path=$subscriptionType->getTypeId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSubscriptionType" path=$subscriptionType->getTypeId()}" onclick="return confirm({translate|json_encode key="manager.subscriptionTypes.confirmDelete"})" class="action">{translate key="common.delete"}</a></td>
	</tr>
  {if $subscriptionTypes->eof()}
  <tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
  {/if}
{/iterate}
{if $subscriptionTypes->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.subscriptionTypes.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$subscriptionTypes}</td>
		<td colspan="2" align="right">{page_links anchor="subscriptionTypes" name="subscriptionTypes" iterator=$subscriptionTypes}</td>
	</tr>
{/if}
</table>

<a href="{url op="createSubscriptionType"}" class="action">{translate key="manager.subscriptionTypes.create"}</a>
</div>

{include file="common/footer.tpl"}

