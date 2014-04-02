{**
 * templates/subscription/subscriptionsSummary.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display summary subscriptions page in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.subscriptions.summary"}
{assign var="pageId" value="manager.subscriptions.summary"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{url op="subscriptionsSummary"}">{translate key="manager.subscriptions.summary"}</a></li>
	<li><a href="{url op="subscriptions" path="individual"}">{translate key="manager.individualSubscriptions"}</a></li>
	<li><a href="{url op="subscriptions" path="institutional"}">{translate key="manager.institutionalSubscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
	<li><a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
</ul>

<h3>{translate key="manager.individualSubscriptions"}</h3>
<ul class="plain">
	{foreach name=allStatus from=$individualStatus key=statusIndex item=status}
	<li>&#187; <a href="{url op="subscriptions" path="individual" filterStatus=$status.status}">{translate key=$status.localeKey}</a> ({$status.count})</li>
	{/foreach}
</ul>
<a href="{url op="selectSubscriber" path="individual"}" class="action">{translate key="manager.subscriptions.create"}</a>

<h3>{translate key="manager.institutionalSubscriptions"}</h3>
<ul class="plain">
	{foreach name=allStatus from=$institutionalStatus key=statusIndex item=status}
	<li>&#187; <a href="{url op="subscriptions" path="institutional" filterStatus=$status.status}">{translate key=$status.localeKey}</a> ({$status.count})</li>
	{/foreach}
</ul>
<a href="{url op="selectSubscriber" path="institutional"}" class="action">{translate key="manager.subscriptions.create"}</a>

{include file="common/footer.tpl"}

