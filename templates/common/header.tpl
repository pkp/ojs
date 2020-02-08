{**
 * templates/common/header.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *}
{capture assign="appSpecificMenuItems"}
	{if array_intersect(array(ROLE_ID_MANAGER), (array)$userRoles)}
		<li aria-haspopup="true" aria-expanded="false">
			<a name="issues" href="#">{translate key="editor.navigation.issues"}</a>
			<ul>
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="manageIssues" anchor="futureIssues"}">{translate key="editor.issues.futureIssues"}</a></li>
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="manageIssues" anchor="backIssues"}">{translate key="editor.issues.backIssues"}</a></li>
			</ul>
		</li>
	{/if}
	{if $currentJournal && $currentJournal->getData('paymentsEnabled') && array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER), (array)$userRoles)}
		<li aria-haspopup="true" aria-expanded="false">
			<a name="payments" href="#">{translate key="common.payments"}</a>
			<ul>
				<li><a name="individualSubscription" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="individualSubscription"}">{translate key="subscriptionManager.individualSubscriptions"}</a></li>
				<li><a name="institutionalSubscriptions" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="institutionalSubscriptions"}">{translate key="subscriptionManager.institutionalSubscriptions"}</a></li>
				<li><a name="subscriptionTypes" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="subscriptionTypes"}">{translate key="subscriptionManager.subscriptionTypes"}</a></li>
				<li><a name="subscriptionPolicies" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
				<li><a name="paymentTypes" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="paymentTypes"}">{translate key="manager.paymentTypes"}</a></li>
				<li><a name="payments" href="{url router=$smarty.const.ROUTE_PAGE page="payments" anchor="payments"}">{translate key="common.payments"}</a></li>
			</ul>
		</li>
	{/if}
{/capture}
{include file="core:common/header.tpl" appSpecificMenuItems=$appSpecificMenuItems}
