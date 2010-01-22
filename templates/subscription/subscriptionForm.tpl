{**
 * subscriptionForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription form under journal management.
 *
 * $Id$
 *}
{assign var="pageCrumbTitle" value="$subscriptionTitle"}
{if $subscriptionId}
{assign var="pageTitle" value="manager.subscriptions.edit"}
{else}
{assign var="pageTitle" value="manager.subscriptions.create"}
{/if}
{assign var="pageId" value="manager.subscription.subscriptionForm"}
{include file="common/header.tpl"}

<br/>

<form method="post" action="{url op="updateSubscription"}">
{if $subscriptionId}
<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="userId" required="true" key="manager.subscriptions.form.userId"}</td>
	<td width="80%" class="value">
		{$user->getFullName()|escape}&nbsp;&nbsp;<a href="{if $subscriptionId}{url op="selectSubscriber" subscriptionId=$subscriptionId}{else}{url op="selectSubscriber"}{/if}" class="action">{translate key="common.select"}</a>
		<input type="hidden" name="userId" id="userId" value="{$user->getUserId()}"/>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="typeId" required="true" key="manager.subscriptions.form.typeId"}</td>
	<td class="value"><select name="typeId" id="typeId" class="selectMenu">
		{iterate from=subscriptionTypes item=subscriptionType}
		<option value="{$subscriptionType->getTypeId()}"{if $typeId == $subscriptionType->getTypeId()} selected="selected"{/if}>{$subscriptionType->getSummaryString()|escape}</option>
		{/iterate} 
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateStart" required="true" key="manager.subscriptions.form.dateStart"}</td>
	<td class="value" id="dateStart">{html_select_date prefix="dateStart" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" end_year="$yearOffsetFuture" time="$dateStart"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateEnd" required="true" key="manager.subscriptions.form.dateEnd"}</td>
	<td class="value" id="dateEnd">
		{html_select_date prefix="dateEnd" start_year="$yearOffsetPast" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" time="$dateEnd"}
		<input type="hidden" name="dateEndHour" value="23" />
		<input type="hidden" name="dateEndMinute" value="59" />
		<input type="hidden" name="dateEndSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<table width="100%">
			<tr valign="top">
				<td width="5%"><input type="checkbox" name="notifyEmail" id="notifyEmail" value="1"{if $notifyEmail} checked="checked"{/if} /></td>
				<td width="95%"><label for="notifyEmail">{translate key="manager.subscriptions.form.notifyEmail"}</label></td>
			</tr>
		</table>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="membership" key="manager.subscriptions.form.membership"}</td>
	<td class="value">
		<input type="text" name="membership" value="{$membership|escape}" id="membership" size="40" maxlength="40" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.subscriptions.form.membershipInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="domain" key="manager.subscriptions.form.domain"}</td>
	<td class="value">
		<input type="text" name="domain" value="{$domain|escape}" size="40" id="domain" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.subscriptions.form.domainInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="ipRange" key="manager.subscriptions.form.ipRange"}</td>
	<td class="value">
		<input type="text" id="ipRange" name="ipRange" value="{$ipRange|escape}" size="40" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.subscriptions.form.ipRangeInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $subscriptionId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptions.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptions" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
