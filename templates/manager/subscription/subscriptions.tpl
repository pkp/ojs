{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
	<li class="current"><a href="{$pageUrl}/manager/subscriptions">{translate key="manager.subscriptions"}</a></li>
	<li><a href="{$pageUrl}/manager/subscriptionTypes">{translate key="manager.subscriptionTypes"}</a></li>
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator"></td>
	</tr>
	<tr class="heading">
		<td width="25%">{translate key="manager.subscriptions.user"}</td>
		<td width="25%">{translate key="manager.subscriptions.subscriptionType"}</td>
		<td width="15%">{translate key="manager.subscriptions.dateStart"}</td>
		<td width="15%">{translate key="manager.subscriptions.dateEnd"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator"></td>
	</tr>
{foreach name=subscriptions from=$subscriptions item=subscription}
	<tr valign="top">
		<td>{$subscription->getUserFullName()}</td>
		<td>{$subscription->getTypeName()}</td>
		<td>{$subscription->getDateStart()}</td>
		<td>{$subscription->getDateEnd()}</td>
		<td><a href="{$pageUrl}/manager/editSubscription/{$subscription->getSubscriptionId()}" class="action">{translate key="common.edit"}</a> <a href="{$pageUrl}/manager/deleteSubscription/{$subscription->getSubscriptionId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.subscriptions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $smarty.foreach.subscriptions.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.subscriptions.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator"></td>
	</tr>
{/foreach}
</table>

<a href="{$pageUrl}/manager/createSubscription" class="action">{translate key="manager.subscriptions.create"}</a>

{include file="common/footer.tpl"}
