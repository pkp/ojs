{**
 * step2.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.journalPolicies"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="2"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" required="true" key="common.language"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="2"}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
		</td>
	</tr>
</table>
{/if}

<h3>2.1 {translate key="manager.setup.focusAndScopeOfJournal"}</h3>
<p>{translate key="manager.setup.focusAndScopeDescription"}</p>
<p>
	<textarea name="focusScopeDesc[{$formLocale|escape}]" id="focusScopeDesc" rows="12" cols="60" class="textArea">{$focusScopeDesc[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>


<div class="separator"></div>


<h3>2.2 {translate key="manager.setup.peerReviewPolicy"}</h3>

<p>{translate key="manager.setup.peerReviewDescription"}</p>

<h4>{translate key="manager.setup.reviewPolicy"}</h4>

<p><textarea name="reviewPolicy[{$formLocale|escape}]" id="reviewPolicy" rows="12" cols="60" class="textArea">{$reviewPolicy[$formLocale]|escape}</textarea></p>


<h4>{translate key="manager.setup.reviewGuidelines"}</h4>

<p>{translate key="manager.setup.reviewGuidelinesDescription"}</p>

<p><textarea name="reviewGuidelines[{$formLocale|escape}]" id="reviewGuidelines" rows="12" cols="60" class="textArea">{$reviewGuidelines[$formLocale]|escape}</textarea></p>

<h4>{translate key="manager.setup.reviewProcess"}</h4>

<p>{translate key="manager.setup.reviewProcessDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-0" value="0"{if not $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="mailSubmissionsToReviewers-0"><strong>{translate key="manager.setup.reviewProcessStandard"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessStandardDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-1" value="1"{if $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="mailSubmissionsToReviewers-1"><strong>{translate key="manager.setup.reviewProcessEmail"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessEmailDescription"}</span>
		</td>
	</tr>
</table>

<h4>{translate key="manager.setup.reviewOptions"}</h4>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAllowSetInviteReminder(form) {
				form.numDaysBeforeInviteReminder.disabled = !form.numDaysBeforeInviteReminder.disabled;
			}
			function toggleAllowSetSubmitReminder(form) {
				form.numDaysBeforeSubmitReminder.disabled = !form.numDaysBeforeSubmitReminder.disabled;
			}
		// -->
		{/literal}
	</script>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewTime"}</strong><br/>
	{translate key="manager.setup.reviewOptions.numWeeksPerReview"}: <input type="text" name="numWeeksPerReview" id="numWeeksPerReview" value="{$numWeeksPerReview|escape}" size="2" maxlength="8" class="textField" /> {translate key="common.weeks"}<br/>
	{translate key="common.note"}: {translate key="manager.setup.reviewOptions.noteOnModification"}
</p>

	<p>
		<strong>{translate key="manager.setup.reviewOptions.reviewerReminders"}</strong><br/>
		{translate key="manager.setup.reviewOptions.automatedReminders"}:<br/>
		<input type="checkbox" name="remindForInvite" id="remindForInvite" value="1" onclick="toggleAllowSetInviteReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $remindForInvite} checked="checked"{/if} />&nbsp;
		<label for="remindForInvite">{translate key="manager.setup.reviewOptions.remindForInvite1"}</label>
		<select name="numDaysBeforeInviteReminder" size="1" class="selectMenu"{if not $remindForInvite || !$scheduledTasksEnabled} disabled="disabled"{/if}>
			{section name="inviteDayOptions" start=3 loop=11}
			<option value="{$smarty.section.inviteDayOptions.index}"{if $numDaysBeforeInviteReminder eq $smarty.section.inviteDayOptions.index or ($smarty.section.inviteDayOptions.index eq 5 and not $remindForInvite)} selected="selected"{/if}>{$smarty.section.inviteDayOptions.index}</option>
			{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForInvite2"}
		<br/>

		<input type="checkbox" name="remindForSubmit" id="remindForSubmit" value="1" onclick="toggleAllowSetSubmitReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled"{elseif $remindForSubmit} checked="checked"{/if} />&nbsp;
		<label for="remindForSubmit">{translate key="manager.setup.reviewOptions.remindForSubmit1"}</label>
		<select name="numDaysBeforeSubmitReminder" size="1" class="selectMenu"{if not $remindForSubmit || !$scheduledTasksEnabled} disabled="disabled"{/if}>
			{section name="submitDayOptions" start=0 loop=11}
				<option value="{$smarty.section.submitDayOptions.index}"{if $numDaysBeforeSubmitReminder eq $smarty.section.submitDayOptions.index} selected="selected"{/if}>{$smarty.section.submitDayOptions.index}</option>
		{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForSubmit2"}
		{if !$scheduledTasksEnabled}
		<br/>
		{translate key="manager.setup.reviewOptions.automatedRemindersDisabled"}
		{/if}
	</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerRatings"}</strong><br/>
	<input type="checkbox" name="rateReviewerOnQuality" id="rateReviewerOnQuality" value="1"{if $rateReviewerOnQuality} checked="checked"{/if} />&nbsp;
	<label for="rateReviewerOnQuality">{translate key="manager.setup.reviewOptions.onQuality"}</label>
</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerAccess"}</strong><br/>
	<input type="checkbox" name="reviewerAccessKeysEnabled" id="reviewerAccessKeysEnabled" value="1"{if $reviewerAccessKeysEnabled} checked="checked"{/if} />&nbsp;
	<label for="reviewerAccessKeysEnabled">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}</label><br/>
	<span class="instruct">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</span><br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;
	<label for="restrictReviewerFileAccess">{translate key="manager.setup.reviewOptions.restrictReviewerFileAccess"}</label>
</p>


<div class="separator"></div>


<h3>2.3 {translate key="manager.setup.privacyStatement"}</h3>

<p><textarea name="privacyStatement[{$formLocale|escape}]" id="privacyStatement" rows="12" cols="60" class="textArea">{$privacyStatement[$formLocale]|escape}</textarea></p>


<div class="separator"></div>


<h3>2.4 {translate key="manager.setup.editorDecision"}</h3>

<p><input type="checkbox" name="notifyAllAuthorsOnDecision" id="notifyAllAuthorsOnDecision" value="1"{if $notifyAllAuthorsOnDecision} checked="checked"{/if} /> <label for="notifyAllAuthorsOnDecision">{translate key="manager.setup.notifyAllAuthorsOnDecision"}</label></p>

<div class="separator"></div>


<h3>2.5 {translate key="manager.setup.addItemtoAboutJournal"}</h3>

<table width="100%" class="data">
{foreach name=customAboutItems from=$customAboutItems[$formLocale] key=aboutId item=aboutItem}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][{$aboutId}][title]" id="customAboutItems-{$aboutId}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$formLocale|escape}][{$aboutId}][content]" id="customAboutItems-{$aboutId}-content" rows="12" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.customAboutItems.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-title" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][0][title]" id="customAboutItems-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$formLocale|escape}][0][content]" id="customAboutItems-0-content" rows="12" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.addAboutItem"}" class="button" /></p>

