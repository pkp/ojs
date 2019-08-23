{**
 * templates/payments/index.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription index.
 *}
{include file="common/header.tpl" pageTitle="manager.subscriptions"}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#subscriptionsTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="subscriptionsTabs" class="pkp_controllers_tab">
	<ul>
		<li><a name="individualSubscription" href="{url op="subscriptions" path="individual"}">{translate key="subscriptionManager.individualSubscriptions"}</a></li>
		<li><a name="institutionalSubscriptions" href="{url op="subscriptions" path="institutional"}">{translate key="subscriptionManager.institutionalSubscriptions"}</a></li>
		<li><a name="subscriptionTypes" href="{url op="subscriptionTypes"}">{translate key="subscriptionManager.subscriptionTypes"}</a></li>
		<li><a name="subscriptionPolicies" href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
		<li><a name="paymentTypes" href="{url op="paymentTypes"}">{translate key="manager.paymentTypes"}</a></li>
		<li><a name="payments" href="{url op="payments"}">{translate key="manager.paymentMethod"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
