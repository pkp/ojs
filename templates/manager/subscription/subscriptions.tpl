{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscriptions in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptions"}
{assign var="pageId" value="manager.subscriptions"}
{include file="common/header.tpl"}

<table>
<tr class="heading">
	<td width="35%">{translate key="manager.subscriptions.user"}</td>
	<td width="35%">{translate key="manager.subscriptions.subscriptionType"}</td>
	<td width="15%">{translate key="manager.subscriptions.dateStart"}</td>
	<td width="15%">{translate key="manager.subscriptions.dateEnd"}</td>
	<td></td>
	<td></td>
</tr>
{foreach from=$subscriptions item=subscription}
<tr class="{cycle values="row,rowAlt"}">
	<td>{$subscription->getUserFullName()}</td>
	<td>{$subscription->getTypeName()}</td>
	<td>{$subscription->getDateStart()}</td>
	<td>{$subscription->getDateEnd()}</td>
	<td><a href="{$pageUrl}/manager/editSubscription/{$subscription->getSubscriptionId()}" class="tableAction">{translate key="common.edit"}</a></td>
	<td><a href="{$pageUrl}/manager/deleteSubscription/{$subscription->getSubscriptionId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.subscriptions.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="6" class="noResults">{translate key="manager.subscriptions.noneCreated"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/manager/createSubscription" class="tableButton">{translate key="manager.subscriptions.create"}</a>

{include file="common/footer.tpl"}
