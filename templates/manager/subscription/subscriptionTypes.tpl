{**
 * subscriptionTypes.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscription types in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptionTypes"}
{assign var="pageId" value="manager.subscriptionTypes"}
{include file="common/header.tpl"}

<table>
<tr class="heading">
	<td width="70%">{translate key="manager.subscriptionTypes.name"}</td>
	<td width="30%">{translate key="manager.subscriptionTypes.cost"}</td>
	<td></td>
	<td></td>
</tr>
{foreach from=$subscriptionTypes item=subscriptionType}
<tr class="{cycle values="row,rowAlt"}">
	<td>{$subscriptionType->getTypeName()}</td>
	<td>${$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({translate key=$subscriptionType->getCurrencyString()})</td>
	<td><a href="{$pageUrl}/manager/editSubscriptionType/{$subscriptionType->getTypeId()}" class="tableAction">{translate key="common.edit"}</a></td>
	<td><a href="{$pageUrl}/manager/deleteSubscriptionType/{$subscriptionType->getTypeId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.subscriptionTypes.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="4" class="noResults">{translate key="manager.subscriptionTypes.noneCreated"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/manager/createSubscriptionType" class="tableButton">{translate key="manager.subscriptionTypes.create"}</a>

{include file="common/footer.tpl"}
