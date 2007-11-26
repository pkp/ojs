{**
 * memberships.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Association memberships
 *
 *}
{assign var="pageTitle" value="about.memberships"}
{include file="common/header.tpl"}
<h3>{$membershipFeeName|escape}</h3>

<p>{$membershipFeeDescription|nl2br}<br />
{translate key="manager.subscriptionTypes.cost"} {$membershipFee|string_format:"%.2f"} ({$currency})</p> 

{include file="common/footer.tpl"}