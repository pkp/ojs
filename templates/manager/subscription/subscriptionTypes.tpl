{**
 * subscriptionTypes.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of subscription types in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptionTypes"}
{assign var="pageId" value="manager.subscriptionTypes"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/manager/subscriptions">{translate key="manager.subscriptions"}</a></li>
	<li class="current"><a href="{$pageUrl}/manager/subscriptionTypes">{translate key="manager.subscriptionTypes"}</a></li>
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="58%">{translate key="manager.subscriptionTypes.name"}</td>
		<td width="30%">{translate key="manager.subscriptionTypes.cost"}</td>
		<td width="12%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{foreach name=types from=$subscriptionTypes item=subscriptionType}
	<tr valign="top">
		<td>{$subscriptionType->getTypeName()}</td>
		<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()})</td>
		<td><a href="{$pageUrl}/manager/editSubscriptionType/{$subscriptionType->getTypeId()}" class="action">{translate key="common.edit"}</a> <a href="{$pageUrl}/manager/deleteSubscriptionType/{$subscriptionType->getTypeId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.subscriptionTypes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr><td colspan="3" class="{if $smarty.foreach.types.last}end{/if}separator">&nbsp;</td></tr>
{foreachelse}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.subscriptionTypes.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>

<a href="{$pageUrl}/manager/createSubscriptionType" class="action">{translate key="manager.subscriptionTypes.create"}</a>

{include file="common/footer.tpl"}
