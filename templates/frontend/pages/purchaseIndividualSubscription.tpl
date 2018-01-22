{**
 * templates/frontend/pages/purchaseIndividualSubscription.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User purchase individual subscription form
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="user.subscriptions.purchaseIndividualSubscription"}

<div class="pkp_page_content pkp_page_purchaseIndividualSubscription">
	<form class="cmp_form purchase_subscription" method="post" id="subscriptionForm" action="{url op="payPurchaseSubscription" path="individual"|to_array:$subscriptionId}">
		{csrf}

		<fieldset>
			<div class="fields">
				<div class="subscription_type">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.typeId"}
						</span>
						<select name="typeId" id="typeId">
							{foreach name=types from=$subscriptionTypes key=thisTypeId item=subscriptionType}
								<option value="{$thisTypeId|escape}"{if $typeId == $thisTypeId} selected{/if}>{$subscriptionType|escape}</option>
							{/foreach}
						</select>
					</label>
				</div>
				<div class="subscription_membership">
					<label>
						<span class="label">
							{translate key="user.subscriptions.form.membership"}
						</span>
						<input type="text" name="membership" id="membership" value="{$membership|escape}">
					</label>
				</div>
			</div>
		</fieldset>

		<div class="buttons">
			<button class="submit" type="submit">
				{translate key="common.save"}
			</button>
		</div>
	</form>
</div>

{include file="frontend/components/footer.tpl"}
