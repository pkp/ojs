{**
 * templates/about/subscriptions.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 *}
{strip}
{assign var="pageTitle" value="about.subscriptions"}
{include file="common/header.tpl"}
{/strip}

<div id="subscriptionsContact">
<h3>{translate key="about.subscriptionsContact"}</h3>
<p>
	{if !empty($subscriptionName)}
		<strong>{$subscriptionName|escape}</strong><br />
	{/if}
	{if !empty($subscriptionMailingAddress)}
		{$subscriptionMailingAddress|nl2br}<br />
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
	{if $acceptGiftSubscriptionPayments}
		{translate key="gifts.giftSubscriptionsAvailable"}&nbsp;
		<a class="action" href="{url page="gifts" op="purchaseGiftSubscription"}">{translate key="gifts.purchaseGiftSubscription"}</a>
	{/if}
</p>
</div>

<a name="subscriptionTypes" id="subscriptionTypes"></a>
{if !$individualSubscriptionTypes->wasEmpty()}
<div id="availableSubscriptionTypes">
<h3>{translate key="about.subscriptions.individual"}</h3>
<p>{translate key="subscriptions.individualDescription"}</p>
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="about.subscriptionTypes.name"}</td>
		<td width="20%">{translate key="about.subscriptionTypes.format"}</td>
		<td width="25%">{translate key="about.subscriptionTypes.duration"}</td>
		<td width="15%">{translate key="about.subscriptionTypes.cost"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=individualSubscriptionTypes item=subscriptionType}
		<tr valign="top">
			<td>{$subscriptionType->getSubscriptionTypeName()|escape}<br />{$subscriptionType->getSubscriptionTypeDescription()|nl2br}</td>
			<td>{translate key=$subscriptionType->getFormatString()}</td>
			<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
			<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})</td>
		</tr>
		<tr><td colspan="4" class="{if $individualSubscriptionTypes->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
</table>
</div>
<br />
{/if}

{if !$institutionalSubscriptionTypes->wasEmpty()}
<h3>{translate key="about.subscriptions.institutional"}</h3>
<p>{translate key="subscriptions.institutionalDescription"}</p>
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="about.subscriptionTypes.name"}</td>
		<td width="20%">{translate key="about.subscriptionTypes.format"}</td>
		<td width="25%">{translate key="about.subscriptionTypes.duration"}</td>
		<td width="15%">{translate key="about.subscriptionTypes.cost"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=institutionalSubscriptionTypes item=subscriptionType}
		<tr valign="top">
			<td>{$subscriptionType->getSubscriptionTypeName()|escape}<br />{$subscriptionType->getSubscriptionTypeDescription()|nl2br}</td>
			<td>{translate key=$subscriptionType->getFormatString()}</td>
			<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
			<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})</td>
		</tr>
		<tr><td colspan="4" class="{if $institutionalSubscriptionTypes->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
</table>
{/if}

{include file="common/footer.tpl"}

