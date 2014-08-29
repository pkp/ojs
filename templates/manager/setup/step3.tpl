{**
 * templates/manager/setup/step3.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of journal setup.
 *}
{assign var="pageTitle" value="manager.setup.guidingSubmissions"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" id="setupForm" method="post" action="{url op="saveSetup" path="3"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locale">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="3" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

<div id="authorGuidelinesInfo">
<h3>3.1 {translate key="manager.setup.authorGuidelines"}</h3>

<p>{translate key="manager.setup.authorGuidelinesDescription"}</p>

<p>
	<textarea name="authorGuidelines[{$formLocale|escape}]" id="authorGuidelines" rows="12" cols="60" class="textArea">{$authorGuidelines[$formLocale]|escape}</textarea>
</p>

</div>

<div id="submissionPreparationChecklist">
<h4>{translate key="manager.setup.submissionPreparationChecklist"}</h4>

<p>{translate key="manager.setup.submissionPreparationChecklistDescription"}</p>

{foreach name=checklist from=$submissionChecklist[$formLocale] key=checklistId item=checklistItem}
	{if !$notFirstChecklistItem}
		{assign var=notFirstChecklistItem value=1}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="5%">{translate key="common.order"}</td>
				<td width="95%" colspan="2">&nbsp;</td>
			</tr>
	{/if}

	<tr valign="top">
		<td width="5%" class="label"><input type="text" name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
		<td class="value"><textarea name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][content]" id="submissionChecklist-{$checklistId|escape}" rows="3" cols="40" class="textArea">{$checklistItem.content|escape}</textarea></td>
		<td width="100%"><input type="submit" name="delChecklist[{$checklistId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
	</tr>
{/foreach}

{if $notFirstChecklistItem}
	</table>
{/if}

<p><input type="submit" name="addChecklist" value="{translate key="manager.setup.addChecklistItem"}" class="button" /></p>
</div>

<div class="separator"></div>

<div id="permissions">
<h3>3.2 {translate key="submission.permissions"}</h3>

<h4>{translate key="manager.setup.authorCopyrightNotice"}</h4>
{url|assign:"sampleCopyrightWordingUrl" page="information" op="sampleCopyrightWording"}
<p>{translate key="manager.setup.authorCopyrightNoticeDescription" sampleCopyrightWordingUrl=$sampleCopyrightWordingUrl}</p>

<p><textarea name="copyrightNotice[{$formLocale|escape}]" id="copyrightNotice" rows="12" cols="60" class="textArea">{$copyrightNotice[$formLocale]|escape}</textarea></p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label" rowspan="3">
			{translate key="submission.copyrightHolder"}
		</td>
		<td width="80%" class="data">
			<input type="radio" value="author" name="copyrightHolderType" {if $copyrightHolderType=="author"}checked="checked" {/if}id="copyrightHolderType-author" />&nbsp;<label for="copyrightHolderType-author">{translate key="user.role.author"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="data">
			<input type="radio" value="journal" name="copyrightHolderType" {if $copyrightHolderType=="journal"}checked="checked" {/if}id="copyrightHolderType-journal" />&nbsp;<label for="copyrightHolderType-journal">{translate key="journal.journal"}</label> ({$currentJournal->getLocalizedTitle()|escape})
		</td>
	</tr>
	<tr valign="top">
		<td class="data">
			<input type="radio" value="other" name="copyrightHolderType" {if $copyrightHolderType=="other"}checked="checked" {/if}id="copyrightHolderType-other" />&nbsp;<label for="copyrightHolderType-other">{translate key="common.other"}</label>&nbsp;&nbsp;<input type="text" name="copyrightHolderOther[{$formLocale|escape}]" id="copyrightHolderOther" value="{$copyrightHolderOther[$formLocale]|escape}" />
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label" rowspan="2">
			{translate key="manager.setup.copyrightYearBasis"}
		</td>
		<td width="80%" class="data">
			<input type="radio" value="issue" name="copyrightYearBasis" {if $copyrightYearBasis=="issue"}checked="checked" {/if}id="copyrightYearBasis-issue" />&nbsp;<label for="copyrightYearBasis-issue">{translate key="issue.issue"}</label> ({translate key="manager.setup.copyrightYearBasis.Issue"})
		</td>
	</tr>
	<tr valign="top">
		<td class="data">
			<input type="radio" value="article" name="copyrightYearBasis" {if $copyrightYearBasis=="article"}checked="checked" {/if}id="copyrightYearBasis-article" />&nbsp;<label for="copyrightYearBasis-article">{translate key="article.article"}</label> ({translate key="manager.setup.copyrightYearBasis.Article"})
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="manager.setup.permissions.priorAgreement"}</td>
		<td class="label">
			<input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $copyrightNoticeAgree} checked="checked"{/if} />&nbsp;<label for="copyrightNoticeAgree">{translate key="manager.setup.authorCopyrightNoticeAgree"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="manager.setup.permissions.display"}</td>
		<td class="value">
			<input type="checkbox" name="includeCopyrightStatement" id="includeCopyrightStatement" value="1"{if $includeCopyrightStatement} checked="checked"{/if} />&nbsp;<label for="includeCopyrightStatement">{translate key="manager.setup.includeCopyrightStatement"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name=licenseURL key="submission.licenseURL"}</td>
		<td class="value">
			<select name="licenseURLSelect" id="licenseURLSelect" onchange="document.getElementById('licenseURL').value=document.getElementById('licenseURLSelect').options[document.getElementById('licenseURLSelect').selectedIndex].value; document.getElementById('licenseURL').readOnly=(document.getElementById('licenseURL').value==''?false:true);">
				{assign var=foundCc value=0}
				{foreach from=$ccLicenseOptions key=ccUrl item=ccNameKey}
					<option {if $licenseURL == $ccUrl}selected="selected" {/if}value="{$ccUrl|escape}">{$ccNameKey|translate}</option>
					{if $licenseURL == $ccUrl}
						{assign var=foundCc value=1}
					{/if}
				{/foreach}
				<option {if !$foundCc}selected="selected" {/if}value="">Other</option>
			</select>
			<br/>
			<input type="text" name="licenseURL" id="licenseURL" value="{$licenseURL|escape}" {if $foundCc}readonly="readonly" {/if}size="40" maxlength="255" class="textField" />
			<br/>
			{translate key="manager.setup.licenseURLDescription"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="manager.setup.permissions.display"}</td>
		<td class="value">
			<input type="checkbox" name="includeLicense" id="includeLicense" value="1"{if $includeLicense} checked="checked"{/if} />&nbsp;<label for="includeLicense">{translate key="manager.setup.includeLicense"}</label>
		</td>
	</tr>
