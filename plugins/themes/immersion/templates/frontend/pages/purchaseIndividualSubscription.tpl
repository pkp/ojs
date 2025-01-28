{**
 * templates/frontend/pages/purchaseIndividualSubscription.tpl
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User purchase individual subscription form
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="user.subscriptions.purchaseIndividualSubscription"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="user.subscriptions.purchaseIndividualSubscription"}</span>
				</h1>
			</header>
			<form class="cmp_form purchase_subscription" method="post" id="subscriptionForm" action="{url op="payPurchaseSubscription" path="individual"|to_array:$subscriptionId}">
				{csrf}

				<fieldset>
					<div class="fields">
						<div class="subscription_type form-group">
							<label for="typeId">
								{translate key="user.subscriptions.form.typeId"}
							</label>
							<select class="form-control" name="typeId" id="typeId">
								{foreach name=types from=$subscriptionTypes key=thisTypeId item=subscriptionType}
									<option value="{$thisTypeId|escape}"{if $typeId == $thisTypeId} selected{/if}>{$subscriptionType|escape}</option>
								{/foreach}
							</select>
						</div>
						<div class="subscription_membership form-group">
							<label for="membership">
								{translate key="user.subscriptions.form.membership"}
							</label>
							<input class="form-control" type="text" name="membership" id="membership" value="{$membership|escape}">
						</div>
					</div>
				</fieldset>

				<div class="form-group form-group-buttons">
					<button class="btn btn-primary" type="submit">
						{translate key="common.save"}
					</button>
				</div>
			</form>
		</div>
	</div>
</main>

{include file="frontend/components/footer.tpl"}
