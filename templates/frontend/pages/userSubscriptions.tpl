{**
 * templates/frontend/pages/userSubscriptions.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Page where users can view and manage their subscriptions.
 *
 * @uses $paymentsEnabled boolean
 * @uses $individualSubscriptionTypesExist boolean Have any individual
 *       subscription types been created?
 * @uses $userIndividualSubscription IndividualSubscription
 * @uses $institutionalSubscriptionTypesExist boolean Have any institutional
 *			subscription types been created?
 * @uses $userInstitutionalSubscriptions array
 *}
{include file="frontend/components/header.tpl" pageTitle="user.subscriptions.mySubscriptions"}

<div class="page page_user_subscriptions">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="user.subscriptions.mySubscriptions"}
	{include file="frontend/components/subscriptionContact.tpl"}

	{if $paymentsEnabled}
		<div class="my_subscription_payments">
			<h3>{translate key="user.subscriptions.subscriptionStatus"}</h3>
			<p>{translate key="user.subscriptions.statusInformation"}</p>
			<table class="cmp_table">
				<tr>
					<th>{translate key="user.subscriptions.status"}</th>
					<th>{translate key="user.subscriptions.statusDescription"}</th>
				</tr>
				<tr>
					<td>{translate key="subscriptions.status.needsInformation"}</td>
					<td>{translate key="user.subscriptions.status.needsInformationDescription"}</td>
				</tr>
				<tr>
					<td>{translate key="subscriptions.status.needsApproval"}</td>
					<td>{translate key="user.subscriptions.status.needsApprovalDescription"}</td>
				</tr>
				<tr>
					<td>{translate key="subscriptions.status.awaitingManualPayment"}</td>
					<td>{translate key="user.subscriptions.status.awaitingManualPaymentDescription"}</td>
				</tr>
				<tr>
					<td>{translate key="subscriptions.status.awaitingOnlinePayment"}</td>
					<td>{translate key="user.subscriptions.status.awaitingOnlinePaymentDescription"}</td>
				</tr>
			</table>
		</div>
	{/if}

	{if $individualSubscriptionTypesExist}
		<div class="my_subscription_individual">
			<h3>{translate key="user.subscriptions.individualSubscriptions"}</h3>
			<p>{translate key="subscriptions.individualDescription"}</p>
			{if $userIndividualSubscription}
				<table class="cmp_table">
					<tr>
						<th>{translate key="user.subscriptions.form.typeId"}</th>
						<th>{translate key="subscriptions.status"}</th>
						{if $paymentsEnabled}
							<th></th>
						{/if}
					</tr>
					<tr>
						<td>{$userIndividualSubscription->getSubscriptionTypeName()|escape}</td>
						<td>
							{assign var="subscriptionStatus" value=$userIndividualSubscription->getStatus()}
							{assign var="isNonExpiring" value=$userIndividualSubscription->isNonExpiring()}
							{if $paymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
								<span class="subscription_disabled">
									{translate key="subscriptions.status.awaitingOnlinePayment"}
								</span>
							{elseif $paymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
								<span class="subscription_disabled">
									{translate key="subscriptions.status.awaitingManualPayment"}
								</span>
							{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
								<span class="subscription_disabled">
									{translate key="subscriptions.inactive"}
								</span>
							{else}
								{if $isNonExpiring}
									{translate key="subscriptionTypes.nonExpiring"}
								{else}
									{assign var="isExpired" value=$userIndividualSubscription->isExpired()}
									{if $isExpired}
										<span class="subscription_disabled">
											{translate key="user.subscriptions.expired" date=$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}
										</span>
									{else}
										<span class="subscription_active">
											{translate key="user.subscriptions.expires" date=$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}
										</span>
									{/if}
								{/if}
							{/if}
						</td>
						{if $paymentsEnabled}
							<td>
								{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
									<a class="cmp_button" href="{url op="completePurchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">
										{translate key="user.subscriptions.purchase"}
									</a>
								{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
									{if !$isNonExpiring}
										<a class="cmp_button" href="{url op="payRenewSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">
											{translate key="user.subscriptions.renew"}
										</a>
									{/if}
									<a class="cmp_button" href="{url op="purchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">
										{translate key="user.subscriptions.purchase"}
									</a>
								{/if}
							</td>
						{/if}
					</tr>
				</table>
			{elseif $paymentsEnabled}
				<p>
					<a class="action" href="{url op="purchaseSubscription" path="individual"}">
						{translate key="user.subscriptions.purchaseNewSubscription"}
					</a>
				</p>
			{else}
				<p>
					<a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">
						{translate key="user.subscriptions.viewSubscriptionTypes"}
					</a>
				</p>
			{/if}
		</div>
	{/if}

	{if $institutionalSubscriptionTypesExist}
		<div class="my_subscriptions_institutional">
			<h3>{translate key="user.subscriptions.institutionalSubscriptions"}</h3>
			<p>
				{translate key="subscriptions.institutionalDescription"}
				{if $paymentsEnabled}
					{translate key="subscriptions.institutionalOnlinePaymentDescription"}
				{/if}
			</p>
			{if $userInstitutionalSubscriptions}
				<table class="cmp_table">
					<tr>
						<th>{translate key="user.subscriptions.form.typeId"}</th>
						<th>{translate key="user.subscriptions.form.institutionName"}</th>
						<th>{translate key="subscriptions.status"}</th>
						{if $paymentsEnabled}
							<th></th>
						{/if}
					</tr>
					{iterate from=userInstitutionalSubscriptions item=userInstitutionalSubscription}
						<tr>
							<td>{$userInstitutionalSubscription->getSubscriptionTypeName()|escape}</td>
							<td>{$userInstitutionalSubscription->getInstitutionName()|escape}</td>
							<td>
								{assign var="subscriptionStatus" value=$userInstitutionalSubscription->getStatus()}
								{assign var="isNonExpiring" value=$userInstitutionalSubscription->isNonExpiring()}
								{if $paymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
									<span class="subscription_disabled">
										{translate key="subscriptions.status.awaitingOnlinePayment"}
									</span>
								{elseif $paymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
									<span class="subscription_disabled">
										{translate key="subscriptions.status.awaitingManualPayment"}
									</span>
								{elseif $paymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_NEEDS_APPROVAL}
									<span class="subscription_disabled">
										{translate key="subscriptions.status.needsApproval"}
									</span>
								{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
									<span class="subscription_disabled">
										{translate key="subscriptions.inactive"}
									</span>
								{else}
									{if $isNonExpiring}
										<span class="subscription_active">
											{translate key="subscriptionTypes.nonExpiring"}
										</span>
									{else}
										{assign var="isExpired" value=$userInstitutionalSubscription->isExpired()}
										{if $isExpired}
											<span class="subscription_disabled">
												{translate key="user.subscriptions.expired" date=$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}
											</span>
										{else}
											<span class="subscription_enabled">
												{translate key="user.subscriptions.expires" date=$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}
											</span>
										{/if}
									{/if}
								{/if}
							</td>
							{if $paymentsEnabled}
								<td>
									{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
										<a class="cmp_button" href="{url op="completePurchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">
											{translate key="user.subscriptions.purchase"}
										</a>
									{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
										{if !$isNonExpiring}
											<a class="cmp_button" href="{url op="payRenewSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">
												{translate key="user.subscriptions.renew"}
											</a>
										{/if}
										<a class="cmp_button" href="{url op="purchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">
											{translate key="user.subscriptions.purchase"}
										</a>
									{/if}
								</td>
							{/if}
						</tr>
					{/iterate}
				</table>
			{/if}
			<p>
				{if $paymentsEnabled}
					<a class="action" href="{url page="user" op="purchaseSubscription" path="institutional"}">
						{translate key="user.subscriptions.purchaseNewSubscription"}
					</a>
				{else}
					<a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">
						{translate key="user.subscriptions.viewSubscriptionTypes"}
					</a>
				{/if}
			</p>
		</div>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
