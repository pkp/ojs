{**
 * step2.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalPolicies}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{$pageUrl}/manager/saveSetup/2">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="manager.setup.focusAndScopeOfJournal"}</h3>
<p>{translate key="manager.setup.focusAndScopeDescription"}</p>
<p>
	<textarea name="focusScopeDesc" id="focusScopeDesc" rows="12" cols="60" class="textArea">{$focusScopeDesc|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>


<div class="separator"></div>


<h3>2.2 {translate key="manager.setup.peerReviewPolicy"}</h3>

<p>{translate key="manager.setup.peerReviewDescription"}</p>

<h4>{translate key="manager.setup.reviewPolicy"}</h4>

<p><textarea name="reviewPolicy" id="reviewPolicy" rows="12" cols="60" class="textArea">{$reviewPolicy|escape}</textarea></p>


<h4>{translate key="manager.setup.reviewGuidelines"}</h4>

<p>{translate key="manager.setup.reviewGuidelinesDescription"}</p>

<p><textarea name="reviewGuidelines" id="reviewGuidelines" rows="12" cols="60" class="textArea">{$reviewGuidelines|escape}</textarea></p>

<h4>{translate key="manager.setup.reviewProcess"}</h4>

<p>{translate key="manager.setup.reviewProcessDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers[0]" value="0"{if not $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="mailSubmissionsToReviewers[0]"><strong>{translate key="manager.setup.reviewProcessStandard"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessStandardDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers[1]" value="1"{if $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="mailSubmissionsToReviewers[1]"><strong>{translate key="manager.setup.reviewProcessEmail"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessEmailDescription"}</span>
		</td>
	</tr>
</table>

<h4>{translate key="manager.setup.reviewOptions"}</h4>

{if $scheduledTasksEnabled}
	<script type="text/javascript">
		{literal}
			function toggleAllowSetInviteReminder(form) {
				form.numDaysBeforeInviteReminder.disabled = !form.numDaysBeforeInviteReminder.disabled;
			}
			function toggleAllowSetSubmitReminder(form) {
				form.numDaysBeforeSubmitReminder.disabled = !form.numDaysBeforeSubmitReminder.disabled;
			}
		{/literal}
	</script>
{/if}

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewTime"}</strong><br/>
	{translate key="manager.setup.reviewOptions.numWeeksPerReview"}: <input type="text" name="numWeeksPerReview" id="numWeeksPerReview" value="{$numWeeksPerReview|escape}" size="2" maxlength="8" class="textField" /> {translate key="common.weeks"}<br/>
	{translate key="common.note"}: {translate key="manager.setup.reviewOptions.noteOnModification"}
</p>

{if $scheduledTasksEnabled}
	<p>
		<strong>{translate key="manager.setup.reviewOptions.reviewerReminders"}</strong><br/>
		{translate key="manager.setup.reviewOptions.automatedReminders"}:<br/>
		<input type="checkbox" name="remindForInvite" id="remindForInvite" value="1" onclick="toggleAllowSetInviteReminder(this.form)"{if $remindForInvite} checked="checked"{/if} />&nbsp;
		<label for="remindForInvite">{translate key="manager.setup.reviewOptions.remindForInvite1"}</label>
		<select name="numDaysBeforeInviteReminder" size="1" class="selectMenu"{if not $remindForInvite} disabled="disabled"{/if}>
			{section name="inviteDayOptions" start=3 loop=11}
			<option value="{$smarty.section.inviteDayOptions.index}"{if $numDaysBeforeInviteReminder eq $smarty.section.inviteDayOptions.index or ($smarty.section.inviteDayOptions.index eq 5 and not $remindForInvite)} selected="SELECTED"{/if}>{$smarty.section.inviteDayOptions.index}</option>
			{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForInvite2"}
		<br/>

		<input type="checkbox" name="remindForSubmit" id="remindForSubmit" value="1" onclick="toggleAllowSetSubmitReminder(this.form)"{if $remindForSubmit} checked="checked"{/if} />&nbsp;
		<label for="remindForSubmit">{translate key="manager.setup.reviewOptions.remindForSubmit1"}</label>
		<select name="numDaysBeforeSubmitReminder" size="1" class="selectMenu"{if not $remindForSubmit} disabled="disabled"{/if}>
			{section name="submitDayOptions" start=0 loop=11}
				<option value="{$smarty.section.submitDayOptions.index}"{if $numDaysBeforeSubmitReminder eq $smarty.section.submitDayOptions.index} selected="SELECTED"{/if}>{$smarty.section.submitDayOptions.index}</option>
		{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForSubmit2"}<br/>
		{translate key="common.note"}: {translate key="manager.setup.reviewOptions.noteOnEmailWording"}
	</p>
{/if}

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerRatings"}</strong><br/>
	<input type="checkbox" name="rateReviewerOnQuality" id="rateReviewerOnQuality" value="1"{if $rateReviewerOnQuality} checked="checked"{/if} />&nbsp;
	<label for="rateReviewerOnQuality">{translate key="manager.setup.reviewOptions.onQuality"}</label>
</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerAccess"}</strong><br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;
	<label for="restrictReviewerFileAccess">{translate key="manager.setup.reviewOptions.restrictReviewerFileAccess"}</label>
</p>


<div class="separator"></div>


<h3>2.3 {translate key="manager.setup.privacyStatement"}</h3>

<p><textarea name="privacyStatement" id="privacyStatement" rows="12" cols="60" class="textArea">{$privacyStatement|escape}</textarea></p>


<div class="separator"></div>


<h3>2.4 {translate key="manager.setup.openAccessPolicy"}</h3>

<p>{translate key="manager.setup.openAccessPolicyDescription"}</p>

<p><textarea name="openAccessPolicy" id="openAccessPolicy" rows="12" cols="60" class="textArea">{$openAccessPolicy|escape}</textarea></p>


<div class="separator"></div>


<h3>2.5 {translate key="manager.setup.addItemtoAboutJournal"}</h3>

<table width="100%" class="data">
{foreach name=customAboutItems from=$customAboutItems key=aboutId item=aboutItem}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="customAboutItems[$aboutId][title]" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="customAboutItems[{$aboutId}][title]" id="customAboutItems[{$aboutId}][title]" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems[$aboutId][content]" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$aboutId}][content]" id="customAboutItems[{$aboutId}][content]" rows="12" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.contributors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems[0][title]" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" name="customAboutItems[0][title]" id="customAboutItems[0][title]" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems[0][content]" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[0][content]" id="customAboutItems[0][content]" rows="12" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.addAboutItem"}" class="button" /></p>

<div class="separator"></div>


<h3>2.6 {translate key="manager.setup.journalArchiving"}</h3>

<p>{translate key="manager.setup.lockssDescription"}</p>

<p>{translate key="manager.setup.lockssRegister" pageUrl=$pageUrl}</p>

<p><input type="checkbox" name="enableLockss" id="enableLockss" value="1"{if $enableLockss} checked="checked"{/if} /> <label for="enableLockss">{translate key="manager.setup.lockssEnable" pageUrl=$pageUrl}</label></p>

<p>
	<textarea name="lockssLicense" id="lockssLicense" rows="6" cols="60" class="textArea">{$lockssLicense|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.lockssLicenses"}</span>
</p>


<div class="separator"></div>


<h3>2.7 {translate key="manager.setup.securitySettings"}</h3>

<p>{translate key="manager.setup.securitySettingsDescription"}</p>

<script type="text/javascript">
{literal}
function toggleRegAllowOpts(form) {
	form.allowRegReader.disabled=!form.allowRegReader.disabled;
	form.allowRegAuthor.disabled=!form.allowRegAuthor.disabled;
	form.allowRegReviewer.disabled=!form.allowRegReviewer.disabled;
}
{/literal}
</script>

<h4>{translate key="manager.setup.userRegistration"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg[0]" value="0" onclick="toggleRegAllowOpts(this.form)"{if !$disableUserReg} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<label for="disableUserReg[0]">{translate key="manager.setup.enableUserRegistration"}</label>
			<table width="100%">
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReader" id="allowRegReader" value="1"{if $allowRegReader || $allowRegReader === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegReader">{translate key="user.role.readers"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegAuthor" id="allowRegAuthor" value="1"{if $allowRegAuthor || $allowRegAuthor === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegAuthor">{translate key="user.role.authors"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReviewer" id="allowRegReviewer" value="1"{if $allowRegReviewer || $allowRegReviewer === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegReviewer">{translate key="user.role.reviewers"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg[1]" value="1" onclick="toggleRegAllowOpts(this.form)"{if $disableUserReg} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="disableUserReg[1]">{translate key="manager.setup.disableUserRegistration"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.siteAccess"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictSiteAccess" id="restrictSiteAccess[0]" value="0"{if !$restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictSiteAccess[0]">{translate key="manager.setup.noRestrictSiteAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictSiteAccess" id="restrictSiteAccess[1]" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictSiteAccess[1]">{translate key="manager.setup.restrictSiteAccess"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.articleAccess"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictArticleAccess" id="restrictArticleAccess[0]" value="0"{if !$restrictArticleAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictArticleAccess[0]">{translate key="manager.setup.noRestrictArticleAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictArticleAccess" id="restrictArticleAccess[1]" value="1"{if $restrictArticleAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictArticleAccess[1]">{translate key="manager.setup.restrictArticleAccess"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.comments"}</h4>

<table width="100%" class="data">
{foreach from=$commentsOptions item=keyName key=value}
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="enableComments" id="enableComments[{$value}]" value="{$value}"{if $enableComments==$value} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enableComments[{$value}]">{translate key=$keyName}</label></td>
	</tr>
{/foreach}
</table>

<h4>{translate key="manager.setup.loggingAndAuditing"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="articleEventLog" id="articleEventLog" value="1"{if $articleEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="articleEventLog">{translate key="manager.setup.submissionEventLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="articleEmailLog" id="articleEmailLog" value="1"{if $articleEmailLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="articleEmailLog">{translate key="manager.setup.submissionEmailLogging"}</label></td>
	</tr>
</table>


<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager/setup'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
