{**
 * templates/manager/setup/step3.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of journal setup.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#policySettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="policySettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="policies"}">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="policiesFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

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

<div class="separator"></div>

<div id="registerJournalForIndexing">
<h3>3.5 {translate key="manager.setup.registerJournalForIndexing"}</h3>

{url|assign:"oaiUrl" router=$smarty.const.ROUTE_PAGE page="oai"}
<p>{translate key="manager.setup.registerJournalForIndexingDescription" oaiUrl=$oaiUrl siteUrl=$baseUrl}</p>
</div>

<div class="separator"></div>

<div id="notifications">
<h3>3.6 {translate key="manager.setup.notifications"}</h3>

<p>{translate key="manager.setup.notifications.description"}</p>

<table class="data">
	<tr>
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="manager.setup.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr>
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="manager.setup.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" id="copySubmissionAckAddress" name="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr>
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" router=$smarty.const.ROUTE_PAGE op="emails"}
		<td>{translate key="manager.setup.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>
</div>

<div class="separator"></div>

<div id="citationAssistant">
<h3>3.7 {translate key="manager.setup.citationAssistant"}</h3>

<a name="metaCitationEditing"></a>
	<p>{translate key="manager.setup.metaCitationsDescription"}</p>
	<table class="data">
		<tr>
			<td width="5%" class="label">
				<input type="checkbox" name="metaCitations" id="metaCitations" value="1"{if $metaCitations} checked="checked"{/if} />
			</td>
			<td class="value"><label for="metaCitations">{translate key="manager.setup.citations"}</label>
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
	</script>{/literal}
</div>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
