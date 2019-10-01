{**
 * templates/payments/userInstitutionalSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User purchase institutional subscription form
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="user.subscriptions.purchaseInstitutionalSubscription"}

<div class="pkp_page_content pkp_page_purchaseInstitutionalSubscription">
	<h1 class="page_title">
		{translate key="user.subscriptions.purchaseInstitutionalSubscription"}
	</h1>

	{assign var="formPath" value="institutional"}
	{if $subscriptionId}
		{assign var="formPath" value="institutional"|to_array:$subscriptionId}
	{/if}
	<form class="cmp_form purchase_subscription" method="post" id="subscriptionForm" action="{url op="payPurchaseSubscription" path=$formPath}">
		{csrf}

		{include file="common/formErrors.tpl"}

		<fieldset>
			<div class="fields">
				<div class="subscription_type">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.typeId"}
							<span class="required">*</span>
							<span class="pkp_screen_reader">
								{translate key="common.required"}
							</span>
						</span>
						<select name="typeId" id="typeId" required>
							{foreach name=types from=$subscriptionTypes item=subscriptionType}
								<option value="{$subscriptionType->getId()}"{if $typeId == $subscriptionType->getId()} selected{/if}>{$subscriptionType->getLocalizedName()|escape}</option>
							{/foreach}
						</select>
					</label>
				</div>
				<div class="subscription_membership">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.membership"}
						</span>
						<input type="text" name="membership" id="membership" value="{$membership|escape}" aria-describedby="subscriptionMembershipDescription">
					</label>
					<p class="description" id="subscriptionMembershipDescription">{translate key="user.subscriptions.form.membershipInstructions"}</p>
				</div>
				<div class="subscription_institution">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.institutionName"}
						</span>
						<input type="text" name="institutionName" id="institutionName" value="{$institutionName|escape}">
					</label>
				</div>
				<div class="subscription_address">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.institutionMailingAddress"}
						</span>
						<textarea name="institutionMailingAddress" id="institutionMailingAddress">{$institutionMailingAddress|escape}</textarea>
					</label>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<div class="fields">
				<div class="subscription_domain">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.domain"}
						</span>
						<input type="text" name="domain" id="domain" value="{$domain|escape}" aria-describedby="subscriptionDomainDescription">
					</label>
					<p class="description" id="subscriptionDomainDescription">{translate key="user.subscriptions.form.domainInstructions"}</p>
				</div>
				<div class="subscription_ips">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.ipRange"}
						</span>
						<input type="text" name="ipRanges" id="ipRanges" value="{$ipRanges|escape}" aria-describedby="subscriptionIPDescription">
					</label>
					<p class="description" id="subscriptionIPDescription">{translate key="user.subscriptions.form.ipRangeInstructions"}</p>
				</div>
			</div>
		</fieldset>

		<div class="buttons">
			<button class="submit" type="submit">
				{translate key="common.continue"}
			</button>
			<a class="cmp_button_link" href="{url page="user" op="subscriptions"}">
				{translate key="common.cancel"}
			</a>
		</div>

	</form>
</div>

{include file="frontend/components/footer.tpl"}