<div class="separator"></div>


<h3>2.6 {translate key="manager.setup.journalArchiving"}</h3>

<p>{translate key="manager.setup.lockssDescription"}</p>

{url|assign:"lockssExistingArchiveUrl" page="manager" op="email" template="LOCKSS_EXISTING_ARCHIVE"}
{url|assign:"lockssNewArchiveUrl" page="manager" op="email" template="LOCKSS_NEW_ARCHIVE"}
<p>{translate key="manager.setup.lockssRegister" lockssExistingArchiveUrl=$lockssExistingArchiveUrl lockssNewArchiveUrl=$lockssNewArchiveUrl}</p>

{url|assign:"lockssUrl" page="gateway" op="lockss"}
<p><input type="checkbox" name="enableLockss" id="enableLockss" value="1"{if $enableLockss} checked="checked"{/if} /> <label for="enableLockss">{translate key="manager.setup.lockssEnable" lockssUrl=$lockssUrl}</label></p>

<p>
	<textarea name="lockssLicense" id="lockssLicense" rows="6" cols="60" class="textArea">{$lockssLicense|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.lockssLicenses"}</span>
</p>


<div class="separator"></div>


<h3>2.7 {translate key="manager.setup.reviewerDatabaseLink"}</h3>

<p>{translate key="manager.setup.reviewerDatabaseLink.desc"}</p>

<table width="100%" class="data">
{foreach name=reviewerDatabaseLinks from=$reviewerDatabaseLinks key=reviewerDatabaseLinkId item=reviewerDatabaseLink}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-title" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId}][title]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId}-title" value="{$reviewerDatabaseLink.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.reviewerDatabaseLinks.total > 1} <input type="submit" name="delReviewerDatabaseLink[{$reviewerDatabaseLinkId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId}][url]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId}-url" value="{$reviewerDatabaseLink.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.reviewerDatabaseLinks.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="reviewerDatabaseLinks-0-title" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" name="reviewerDatabaseLinks[0][title]" id="reviewerDatabaseLinks-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="reviewerDatabaseLinks-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="reviewerDatabaseLinks[0][url]" id="reviewerDatabaseLinks-0-url" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addReviewerDatabaseLink" value="{translate key="manager.setup.addReviewerDatabaseLink"}" class="button" /></p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
