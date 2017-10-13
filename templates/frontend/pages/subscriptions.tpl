{**
 * templates/about/subscriptions.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="about.subscriptions"}
<div class="page page_subscriptions">
	{* Contact section *}
	<div class="subscription_contact_section">
		{if $subscriptionAdditionalInformation}{$subscriptionAdditionalInformation|strip_unsafe_html}{/if}

		{if $subscriptionMailingAddress}
			<div class="address">
				{$subscriptionMailingAddress|nl2br|strip_unsafe_html}
			</div>
		{/if}

		{* Subscription contact *}
		{if $subscriptionName || $subscriptionPhone || $subscriptionEmail}
			<div class="contact primary">
				<h3>
					{translate key="about.contact.subscriptionContact"}
				</h3>

				{if $subscriptionName}
				<div class="name">
					{$subscriptionName|escape}
				</div>
				{/if}

				{if $subscriptionPhone}
				<div class="phone">
					<span class="label">
						{translate key="about.contact.phone"}
					</span>
					<span class="value">
						{$subscriptionPhone|escape}
					</span>
				</div>
				{/if}

				{if $subscriptionEmail}
				<div class="email">
					<a href="mailto:{$subscriptionEmail|escape}">
						{$subscriptionEmail|escape}
					</a>
				</div>
				{/if}
			</div>
		{/if}
	</div>

	<a name="subscriptionTypes" id="subscriptionTypes"></a>
	{if !$individualSubscriptionTypes->wasEmpty()}
		<div id="availableSubscriptionTypes">
			<h3>{translate key="about.subscriptions.individual"}</h3>
			<p>{translate key="subscriptions.individualDescription"}</p>
			<table width="100%" class="listing">
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
						<td>{$subscriptionType->getLocalizedName()|escape}<br />{$subscriptionType->getLocalizedDescription()|nl2br}</td>
						<td>{translate key=$subscriptionType->getFormatString()}</td>
						<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
						<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})</td>
					</tr>
				{/iterate}
			</table>
		</div>
	{/if}

	{if !$institutionalSubscriptionTypes->wasEmpty()}
		<h3>{translate key="about.subscriptions.institutional"}</h3>
		<p>{translate key="subscriptions.institutionalDescription"}</p>
		<table width="100%" class="listing">
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
					<td>{$subscriptionType->getLocalizedName()|escape}<br />{$subscriptionType->getLocalizedDescription()|nl2br}</td>
					<td>{translate key=$subscriptionType->getFormatString()}</td>
					<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
					<td>{$subscriptionType->getCost()|string_format:"%.2f"}&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})</td>
				</tr>
			{/iterate}
		</table>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