</table>

<p>{translate key="manager.setup.resetPermissions.description"}</p>
<p><input id="resetPermissionsButton" type="button" value="{translate key="manager.setup.resetPermissions"}" class="button" /></p>
</div>

<div class="separator"></div>

<div id="competingInterests">
<h3>3.3 {translate key="manager.setup.competingInterests"}</h3>

<p>{translate key="manager.setup.competingInterests.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="5%">
			<input type="checkbox" name="requireAuthorCompetingInterests" id="requireAuthorCompetingInterests" value="1"{if $requireAuthorCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="requireAuthorCompetingInterests">{translate key="manager.setup.competingInterests.requireAuthors"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="checkbox" name="requireReviewerCompetingInterests" id="requireReviewerCompetingInterests" value="1"{if $requireReviewerCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="requireReviewerCompetingInterests">{translate key="manager.setup.competingInterests.requireReviewers"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.setup.competingInterests.guidelines"}</h4>
<p><textarea name="competingInterestGuidelines[{$formLocale|escape}]" id="competingInterestGuidelines" rows="12" cols="60" class="textArea">{$competingInterestGuidelines[$formLocale]|escape}</textarea></p>
</div>

<div class="separator"></div>

<div id="forAuthorsToIndexTheirWork">
<h3>3.4 {translate key="manager.setup.forAuthorsToIndexTheirWork"}</h3>

<p>{translate key="manager.setup.forAuthorsToIndexTheirWorkDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaDiscipline" id="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaDiscipline" key="manager.setup.discipline"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.disciplineDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.disciplineProvideExamples"}:</span>
			<br />
			<input type="text" name="metaDisciplineExamples[{$formLocale|escape}]" id="metaDisciplineExamples" value="{$metaDisciplineExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.disciplineExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubjectClass" id="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaSubjectClass" key="manager.setup.subjectClassification"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<table width="100%">
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassTitle" key="common.title"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassTitle[{$formLocale|escape}]" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle[$formLocale]|escape}" size="40" class="textField" /></td>
				</tr>
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassUrl" key="common.url"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassUrl[{$formLocale|escape}]" id="metaSubjectClassUrl" value="{$metaSubjectClassUrl[$formLocale]|escape}" size="40" class="textField" /></td>
				</tr>
			</table>
			<span class="instruct">{translate key="manager.setup.subjectClassificationExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubject" id="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaSubject" key="manager.setup.subjectKeywordTopic"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.subjectProvideExamples"}:</span>
			<br />
			<input type="text" name="metaSubjectExamples[{$formLocale|escape}]" id="metaSubjectExamples" value="{$metaSubjectExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.subjectExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaCoverage" id="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaCoverage" key="manager.setup.coverage"}</h4>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.coverageGeoProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageGeoExamples[{$formLocale|escape}]" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageGeoExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageChronProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageChronExamples[{$formLocale|escape}]" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageChronExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageResearchSampleExamples[{$formLocale|escape}]" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleExamples"}</span>
		</td>
	</tr>

	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>

	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaType" id="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaType" key="manager.setup.typeMethodApproach"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.typeProvideExamples"}:</span>
			<br />
			<input type="text" name="metaTypeExamples[{$formLocale|escape}]" id="metaTypeExamples" value="{$metaTypeExamples[$formLocale]|escape}" size="60" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.typeExamples"}</span>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="registerJournalForIndexing">
<h3>3.5 {translate key="manager.setup.registerJournalForIndexing"}</h3>

{url|assign:"oaiUrl" page="oai"}
<p>{translate key="manager.setup.registerJournalForIndexingDescription" oaiUrl=$oaiUrl siteUrl=$baseUrl}</p>
</div>

<div class="separator"></div>

<div id="notifications">
<h3>3.6 {translate key="manager.setup.notifications"}</h3>

<p>{translate key="manager.setup.notifications.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="manager.setup.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="manager.setup.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" id="copySubmissionAckAddress" name="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr valign="top">
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" op="emails"}
		<td>{translate key="manager.setup.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>
</div>

<div class="separator"></div>

<div id="citationAssistant">
<h3>3.7 {translate key="manager.setup.citationAssistant"}</h3>

<a name="metaCitationEditing"></a>
{if $citationEditorError}
	<p>{translate key=$citationEditorError}</p>
{else}
	<p>{translate key="manager.setup.metaCitationsDescription"}</p>
	<table width="100%" class="data">
		<tr valign="top">
			<td width="5%" class="label">
				<input type="checkbox" name="metaCitations" id="metaCitations" value="1"{if $metaCitations} checked="checked"{/if} />
			</td>
			<td width="95%" class="value"><label for="metaCitations">{translate key="manager.setup.citations"}</label>
			</td>
		</tr>
	</table>

	<div id="citationFilterSetupToggle" {if !$metaCitations}style="visible: none"{/if}>
		<h4>{translate key="manager.setup.citationFilterParser"}</h4>
		<p>{translate key="manager.setup.citationFilterParserDescription"}</p>
		{load_url_in_div id="parserFilterGridContainer" loadMessageId="manager.setup.filter.parser.grid.loadMessage" url="$parserFilterGridUrl"}

		<h4>{translate key="manager.setup.citationFilterLookup"}</h4>
		<p>{translate key="manager.setup.citationFilterLookupDescription"}</p>
		{load_url_in_div id="lookupFilterGridContainer" loadMessageId="manager.setup.filter.lookup.grid.loadMessage" url="$lookupFilterGridUrl"}
		<h4>{translate key="manager.setup.citationOutput"}</h4>
		<p>{translate key="manager.setup.citationOutputStyleDescription"}</p>
		{fbvElement type="select" id="metaCitationOutputFilterSelect" name="metaCitationOutputFilterId"
				from=$metaCitationOutputFilters translate=false selected=$metaCitationOutputFilterId|escape
				defaultValue="-1" defaultLabel="manager.setup.filter.pleaseSelect"|translate}
	</div>
	{literal}<script type='text/javascript'>
		$(function(){
			// jQuerify DOM elements
			$metaCitationsCheckbox = $('#metaCitations');
			$metaCitationsSetupBox = $('#citationFilterSetupToggle');

			// Set the initial state
			initialCheckboxState = $metaCitationsCheckbox.attr('checked');
			if (initialCheckboxState) {
				$metaCitationsSetupBox.css('display', 'block');
			} else {
				$metaCitationsSetupBox.css('display', 'none');
			}

			// Toggle the settings box.
			// NB: Has to be click() rather than change() to work in IE.
			$metaCitationsCheckbox.click(function(){
				checkboxState = $metaCitationsCheckbox.attr('checked');
				toggleState = ($metaCitationsSetupBox.css('display') === 'block');
				if (checkboxState !== toggleState) {
					$metaCitationsSetupBox.toggle(300);
				}
			});
		});
		
		function permissionsValues() {
			perm = ':';
			licenseNames = ["copyrightYearBasis", "copyrightHolderType"];
			for (l = 0; l < licenseNames.length; l++) {
				ele = document.getElementsByName(licenseNames[l]);
				for (i = 0; i < ele.length; i++) {
					if (ele[i].type == 'radio') {
						if (ele[i].checked) {
							perm += ele[i].value + ':';
							break;
						}
					} else {
						perm += ele[i].value + ':';
					}
				}
			};
			licenseIds = ["copyrightHolderOther", "licenseURL"];
			for (l = 0; l < licenseIds.length; l++) {
				ele = document.getElementById(licenseIds[l]);
				if (ele) {
					perm += ele.value + ':';
				}
			}
			return perm;
		}
		var resetValues;
		$(document).ready( function () {
			resetValues = permissionsValues();
			$('#resetPermissionsButton').click( function ( event ) {
				if (resetValues == permissionsValues()) {
					{/literal}
					confirmAction('{url op="resetPermissions"}', '{translate|escape:"jsparam" key="manager.setup.confirmResetLicense"}');
					{literal}
				} else {
					{/literal}
					window.alert('{translate|escape:"jsparam" key="manager.setup.confirmResetLicenseChanged"}')
					{literal}
				}
				event.preventDefault();
			});
		});
	</script>{/literal}
{/if}
</div>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
