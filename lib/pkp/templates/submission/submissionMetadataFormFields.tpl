{**
 * templates/submission/submissionMetadataFormFields.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission's metadata form fields. To be included in any form that wants to handle
 * submission metadata.
 *}
{if $coverageEnabled || $typeEnabled || $sourceEnabled || $rightsEnabled ||
		$languagesEnabled || $subjectEnabled || $keywordsEnabled || $agenciesEnabled || $referencesEnabled}
	{fbvFormSection title="submission.metadata"}
		<p class="description">{translate key="submission.metadataDescription"}</p>
	{/fbvFormSection}
{/if}
{if $coverageEnabled || $typeEnabled || $sourceEnabled || $rightsEnabled}
	{fbvFormArea id="additionalDublinCore"}
		{if $coverageEnabled}
			{fbvFormSection title="submission.coverage" for="coverage"}
				{fbvElement type="text" multilingual=true name="coverage" id="coverage" value=$coverage maxlength="255" readonly=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $typeEnabled}
			{fbvFormSection for="type" title="common.type"}
				{fbvElement type="text" label="submission.type.tip" multilingual=true name="type" id="type" value=$type maxlength="255" readonly=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $sourceEnabled}
			{fbvFormSection label="submission.source" for="source"}
				{fbvElement type="text" label="submission.source.tip" multilingual=true name="source" id="source" value=$source maxlength="255" readonly=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $rightsEnabled}
			{fbvFormSection label="submission.rights" for="rights"}
				{fbvElement type="text" label="submission.rights.tip" multilingual=true name="rights" id="rights" value=$rights maxlength="255" readonly=$readOnly}
			{/fbvFormSection}
		{/if}
	{/fbvFormArea}
{/if}

{if $languagesEnabled || $subjectEnabled || $keywordsEnabled || $agenciesEnabled || $referencesEnabled || $disciplinesEnabled}
	{fbvFormArea id="tagitFields" title="submission.submit.metadataForm"}
		{if $languagesEnabled}
			{$languagesField}
		{/if}
		{if $subjectEnabled}
			{fbvFormSection label="common.subjects"}
				{fbvElement type="keyword" id="subjects" multilingual=true current=$subjects disabled=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $disciplinesEnabled}
			{fbvFormSection label="search.discipline"}
				{fbvElement type="keyword" id="disciplines" multilingual=true current=$disciplines disabled=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $keywordsEnabled}
			{fbvFormSection label="common.keywords"}
				{fbvElement type="keyword" id="keywords" multilingual=true current=$keywords disabled=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $agenciesEnabled}
			{fbvFormSection label="submission.supportingAgencies"}
				{fbvElement type="keyword" id="agencies" multilingual=true current=$agencies disabled=$readOnly}
			{/fbvFormSection}
		{/if}
		{if $referencesEnabled}
			{fbvFormSection label="submission.citations"}
				{fbvElement type="textarea" id="citations" value=$citations disabled=$readOnly}
			{/fbvFormSection}
		{/if}
	{/fbvFormArea}
{/if}

{call_hook name="Templates::Submission::SubmissionMetadataForm::AdditionalMetadata"}
