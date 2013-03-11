{**
 * templates/controllers/tab/settings/submissions/form/submissionsForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
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

<div id="authorCopyrightNotice">
<h3>3.2 {translate key="manager.setup.authorCopyrightNotice"}</h3>

<table class="data">
	<tr>
		<td class="label">
			<input type="checkbox" name="includeCreativeCommons" id="includeCreativeCommons" value="1"{if $includeCreativeCommons} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="includeCreativeCommons">{translate key="manager.setup.includeCreativeCommons"}</label>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>

<div id="competingInterests">
<h3>3.3 {translate key="manager.setup.competingInterests"}</h3>

<p>{translate key="manager.setup.competingInterests.description"}</p>

<table class="data">
	<tr>
		<td class="label" width="5%">
			<input type="checkbox" name="requireAuthorCompetingInterests" id="requireAuthorCompetingInterests" value="1"{if $requireAuthorCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="requireAuthorCompetingInterests">{translate key="manager.setup.competingInterests.requireAuthors"}</label>
		</td>
	</tr>
	<tr>
		<td class="label">
			<input type="checkbox" name="requireReviewerCompetingInterests" id="requireReviewerCompetingInterests" value="1"{if $requireReviewerCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="requireReviewerCompetingInterests">{translate key="manager.setup.competingInterests.requireReviewers"}</label>
		</td>
	</tr>
</table>

<div class="separator"></div>

<div id="forAuthorsToIndexTheirWork">
<h3>3.4 {translate key="manager.setup.forAuthorsToIndexTheirWork"}</h3>

<p>{translate key="manager.setup.forAuthorsToIndexTheirWorkDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaDiscipline" id="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
		<td class="value">
			<h4>{fieldLabel name="metaDiscipline" key="manager.setup.discipline"}</h4>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.disciplineDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.disciplineProvideExamples"}:</span>
			<br />
			<input type="text" name="metaDisciplineExamples[{$formLocale|escape}]" id="metaDisciplineExamples" value="{$metaDisciplineExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.disciplineExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr>
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubjectClass" id="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
		<td class="value">
			<h4>{fieldLabel name="metaSubjectClass" key="manager.setup.subjectClassification"}</h4>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<table>
				<tr>
					<td>{fieldLabel name="metaSubjectClassTitle" key="common.title"}</td>
					<td><input type="text" name="metaSubjectClassTitle[{$formLocale|escape}]" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
				</tr>
				<tr>
					<td>{fieldLabel name="metaSubjectClassUrl" key="common.url"}</td>
					<td><input type="text" name="metaSubjectClassUrl[{$formLocale|escape}]" id="metaSubjectClassUrl" value="{$metaSubjectClassUrl[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
				</tr>
			</table>
			<span class="instruct">{translate key="manager.setup.subjectClassificationExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr>
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubject" id="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
		<td class="value">
			<h4>{fieldLabel name="metaSubject" key="manager.setup.subjectKeywordTopic"}</h4>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.subjectProvideExamples"}:</span>
			<br />
			<input type="text" name="metaSubjectExamples[{$formLocale|escape}]" id="metaSubjectExamples" value="{$metaSubjectExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.subjectExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr>
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaCoverage" id="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
		<td class="value">
			<h4>{fieldLabel name="metaCoverage" key="manager.setup.coverage"}</h4>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.coverageGeoProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageGeoExamples[{$formLocale|escape}]" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageGeoExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageChronProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageChronExamples[{$formLocale|escape}]" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageChronExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageResearchSampleExamples[{$formLocale|escape}]" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr>
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaType" id="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
		<td class="value">
			<h4>{fieldLabel name="metaType" key="manager.setup.typeMethodApproach"}</h4>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.typeProvideExamples"}:</span>
			<br />
			<input type="text" name="metaTypeExamples[{$formLocale|escape}]" id="metaTypeExamples" value="{$metaTypeExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.typeExamples"}</span>
		</td>
	</tr>
</table>
</div>

<div id="securitySettings">
<h3>4.1 {translate key="manager.setup.securitySettings"}</h3>
<div id="onlineAccessManagement">
<h4>{translate key="manager.setup.onlineAccessManagement"}</h4>

<table class="data">
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="publishingMode" id="publishingMode-0" value="{$smarty.const.PUBLISHING_MODE_OPEN}"{if $publishingMode == $smarty.const.PUBLISHING_MODE_OPEN} checked="checked"{/if} />
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
			<input type="radio" name="publishingMode" id="publishingMode-1" value="{$smarty.const.PUBLISHING_MODE_SUBSCRIPTION}"{if $publishingMode == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="publishingMode-1">{translate key="manager.setup.subscription"}</label>
			<p><span class="instruct">{translate key="manager.setup.subscriptionDescription"}</span></p>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="publishingMode" id="publishingMode-2" value="{$smarty.const.PUBLISHING_MODE_NONE}"{if $publishingMode == $smarty.const.PUBLISHING_MODE_NONE} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="publishingMode-2">{translate key="manager.setup.noPublishing"}</label>
		</td>
	</tr>
</table>

<p>{translate key="manager.setup.securitySettingsDescription"}</p>
</div><!-- onlineAccessManagement -->

</div><!-- securitySettings -->

<div class="separator"></div>

<div id="publicationScheduling">
<h3>4.2 {translate key="manager.setup.publicationScheduling"}</h3>
<div id="publicationSchedule">
<h4>{translate key="manager.setup.publicationSchedule"}</h4>

<p>{translate key="manager.setup.publicationScheduleDescription"}</p>

<p><textarea name="pubFreqPolicy[{$formLocale|escape}]" id="pubFreqPolicy" rows="12" cols="60" class="textArea richContent">{$pubFreqPolicy[$formLocale]|escape}</textarea></p>
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

<div id="copyeditInstructionsSection">
<h4>{translate key="manager.setup.copyeditInstructions"}</h4>

<p>{translate key="manager.setup.copyeditInstructionsDescription"}</p>

<p>
	<textarea name="copyeditInstructions[{$formLocale|escape}]" id="copyeditInstructions" rows="12" cols="60" class="textArea richContent">{$copyeditInstructions[$formLocale]|escape}</textarea>
</p>
</div><!-- copyeditInstructionsSection -->

<div class="separator"></div>

<div id="layoutInstructionsSection">
<h4>{translate key="manager.setup.layoutInstructions"}</h4>

<p>{translate key="manager.setup.layoutInstructionsDescription"}</p>

<p>
	<textarea name="layoutInstructions[{$formLocale|escape}]" id="layoutInstructions" rows="12" cols="60" class="textArea richContent">{$layoutInstructions[$formLocale]|escape}</textarea>
</p>
</div><!-- layoutInstructionsSection -->

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

<div id="proofingInstructions">
<h4>{translate key="manager.setup.proofingInstructions"}</h4>

<p>{translate key="manager.setup.proofingInstructionsDescription"}</p>

<p>
	<textarea name="proofInstructions[{$formLocale|escape}]" id="proofInstructions" rows="12" cols="60" class="textArea richContent">{$proofInstructions[$formLocale]|escape}</textarea>
</p>
</div>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
