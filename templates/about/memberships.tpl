{**
 * templates/about/memberships.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Association memberships
 *
 *}
{strip}
{assign var="pageTitle" value="about.memberships"}
{include file="common/header.tpl"}
{/strip}
<div id="membershipFee">
<h3>{$membershipFeeName|escape}</h3>

<p>{$membershipFeeDescription|nl2br}<br />
{translate key="manager.subscriptionTypes.cost"} {$membershipFee|string_format:"%.2f"} ({$currency|escape})</p> 
</div>
{include file="common/footer.tpl"}

