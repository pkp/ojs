{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscriptions in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptions"}
{assign var="pageId" value="manager.subscriptions"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="subscriptions"}">{translate key="manager.subscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
</ul>

<br />

<a name="subscriptions"></a>

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
		<td><a href="{url op="editSubscription" path=$subscription->getSubscriptionId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSubscription" path=$subscription->getSubscriptionId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.subscriptions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
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

{include file="common/footer.tpl"}
