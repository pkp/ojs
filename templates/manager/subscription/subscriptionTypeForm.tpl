{**
 * subscriptionTypeForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription type form under journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="$subscriptionTypeTitle"}
{assign var="pageId" value="manager.subscriptionTypes.subscriptionTypeForm"}
{include file="common/header.tpl"}

{if $subscriptionTypeCreated}
{translate key="manager.subscriptionTypes.subscriptionTypeCreatedSuccessfully"}<br /><br />
{/if}

<form method="post" action="{$pageUrl}/manager/updateSubscriptionType">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId}" />
{/if}

<div class="form">
<div class="subTitle">{if $typeId}{translate key="manager.subscriptionTypes.edit"}{else}{translate key="manager.subscriptionTypes.create"}{/if}</div>
<br />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="typeName" required="true"}{translate key="manager.subscriptionTypes.form.typeName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="typeName" value="{$typeName|escape}" size="35" maxlength="80" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="description"}{translate key="manager.subscriptionTypes.form.description"}:{/formLabel}</td>
	<td class="formField"><textarea name="description" cols="40" rows="2" class="textArea" />{$description|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="cost" required="true"}{translate key="manager.subscriptionTypes.form.cost"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="cost" value="{$cost|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr> 
	<td></td>
	<td class="formInstructions">{translate key="manager.subscriptionTypes.form.costInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="currency" required="true"}{translate key="manager.subscriptionTypes.form.currency"}:{/formLabel}</td>
	<td><select name="currency" class="select" />{html_options options=$validCurrencies selected=$currency}</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="format" required="true"}{translate key="manager.subscriptionTypes.form.format"}:{/formLabel}</td>
	<td><select name="format" class="select" />{html_options options=$validFormats selected=$format}</select></td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="institutional" value="1"{if $institutional} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.subscriptionTypes.form.institutional"}</td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="membership" value="1"{if $membership} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.subscriptionTypes.form.membership"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="seq" required="true"}{translate key="manager.subscriptionTypes.form.seq"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="seq" value="{$seq|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr> 
	<td></td>
	<td class="formInstructions">{translate key="manager.subscriptionTypes.form.seqInstructions"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptionTypes.form.saveAndCreateAnotherType"}" class="formButton" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/subscriptionTypes'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
