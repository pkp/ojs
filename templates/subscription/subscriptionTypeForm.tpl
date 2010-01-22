{**
 * subscriptionTypeForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription type form under journal management.
 *
 * $Id$
 *}
{if $typeId}
{assign var="pageTitle" value="manager.subscriptionTypes.edit"}
{else}
{assign var="pageTitle" value="manager.subscriptionTypes.create"}
{/if}
{assign var="pageId" value="manager.subscriptionTypes.subscriptionTypeForm"}
{assign var="pageCrumbTitle" value=$subscriptionTypeTitle}
{include file="common/header.tpl"}

{if $subscriptionTypeCreated}
<br/>
{translate key="manager.subscriptionTypes.subscriptionTypeCreatedSuccessfully"}<br />
{/if}

<br/>

<form name="subscriptionType" method="post" action="{url op="updateSubscriptionType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId|escape}" />
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $typeId}{url|assign:"subscriptionTypeUrl" op="editSubscriptionType" path=$typeId escape=false}
			{else}{url|assign:"subscriptionTypeUrl" op="createSubscriptionType" escape=false}
			{/if}
			{form_language_chooser form="subscriptionType" url=$subscriptionTypeUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.subscriptionTypes.form.typeName"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="35" maxlength="80" id="name" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.subscriptionTypes.form.description"}</td>
	<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="4" class="textArea">{$description[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="cost" required="true" key="manager.subscriptionTypes.form.cost"}</td>
	<td class="value">
		<input type="text" name="cost" value="{$cost|escape}" size="5" maxlength="10" id="cost" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.subscriptionTypes.form.costInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="currency" required="true" key="manager.subscriptionTypes.form.currency"}</td>
	<td><select name="currency" id="currency" class="selectMenu">{html_options options=$validCurrencies selected=$currency}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="duration" required="true" key="manager.subscriptionTypes.form.duration"}</td>
	<td class="value">
		<input type="text" name="duration" value="{$duration|escape}" size="5" maxlength="10" id="duration" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.subscriptionTypes.form.durationInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="format" required="true" key="manager.subscriptionTypes.form.format"}</td>
	<td><select id="format" name="format" class="selectMenu">{html_options options=$validFormats selected=$format}</select></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="institutional" id="institutional" value="1"{if $institutional} checked="checked"{/if} />
		<label for="institutional">{translate key="manager.subscriptionTypes.form.institutional"}</label>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="membership" id="membership" value="1"{if $membership} checked="checked"{/if} />
		<label for="membership">{translate key="manager.subscriptionTypes.form.membership"}</label>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="disable_public_display" id="disable_public_display" value="1"{if $disable_public_display} checked="checked"{/if} />
		<label for="disable_public_display">{translate key="manager.subscriptionTypes.form.public"}</label>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptionTypes.form.saveAndCreateAnotherType"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptionTypes" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
