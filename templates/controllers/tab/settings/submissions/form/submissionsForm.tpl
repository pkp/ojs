{**
 * templates/controllers/tab/settings/submissions/form/submissionsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#submissionSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submissionSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="submissions"}">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submissionsFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

<div id="reviewProcess">
<h4>{translate key="manager.setup.reviewProcess"}</h4>

<p>{translate key="manager.setup.reviewProcessDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-0" value="0"{if not $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="mailSubmissionsToReviewers-0"><strong>{translate key="manager.setup.reviewProcessStandard"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessStandardDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-1" value="1"{if $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="mailSubmissionsToReviewers-1"><strong>{translate key="manager.setup.reviewProcessEmail"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessEmailDescription"}</span>
		</td>
	</tr>
</table>
</div>
<div id="reviewOptions">
<h4>{translate key="manager.setup.reviewOptions"}</h4>

	<script>
		{literal}
			function toggleAllowSetInviteReminder(form) {
				form.numDaysBeforeInviteReminder.disabled = !form.numDaysBeforeInviteReminder.disabled;
			}
			function toggleAllowSetSubmitReminder(form) {
				form.numDaysBeforeSubmitReminder.disabled = !form.numDaysBeforeSubmitReminder.disabled;
			}
		{/literal}
	</script>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerAccess"}</strong><br/>
	<input type="checkbox" name="reviewerAccessKeysEnabled" id="reviewerAccessKeysEnabled" value="1"{if $reviewerAccessKeysEnabled} checked="checked"{/if} />&nbsp;
	<label for="reviewerAccessKeysEnabled">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}</label><br/>
	<span class="instruct">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</span><br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;
	<label for="restrictReviewerFileAccess">{translate key="manager.setup.reviewOptions.restrictReviewerFileAccess"}</label>
</p>

</div>
</div>

<div class="separator"></div>

<div id="editorDecision">
<h3>2.4 {translate key="manager.setup.editorDecision"}</h3>

<p><input type="checkbox" name="notifyAllAuthorsOnDecision" id="notifyAllAuthorsOnDecision" value="1"{if $notifyAllAuthorsOnDecision} checked="checked"{/if} /> <label for="notifyAllAuthorsOnDecision">{translate key="manager.setup.notifyAllAuthorsOnDecision"}</label></p>
</div>
<div class="separator"></div>

<div id="addItemtoAboutJournal">
<h3>2.5 {translate key="manager.setup.addItemtoAboutJournal"}</h3>

<table class="data">
{foreach name=customAboutItems from=$customAboutItems[$formLocale] key=aboutId item=aboutItem}
	<tr>
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][title]" id="customAboutItems-{$aboutId|escape}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField richContent" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutItemContent"}</td>
		<td class="value"><textarea name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][content]" id="customAboutItems-{$aboutId|escape}-content" rows="12" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.customAboutItems.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-0-title" key="common.title"}</td>
		<td class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][0][title]" id="customAboutItems-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutItemContent"}</td>
		<td class="value"><textarea name="customAboutItems[{$formLocale|escape}][0][content]" id="customAboutItems-0-content" rows="12" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.addAboutItem"}" class="button" /></p>
</div>
<div class="separator"></div>

<div id="reviewerDatabaseLink">
<h3>2.7 {translate key="manager.setup.reviewerDatabaseLink"}</h3>

<p>{translate key="manager.setup.reviewerDatabaseLink.desc"}</p>

<table class="data">
{foreach name=reviewerDatabaseLinks from=$reviewerDatabaseLinks key=reviewerDatabaseLinkId item=reviewerDatabaseLink}
	<tr>
		<td width="5%" class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-title" key="common.title"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId|escape}][title]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId|escape}-title" value="{$reviewerDatabaseLink.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.reviewerDatabaseLinks.total > 1} <input type="submit" name="delReviewerDatabaseLink[{$reviewerDatabaseLinkId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-url" key="common.url"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId|escape}][url]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId|escape}-url" value="{$reviewerDatabaseLink.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.reviewerDatabaseLinks.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-0-title" key="common.title"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[0][title]" id="reviewerDatabaseLinks-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-0-url" key="common.url"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[0][url]" id="reviewerDatabaseLinks-0-url" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
