{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.subscriptions"}
{include file="common/header.tpl"}

<h3>{translate key="about.subscriptionsContact"}</h3>
<p>
	{if !empty($subscriptionName)}
		<strong>{$subscriptionName|escape}</strong><br />
	{/if}
	{if !empty($subscriptionMailingAddress)}
		{$subscriptionMailingAddress|escape|nl2br}<br />
	{/if}
	{if !empty($subscriptionPhone)}
		{translate key="user.phone"}: {$subscriptionPhone|escape}<br />
	{/if}
	{if !empty($subscriptionFax)}
		{translate key="user.fax"}: {$subscriptionFax|escape}<br />
	{/if}
	{if !empty($subscriptionEmail)}
		{translate key="user.email"}: {mailto address=$subscriptionEmail|escape encode="hex"}<br /><br />
	{/if}
	{if !empty($subscriptionAdditionalInformation)}
		{$subscriptionAdditionalInformation|nl2br}<br />
	{/if}
</p>

<h3>{translate key="about.availableSubscriptionTypes"}</h3>
<p>
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="manager.subscriptionTypes.name"}</td>
		<td width="20%">{translate key="manager.subscriptionTypes.format"}</td>
		<td width="25%">{translate key="manager.subscriptionTypes.duration"}</td>
		<td width="15%">{translate key="manager.subscriptionTypes.cost"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=subscriptionTypes item=subscriptionType}
	{if !$subscriptionType->getPublic()}
		<tr valign="top">
			<td>{$subscriptionType->getTypeName()|escape}<br />{$subscriptionType->getDescription()|escape|nl2br}</td>
			<td>{translate key=$subscriptionType->getFormatString()}</td>
			<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
			<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})</td>
		</tr>
		<tr><td colspan="4" class="{if $subscriptionTypes->eof()}end{/if}separator">&nbsp;</td></tr>
	{/if}
{/iterate}
</table>
</p>

{include file="common/footer.tpl"}
