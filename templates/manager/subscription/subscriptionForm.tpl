{**
 * subscriptionForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription form under journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="$subscriptionTitle"}
{assign var="pageId" value="manager.subscription.subscriptionForm"}
{include file="common/header.tpl"}

{if $subscriptionCreated}
{translate key="manager.subscriptions.subscriptionCreatedSuccessfully"}<br /><br />
{/if}

<form method="post" action="{$pageUrl}/manager/updateSubscription">
{if $subscriptionId}
<input type="hidden" name="subscriptionId" value="{$subscriptionId}" />
{/if}

<div class="form">
<div class="subTitle">{if $subscriptionId}{translate key="manager.subscriptions.edit"}{else}{translate key="manager.subscriptions.create"}{/if}</div>
<br />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="userId" required="true"}{translate key="manager.subscriptions.form.userId"}:{/formLabel}</td>
	<td><select name="userId" class="select" />
		{foreach from=$users item=user}
		<option value="{$user->getUserId()}" {if $userId == $user->getUserId()}selected="selected"{/if}>{$user->getFullName()} [{$user->getUsername()}]</option>
		{/foreach} 
	</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="typeId" required="true"}{translate key="manager.subscriptions.form.typeId"}:{/formLabel}</td>
	<td><select name="typeId" class="select" />
		{foreach from=$subscriptionTypes item=subscriptionType}
		<option value="{$subscriptionType->getTypeId()}"{if $typeId == $subscriptionType->getTypeId()} selected="selected"{/if}>{$subscriptionType->getTypeName()}</option>
		{/foreach} 
	</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="dateStart" required="true"}{translate key="manager.subscriptions.form.dateStart"}:{/formLabel}</td>
	<td>{html_select_date prefix="dateStart" start_year="$yearOffsetPast" end_year="$yearOffsetFuture"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="dateEnd" required="true"}{translate key="manager.subscriptions.form.dateEnd"}:{/formLabel}</td>
	<td>{html_select_date prefix="dateEnd" start_year="$yearOffsetPast" end_year="$yearOffsetFuture"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="membership"}{translate key="manager.subscriptions.form.membership"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="membership" value="{$membership|escape}" size="40" maxlength="40" class="textField" /></td>
</tr>
<tr> 
	<td></td>
	<td class="formInstructions">{translate key="manager.subscriptions.form.membershipInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="domain"}{translate key="manager.subscriptions.form.domain"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="domain" value="{$domain|escape}" size="40" maxlength="255" class="textField" /></td>
</tr>
<tr> 
	<td></td>
	<td class="formInstructions">{translate key="manager.subscriptions.form.domainInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="ipRange"}{translate key="manager.subscriptions.form.ipRange"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="ipRange" value="{$ipRange|escape}" size="40" maxlength="255" class="textField" /></td>
</tr>
<tr> 
	<td></td>
	<td class="formInstructions">{translate key="manager.subscriptions.form.ipRangeInstructions"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> {if not $subscriptionId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptions.form.saveAndCreateAnother"}" class="formButton" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/subscriptions'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
