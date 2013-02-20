{**
 * templates/controllers/tab/settings/management/form/managementForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of journal setup.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#managementSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="managementSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="management"}" enctype="multipart/form-data">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="managementFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

<div id="securitySettings">
<h3>4.1 {translate key="manager.setup.securitySettings"}</h3>
<div id="onlineAccessManagement">
<h4>{translate key="manager.setup.onlineAccessManagement"}</h4>
<script>
	{literal}
		function togglePublishingMode(form) {
			if (form.publishingMode[0].checked) {
				// PUBLISHING_MODE_OPEN
				form.showGalleyLinks.disabled = true;
			} else if (form.publishingMode[1].checked) {
				// PUBLISHING_MODE_SUBSCRIPTION
				form.showGalleyLinks.disabled = false;
			} else {
				// PUBLISHING_MODE_NONE
				form.showGalleyLinks.disabled = true;
			}
		}
	{/literal}
</script>

<table class="data">
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="publishingMode" id="publishingMode-0" value="{$smarty.const.PUBLISHING_MODE_OPEN}" onclick="togglePublishingMode(this.form)"{if $publishingMode == $smarty.const.PUBLISHING_MODE_OPEN} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="publishingMode-0">{translate key="manager.setup.openAccess"}</label>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="publishingMode" id="publishingMode-1" value="{$smarty.const.PUBLISHING_MODE_SUBSCRIPTION}" onclick="togglePublishingMode(this.form)"{if $publishingMode == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="publishingMode-1">{translate key="manager.setup.subscription"}</label>
			<p><span class="instruct">{translate key="manager.setup.subscriptionDescription"}</span></p>
			<table>
				<tr>
					<td width="5%"><input type="checkbox" name="showGalleyLinks" id="showGalleyLinks" {if $showGalleyLinks} checked="checked"{/if} /></td>
					<td><label for="showGalleyLinks">{translate key="manager.setup.showGalleyLinksDescription"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="publishingMode" id="publishingMode-2" value="{$smarty.const.PUBLISHING_MODE_NONE}" onclick="togglePublishingMode(this.form)"{if $publishingMode == $smarty.const.PUBLISHING_MODE_NONE} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="publishingMode-2">{translate key="manager.setup.noPublishing"}</label>
		</td>
	</tr>
</table>

<p>{translate key="manager.setup.securitySettingsDescription"}</p>
</div><!-- onlineAccessManagement -->

<script>
{literal}
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
{/literal}
</script>

<div id="siteAccess">
<h4>{translate key="manager.setup.siteAccess"}</h4>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="restrictSiteAccess" id="restrictSiteAccess" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td class="value"><label for="restrictSiteAccess">{translate key="manager.setup.restrictSiteAccess"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="restrictArticleAccess" id="restrictArticleAccess" value="1"{if $restrictArticleAccess} checked="checked"{/if} /></td>
		<td class="value"><label for="restrictArticleAccess">{translate key="manager.setup.restrictArticleAccess"}</label></td>
	</tr>
</table>
</div><!-- siteAccess -->

<div id="userRegistration">
<h4>{translate key="manager.setup.userRegistration"}</h4>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg-0" value="0" onclick="setRegAllowOpts(this.form)"{if !$disableUserReg} checked="checked"{/if} /></td>
		<td class="value">
			<label for="disableUserReg-0">{translate key="manager.setup.enableUserRegistration"}</label>
			<table>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReader" id="allowRegReader" value="1"{if $allowRegReader} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td><label for="allowRegReader">{translate key="manager.setup.enableUserRegistration.reader"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegAuthor" id="allowRegAuthor" value="1"{if $allowRegAuthor} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td><label for="allowRegAuthor">{translate key="manager.setup.enableUserRegistration.author"}</label></td>
				</tr>
				<tr>
					<td width="5%"><input type="checkbox" name="allowRegReviewer" id="allowRegReviewer" value="1"{if $allowRegReviewer} checked="checked"{/if}{if $disableUserReg} disabled="disabled"{/if} /></td>
					<td><label for="allowRegReviewer">{translate key="manager.setup.enableUserRegistration.reviewer"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="radio" name="disableUserReg" id="disableUserReg-1" value="1" onclick="setRegAllowOpts(this.form)"{if $disableUserReg} checked="checked"{/if} /></td>
		<td class="value"><label for="disableUserReg-1">{translate key="manager.setup.disableUserRegistration"}</label></td>
	</tr>
</table>
</div><!-- userRegistration -->

</div><!-- securitySettings -->

<div class="separator"></div>

<div id="publicationScheduling">
<h3>4.2 {translate key="manager.setup.publicationScheduling"}</h3>
<div id="publicationSchedule">
<h4>{translate key="manager.setup.publicationSchedule"}</h4>

<p>{translate key="manager.setup.publicationScheduleDescription"}</p>

<p><textarea name="pubFreqPolicy[{$formLocale|escape}]" id="pubFreqPolicy" rows="12" cols="60" class="textArea richContent">{$pubFreqPolicy[$formLocale]|escape}</textarea></p>
</div>
<div id="publicationFormat">
<h4>{translate key="manager.setup.publicationFormat"}</h4>

<p>{translate key="manager.setup.publicationFormatDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatVolume" id="publicationFormatVolume" value="1"{if ($publicationFormatVolume)} checked="checked"{/if} /></td>
		<td class="value"><label for="publicationFormatVolume">{translate key="manager.setup.publicationFormatVolume"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatNumber" id="publicationFormatNumber" value="1"{if ($publicationFormatNumber)} checked="checked"{/if} /></td>
		<td class="value"><label for="publicationFormatNumber">{translate key="manager.setup.publicationFormatNumber"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatYear" id="publicationFormatYear" value="1"{if ($publicationFormatYear)} checked="checked"{/if} /></td>
		<td class="value"><label for="publicationFormatYear">{translate key="manager.setup.publicationFormatYear"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="publicationFormatTitle" id="publicationFormatTitle" value="1"{if ($publicationFormatTitle)} checked="checked"{/if} /></td>
		<td class="value">
			<label for="publicationFormatTitle">{translate key="manager.setup.publicationFormatTitle"}</label>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="publicIdentifier">
<h3>4.3 {translate key="manager.setup.publicIdentifier"}</h3>
<div id="uniqueIdentifier">
<h4>{translate key="manager.setup.uniqueIdentifier"}</h4>

<p>{translate key="manager.setup.uniqueIdentifierDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="enablePublicIssueId" id="enablePublicIssueId" value="1"{if $enablePublicIssueId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicIssueId">{translate key="manager.setup.enablePublicIssueId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicArticleId" id="enablePublicArticleId" value="1"{if $enablePublicArticleId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicArticleId">{translate key="manager.setup.enablePublicArticleId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicGalleyId" id="enablePublicGalleyId" value="1"{if $enablePublicGalleyId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicGalleyId">{translate key="manager.setup.enablePublicGalleyId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicSuppFileId">{translate key="manager.setup.enablePublicSuppFileId"}</label></td>
	</tr>
</table>
</div><!-- uniqueIdentifier -->
<br />
<div id="pageNumberIdentifier">
<h4>{translate key="manager.setup.pageNumberIdentifier"}</h4>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="enablePageNumber" id="enablePageNumber" value="1"{if $enablePageNumber} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePageNumber">{translate key="manager.setup.enablePageNumber"}</label></td>
	</tr>
</table>
</div><!-- pageNumberIdentifier -->
</div><!-- publicIdentifier -->
<div class="separator"></div>

<div id="copyediting">
<h3>4.5 {translate key="manager.setup.copyediting"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="radio" name="useCopyeditors" id="useCopyeditors-1" value="1"{if $useCopyeditors} checked="checked"{/if} /></td>
		<td class="value"><label for="useCopyeditors-1">{translate key="manager.setup.useCopyeditors"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="radio" name="useCopyeditors" id="useCopyeditors-0" value="0"{if !$useCopyeditors} checked="checked"{/if} /></td>
		<td class="value"><label for="useCopyeditors-0">{translate key="manager.setup.noUseCopyeditors"}</label></td>
	</tr>
</table>
</div><!-- copyediting -->

<div id="copyeditInstructionsSection">
<h4>{translate key="manager.setup.copyeditInstructions"}</h4>

<p>{translate key="manager.setup.copyeditInstructionsDescription"}</p>

<p>
	<textarea name="copyeditInstructions[{$formLocale|escape}]" id="copyeditInstructions" rows="12" cols="60" class="textArea richContent">{$copyeditInstructions[$formLocale]|escape}</textarea>
</p>
</div><!-- copyeditInstructionsSection -->

<div class="separator"></div>

<div id="layoutAndGalleys">
<h3>4.6 {translate key="manager.setup.layoutAndGalleys"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="radio" name="useLayoutEditors" id="useLayoutEditors-1" value="1"{if $useLayoutEditors} checked="checked"{/if} /></td>
		<td class="value"><label for="useLayoutEditors-1">{translate key="manager.setup.useLayoutEditors"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="radio" name="useLayoutEditors" id="useLayoutEditors-0" value="0"{if !$useLayoutEditors} checked="checked"{/if} /></td>
		<td class="value"><label for="useLayoutEditors-0">{translate key="manager.setup.noUseLayoutEditors"}</label></td>
	</tr>
</table>

<div id="layoutInstructionsSection">
<h4>{translate key="manager.setup.layoutInstructions"}</h4>

<p>{translate key="manager.setup.layoutInstructionsDescription"}</p>

<p>
	<textarea name="layoutInstructions[{$formLocale|escape}]" id="layoutInstructions" rows="12" cols="60" class="textArea richContent">{$layoutInstructions[$formLocale]|escape}</textarea>
</p>
</div><!-- layoutInstructionsSection -->

<div id="layoutTemplates">
<h4>{translate key="manager.setup.layoutTemplates"}</h4>

<p>{translate key="manager.setup.layoutTemplatesDescription"}</p>

<table class="data">
{foreach name=templates from=$templates key=templateId item=template}
	<tr>
		<td class="label"><a href="{url router=$smarty.const.ROUTE_PAGE op="downloadLayoutTemplate" path=$templateId}" class="action">{$template.filename|escape}</a></td>
		<td class="value">{$template.title|escape}</td>
		<td><input type="submit" name="delTemplate[{$templateId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
{/foreach}
	<tr>
		<td class="label">{fieldLabel name="template-title" key="manager.setup.layoutTemplates.title"}</td>
		<td colspan="2" class="value"><input type="text" name="template-title" id="template-title" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="template-file" key="manager.setup.layoutTemplates.file"}</td>
		<td colspan="2" class="value"><input type="file" name="template-file" id="template-file" class="uploadField" /><input type="submit" name="addTemplate" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>
</div><!-- layoutTemplates -->

<div id="referenceLinking">
<h4>{translate key="manager.setup.referenceLinking"}</h4>

{translate key="manager.setup.referenceLinkingDescription"}

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="provideRefLinkInstructions" id="provideRefLinkInstructions" value="1"{if $provideRefLinkInstructions} checked="checked"{/if} /></td>
		<td class="value"><label for="provideRefLinkInstructions">{translate key="manager.setup.provideRefLinkInstructions"}</label></td>
	</tr>
</table>
</div><!-- referenceLinking -->

<div id="refLinkInstructionsSection">
<h4>{translate key="manager.setup.refLinkInstructions.description"}</h4>
<textarea name="refLinkInstructions[{$formLocale|escape}]" id="refLinkInstructions" rows="12" cols="60" class="textArea richContent">{$refLinkInstructions[$formLocale]|escape}</textarea>
</div><!-- refLinkInstructionsSection -->
</div>
<div class="separator"></div>

<div id="proofreading">
<h3>4.7 {translate key="manager.setup.proofreading"}</h3>

<p>{translate key="manager.setup.selectOne"}:</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="radio" name="useProofreaders" id="useProofreaders-1" value="1"{if $useProofreaders} checked="checked"{/if} /></td>
		<td class="value"><label for="useProofreaders-1">{translate key="manager.setup.useProofreaders"}</label></td>
	</tr>
	<tr>
		<td width="5%" class="label"><input type="radio" name="useProofreaders" id="useProofreaders-0" value="0"{if !$useProofreaders} checked="checked"{/if} /></td>
		<td class="value"><label for="useProofreaders-0">{translate key="manager.setup.noUseProofreaders"}</label></td>
	</tr>
</table>
<div id="proofingInstructions">
<h4>{translate key="manager.setup.proofingInstructions"}</h4>

<p>{translate key="manager.setup.proofingInstructionsDescription"}</p>

<p>
	<textarea name="proofInstructions[{$formLocale|escape}]" id="proofInstructions" rows="12" cols="60" class="textArea richContent">{$proofInstructions[$formLocale]|escape}</textarea>
</p>
</div>
</div>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
