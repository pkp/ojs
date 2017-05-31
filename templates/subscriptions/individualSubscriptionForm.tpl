{**
 * templates/subscriptions/individualSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Individual subscription form under journal management.
 *
 *}
<br/>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#subscriptionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="individualSubscriptionForm" action="{url op="updateSubscription"}">
{if $subscriptionId}
	<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
{/if}
{csrf}

{include file="common/formErrors.tpl"}

<table class="data">
{include file="subscriptions/subscriptionForm.tpl"}

{* For new subscriptions, select end date for default subscription type *}
{if !$subscriptionId}
	<script>
	<!--
	chooseEndDate();
	// -->
	</script>
{/if}
</table>

<br />
<div class="separator"></div>
<br />

<table class="data">
<tr>
	<td class="label">{fieldLabel name="userId" required="true" key="manager.subscriptions.form.userId"}</td>
	<td class="value">
		{$username|escape}
		<input type="hidden" name="userId" id="userId" value="{$userId|escape}"/>
	</td>
</tr>
{include file="subscriptions/subscriptionFormUser.tpl"}
</table>

<br />
<div class="separator"></div>
<br />

<table class="data">
<tr>
	<td class="label">{fieldLabel name="notes" key="manager.subscriptions.form.notes"}</td>
	<td class="value"><textarea name="notes" id="notes" cols="40" rows="6" class="textArea richContent">{$notes|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $subscriptionId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptions.form.saveAndCreateAnother"}" class="button" /> {/if}

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
