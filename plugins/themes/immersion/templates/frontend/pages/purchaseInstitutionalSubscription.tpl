{**
 * templates/payments/userInstitutionalSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User purchase institutional subscription form
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="user.subscriptions.purchaseInstitutionalSubscription"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="user.subscriptions.purchaseInstitutionalSubscription"}</span>
				</h1>
			</header>

			{assign var="formPath" value="institutional"}
			{if $subscriptionId}
				{assign var="formPath" value="institutional"|to_array:$subscriptionId}
			{/if}
			<form class="cmp_form purchase_subscription" method="post" id="subscriptionForm"
			      action="{url op="payPurchaseSubscription" path=$formPath}">
				{csrf}

				{include file="common/formErrors.tpl"}

				<fieldset>
					<div class="fields">
						<div class="form-group subscription_type">
							<label for="typeId">
								{translate key="user.subscriptions.form.typeId"}
								<span class="required">*</span>
								<span class="visually-hidden">
								{translate key="common.required"}
								</span>
							</label>
							<select class="form-control" name="typeId" id="typeId" required>
								{foreach name=types from=$subscriptionTypes item=subscriptionType}
									<option value="{$subscriptionType->getId()}"{if $typeId == $subscriptionType->getId()} selected{/if}>{$subscriptionType->getSummaryString()|escape}</option>
								{/foreach}
							</select>
						</div>


						<div class="form-group subscription_membership">
							<label for="membership">
								{translate key="user.subscriptions.form.membership"}
							</label>
							<input class="form-control" type="text" name="membership" id="membership" value="{$membership|escape}"
							       aria-describedby="subscriptionMembershipDescription">

							<small class="form-text text-muted" id="subscriptionMembershipDescription">{translate key="user.subscriptions.form.membershipInstructions"}</small>
						</div>


						<div class="form-group subscription_institution">
							<label for="institutionName">
								{translate key="user.subscriptions.form.institutionName"}
							</label>
							<input class="form-control" type="text" name="institutionName" id="institutionName" value="{$institutionName|escape}">
						</div>


						<div class="form-group subscription_address">
							<label for="institutionMailingAddress">
								{translate key="user.subscriptions.form.institutionMailingAddress"}
							</label>
							<textarea class="form-control" name="institutionMailingAddress" id="institutionMailingAddress">{$institutionMailingAddress|escape}</textarea>
						</div>
					</div>
				</fieldset>

				<fieldset>
					<div class="fields">
						<div class="form-group subscription_domain">
							<label for="domain">
								{translate key="user.subscriptions.form.domain"}
							</label>
							<input class="form-control" type="text" name="domain" id="domain" value="{$domain|escape}" aria-describedby="subscriptionDomainDescription">
							<small class="form-text text-muted" id="subscriptionDomainDescription">{translate key="user.subscriptions.form.domainInstructions"}</small>
						</div>

						<div class="subscription_ips">
							<label>
						<span class="label">
							{translate key="user.subscriptions.form.ipRange"}
						</span>
								<input type="text" name="ipRanges" id="ipRanges" value="{$ipRanges|escape}"
								       aria-describedby="subscriptionIPDescription">
							</label>
							<small class="form-text text-muted" id="subscriptionIPDescription">{translate key="user.subscriptions.form.ipRangeInstructions"}</small>
						</div>
					</div>
				</fieldset>

				<div class="form-group form-group-buttons">
					<button class="btn btn-primary" type="submit">
						{translate key="common.continue"}
					</button>
					<a class="cmp_button_link" href="{url page="user" op="subscriptions"}">
						{translate key="common.cancel"}
					</a>
				</div>

			</form>

		</div>
	</div><!-- .row -->
</main><!-- .main__content -->
