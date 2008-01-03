{**
 * step4.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of journal setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.managingTheJournal"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="4"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="4"}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}

<h3>4.1 {translate key="manager.setup.securitySettings"}</h3>

<h4>{translate key="manager.setup.onlineAccessManagement"}</h4>
	<script type="text/javascript">
		{literal}
		<!--
			function toggleEnableSubscriptions(form) {
				if (form.enableSubscriptions[0].checked) {
					form.openAccessPolicy.disabled = false;
					form.showGalleyLinks.disabled = true;
				} else {
					form.openAccessPolicy.disabled = true;
					form.showGalleyLinks.disabled = false;
				}
			}
		// -->
		{/literal}
	</script>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="enableSubscriptions" id="enableSubscriptions-0" value="0" onclick="toggleEnableSubscriptions(this.form)"{if not $enableSubscriptions} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="enableSubscriptions-0">{translate key="manager.setup.openAccess"}</label>
			<h4>{translate key="manager.setup.openAccessPolicy"}</h4>
			<p><span class="instruct">{translate key="manager.setup.openAccessPolicyDescription"}</span></p>
			<p><textarea name="openAccessPolicy[{$formLocale|escape}]" id="openAccessPolicy" rows="12" cols="60" class="textArea"{if $enableSubscriptions} disabled="disabled"{/if}>{$openAccessPolicy[$formLocale]|escape}</textarea></p>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="enableSubscriptions" id="enableSubscriptions-1" value="1" onclick="toggleEnableSubscriptions(this.form)"{if $enableSubscriptions} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="enableSubscriptions-1">{translate key="manager.setup.subscription"}</label>
			<p><span class="instruct">{translate key="manager.setup.subscriptionDescription"}</span></p>
			<table width="100%">
				<tr>
					<td width="5%"><input type="checkbox" name="showGalleyLinks" id="showGalleyLinks" {if $showGalleyLinks} checked="checked"{/if} /></td>
					<td width="95%"><label for="showGalleyLinks">{translate key="manager.setup.showGalleyLinksDescription"}</label></td>
				</tr>	
			</table>
		</td>
	</tr>

</table>


<p>{translate key="manager.setup.securitySettingsDescription"}</p>

<script type="text/javascript">
{literal}
<!--
function setRegAllowOpts(form) {
	if(form.disableUserReg[0].checked) {
		form.allowRegReader.disabled=false;
		form.allowRegAuthor.disabled=false;
		form.allowRegReviewer.disabled=false;
	} else {
		form.allowRegReader.disabled=true;
		form.allowRegAuthor.disabled=true;
		form.allowRegReviewer.disabled=true;
	}
}
// -->
{/literal}
</script>

<h4>{translate key="manager.setup.siteAccess"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="restrictSiteAccess" id="restrictSiteAccess" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictSiteAccess">{translate key="manager.setup.restrictSiteAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="restrictArticleAccess" id="restrictArticleAccess" value="1"{if $restrictArticleAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictArticleAccess">{translate key="manager.setup.restrictArticleAccess"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.userRegistration"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg-0" value="0" onclick="setRegAllowOpts(this.form)"{if !$disableUserReg} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<label for="disableUserReg-0">{translate key="manager.setup.enableUserRegistration"}</label>
			<table width="100%">
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReader" id="allowRegReader" value="1"{if $allowRegReader || $allowRegReader === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegReader">{translate key="manager.setup.enableUserRegistration.reader"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegAuthor" id="allowRegAuthor" value="1"{if $allowRegAuthor || $allowRegAuthor === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegAuthor">{translate key="manager.setup.enableUserRegistration.author"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReviewer" id="allowRegReviewer" value="1"{if $allowRegReviewer || $allowRegReviewer === null} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td width="95%"><label for="allowRegReviewer">{translate key="manager.setup.enableUserRegistration.reviewer"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg-1" value="1" onclick="setRegAllowOpts(this.form)"{if $disableUserReg} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="disableUserReg-1">{translate key="manager.setup.disableUserRegistration"}</label></td>
	</tr>
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

<h3>4.2 {translate key="manager.setup.publicationScheduling"}</h3>

<h4>{translate key="manager.setup.publicationSchedule"}</h4>

<p>{translate key="manager.setup.publicationScheduleDescription"}</p>

<p><textarea name="pubFreqPolicy[{$formLocale|escape}]" rows="12" cols="60" class="textArea">{$pubFreqPolicy[$formLocale]|escape}</textarea></p>

<h4>{translate key="manager.setup.publicationFormat"}</h4>

<p>{translate key="manager.setup.publicationFormatDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatVolume" id="publicationFormatVolume" value="1"{if ($publicationFormatVolume)} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="publicationFormatVolume">{translate key="manager.setup.publicationFormatVolume"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatNumber" id="publicationFormatNumber" value="1"{if ($publicationFormatNumber)} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="publicationFormatNumber">{translate key="manager.setup.publicationFormatNumber"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatYear" id="publicationFormatYear" value="1"{if ($publicationFormatYear)} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="publicationFormatYear">{translate key="manager.setup.publicationFormatYear"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatTitle" id="publicationFormatTitle" value="1"{if ($publicationFormatTitle)} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<label for="publicationFormatTitle">{translate key="manager.setup.publicationFormatTitle"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.setup.frequencyOfPublication"}</h4>

<p>{translate key="manager.setup.frequencyOfPublicationDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="initialNumber" key="issue.number"}</td>
		<td width="80%" class="data"><input type="text" name="initialNumber" id="initialNumber" value="{$initialNumber|escape}" size="5" maxlength="8" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="initialVolume" key="issue.volume"}</td>
		<td width="80%" class="data"><input type="text" name="initialVolume" id="initialVolume" value="{$initialVolume|escape}" size="5" maxlength="8" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="initialYear" key="issue.year"}</td>
		<td width="80%" class="data"><input type="text" name="initialYear" id="initialYear" value="{$initialYear|escape}" size="5" maxlength="8" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="issuePerVolume" key="manager.setup.issuePerVolume"}</td>
		<td width="80%" class="data"><input type="text" name="issuePerVolume" id="issuePerVolume" value="{if $issuePerVolume}{$issuePerVolume|escape}{/if}" size="5" maxlength="8" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="volumePerYear" key="manager.setup.volumePerYear"}</td>
		<td width="80%" class="data"><input type="text" name="volumePerYear" id="volumePerYear" value="{if $volumePerYear}{$volumePerYear|escape}{/if}" size="5" maxlength="8" class="textField" /></td>
	</tr>
</table>

<p>{translate key="manager.setup.frequencyOfPublicationNote"}</p>


<div class="separator"></div>


<h3>4.3 {translate key="manager.setup.publicIdentifier"}</h3>

<h4>{translate key="manager.setup.uniqueIdentifier"}</h4>

<p>{translate key="manager.setup.uniqueIdentifierDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicIssueId" id="enablePublicIssueId" value="1"{if $enablePublicIssueId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicIssueId">{translate key="manager.setup.enablePublicIssueId"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicArticleId" id="enablePublicArticleId" value="1"{if $enablePublicArticleId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicArticleId">{translate key="manager.setup.enablePublicArticleId"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicSuppFileId">{translate key="manager.setup.enablePublicSuppFileId"}</label></td>
	</tr>
</table>

<br />

<h4>{translate key="manager.setup.pageNumberIdentifier"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePageNumber" id="enablePageNumber" value="1"{if $enablePageNumber} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePageNumber">{translate key="manager.setup.enablePageNumber"}</label></td>
	</tr>
</table>

<div class="separator"></div>


<h3>4.4 {translate key="manager.setup.announcements"}</h3>

<p>{translate key="manager.setup.announcementsDescription"}</p>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleEnableAnnouncementsHomepage(form) {
				form.numAnnouncementsHomepage.disabled = !form.numAnnouncementsHomepage.disabled;
			}
		// -->
		{/literal}
	</script>

<p>
	<input type="checkbox" name="enableAnnouncements" id="enableAnnouncements" value="1" {if $enableAnnouncements} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncements">{translate key="manager.setup.enableAnnouncements"}</label>
</p>

<p>
	<input type="checkbox" name="enableAnnouncementsHomepage" id="enableAnnouncementsHomepage" value="1" onclick="toggleEnableAnnouncementsHomepage(this.form)"{if $enableAnnouncementsHomepage} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncementsHomepage">{translate key="manager.setup.enableAnnouncementsHomepage1"}</label>
	<select name="numAnnouncementsHomepage" size="1" class="selectMenu" {if not $enableAnnouncementsHomepage}disabled="disabled"{/if}>
		{section name="numAnnouncementsHomepageOptions" start=1 loop=11}
		<option value="{$smarty.section.numAnnouncementsHomepageOptions.index}"{if $numAnnouncementsHomepage eq $smarty.section.numAnnouncementsHomepageOptions.index or ($smarty.section.numAnnouncementsHomepageOptions.index eq 1 and not $numAnnouncementsHomepage)} selected="selected"{/if}>{$smarty.section.numAnnouncementsHomepageOptions.index}</option>
		{/section}
	</select>
	{translate key="manager.setup.enableAnnouncementsHomepage2"}
</p>

<h4>{translate key="manager.setup.announcementsIntroduction"}</h4>

<p>{translate key="manager.setup.announcementsIntroductionDescription"}</p>

<p><textarea name="announcementsIntroduction[{$formLocale|escape}]" id="announcementsIntroduction" rows="12" cols="60" class="textArea">{$announcementsIntroduction[$formLocale]|escape}</textarea></p>


<div class="separator"></div>


<h3>4.5 {translate key="manager.setup.copyediting"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useCopyeditors" id="useCopyeditors-1" value="1"{if $useCopyeditors} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useCopyeditors-1">{translate key="manager.setup.useCopyeditors"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useCopyeditors" id="useCopyeditors-0" value="0"{if !$useCopyeditors} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useCopyeditors-0">{translate key="manager.setup.noUseCopyeditors"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.copyeditInstructions"}</h4>

<p>{translate key="manager.setup.copyeditInstructionsDescription"}</p>

<p>
	<textarea name="copyeditInstructions[{$formLocale|escape}]" id="copyeditInstructions" rows="12" cols="60" class="textArea">{$copyeditInstructions[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>


<div class="separator"></div>


<h3>4.6 {translate key="manager.setup.layoutAndGalleys"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useLayoutEditors" id="useLayoutEditors-1" value="1"{if $useLayoutEditors} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useLayoutEditors-1">{translate key="manager.setup.useLayoutEditors"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useLayoutEditors" id="useLayoutEditors-0" value="0"{if !$useLayoutEditors} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useLayoutEditors-0">{translate key="manager.setup.noUseLayoutEditors"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.layoutInstructions"}</h4>

<p>{translate key="manager.setup.layoutInstructionsDescription"}</p>

<p>
	<textarea name="layoutInstructions[{$formLocale|escape}]" id="layoutInstructions" rows="12" cols="60" class="textArea">{$layoutInstructions[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>

<h4>{translate key="manager.setup.layoutTemplates"}</h4>

<p>{translate key="manager.setup.layoutTemplatesDescription"}</p>

<table width="100%" class="data">
{foreach name=templates from=$templates key=templateId item=template}
	<tr valign="top">
		<td width="20%" class="label"><a href="{url op="downloadLayoutTemplate" path=$templateId}" class="action">{$template.filename|escape}</a></td>
		<td width="50%" class="value">{$template.title|escape}</td>
		<td width="30%"><input type="submit" name="delTemplate[{$templateId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
{/foreach}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="template-title" key="manager.setup.layoutTemplates.title"}</td>
		<td width="80%" colspan="2" class="value"><input type="text" name="template-title" id="template-title" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="template-file" key="manager.setup.layoutTemplates.file"}</td>
		<td width="80%" colspan="2" class="value"><input type="file" name="template-file" id="template-file" class="uploadField" /><input type="submit" name="addTemplate" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

<h4>{translate key="manager.setup.referenceLinking"}</h4>

{translate key="manager.setup.referenceLinkingDescription"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="provideRefLinkInstructions" id="provideRefLinkInstructions" value="1"{if $provideRefLinkInstructions} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="provideRefLinkInstructions">{translate key="manager.setup.provideRefLinkInstructions"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.refLinkInstructions.description"}</h4>
<textarea name="refLinkInstructions[{$formLocale|escape}]" id="refLinkInstructions" rows="12" cols="60" class="textArea">{$refLinkInstructions[$formLocale]|escape}</textarea>

<div class="separator"></div>


<h3>4.7 {translate key="manager.setup.proofreading"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useProofreaders" id="useProofreaders-1" value="1"{if $useProofreaders} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useProofreaders-1">{translate key="manager.setup.useProofreaders"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="useProofreaders" id="useProofreaders-0" value="0"{if !$useProofreaders} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="useProofreaders-0">{translate key="manager.setup.noUseProofreaders"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.proofingInstructions"}</h4>

<p>{translate key="manager.setup.proofingInstructionsDescription"}</p>

<p>
	<textarea name="proofInstructions[{$formLocale|escape}]" id="proofInstructions" rows="12" cols="60" class="textArea">{$proofInstructions[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
