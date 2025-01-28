{**
 * templates/frontend/pages/subscriptions.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="about.subscriptions"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="about.subscriptions"}</span>
				</h1>
			</header>

			{include file="frontend/components/subscriptionContact.tpl"}

			<a name="subscriptionTypes"></a>
			{if $individualSubscriptionTypes|@count}
				<div class="subscriptions_institutional">
					<h3>{translate key="about.subscriptions.individual"}</h3>
					<p>{translate key="subscriptions.individualDescription"}</p>
					<table class="table">
						<thead class="thead-dark">
							<tr>
								<th>{translate key="about.subscriptionTypes.name"}</th>
								<th>{translate key="about.subscriptionTypes.format"}</th>
								<th>{translate key="about.subscriptionTypes.duration"}</th>
								<th>{translate key="about.subscriptionTypes.cost"}</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$individualSubscriptionTypes item=subscriptionType}
								<tr>
									<td>
										<div class="subscription_name">
											{$subscriptionType->getLocalizedName()|escape}
										</div>
										<div class="subscription_description">
											{$subscriptionType->getLocalizedDescription()|strip_unsafe_html}
										</div>
									</td>
									<td>{translate key=$subscriptionType->getFormatString()}</td>
									<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
									<td>{$subscriptionType->getCost()|string_format:"%.2f"}
										&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
				{if $isUserLoggedIn}
					<div class="subscriptions_individual_purchase">
						<a class="action" href="{url page="user" op="purchaseSubscription" path="individual"}">
							{translate key="user.subscriptions.purchaseNewSubscription"}
						</a>
					</div>
				{/if}
			{/if}

			{if $institutionalSubscriptionTypes|@count}
				<h3>{translate key="about.subscriptions.institutional"}</h3>
				<p>{translate key="subscriptions.institutionalDescription"}</p>
				<table class="table">
					<thead class="thead-dark">
						<tr>
							<th>{translate key="about.subscriptionTypes.name"}</th>
							<th>{translate key="about.subscriptionTypes.format"}</th>
							<th>{translate key="about.subscriptionTypes.duration"}</th>
							<th>{translate key="about.subscriptionTypes.cost"}</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$institutionalSubscriptionTypes item=subscriptionType}
							<tr>
								<td>
									<div class="subscription_name">
										{$subscriptionType->getLocalizedName()|escape}
									</div>
									<div class="subscription_description">
										{$subscriptionType->getLocalizedDescription()|strip_unsafe_html}
									</div>
								</td>
								<td>{translate key=$subscriptionType->getFormatString()}</td>
								<td>{$subscriptionType->getDurationYearsMonths()|escape}</td>
								<td>{$subscriptionType->getCost()|string_format:"%.2f"}
									&nbsp;({$subscriptionType->getCurrencyStringShort()|escape})
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				{if $isUserLoggedIn}
					<div class="subscriptions_institutional_purchase">
						<a class="action" href="{url page="user" op="purchaseSubscription" path="institutional"}">
							{translate key="user.subscriptions.purchaseNewSubscription"}
						</a>
					</div>
				{/if}
			{/if}
		</div>
	</div><!-- .row -->
</main><!-- .main__content -->

{include file="frontend/components/footer.tpl"}
