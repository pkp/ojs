{**
 * controllers/tab/settings/policies/form/policiesForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Policies management form (extends the built-in one).
 *
 *}
{capture assign="additionalFormContent"}
	{fbvFormSection list=true}
		{fbvElement type="checkbox" id="requireAuthorCompetingInterests" name="requireAuthorCompetingInterests" checked=$requireAuthorCompetingInterests label="manager.setup.competingInterests.requireAuthors" inline=true}
		{fbvElement type="checkbox" id="requireReviewerCompetingInterests" name="requireReviewerCompetingInterests" checked=$requireReviewerCompetingInterests label="manager.setup.competingInterests.requireReviewers" inline=true}
	{/fbvFormSection}

	{fbvFormSection label="manager.setup.publicationScheduling" description="manager.setup.publicationScheduleDescription"}
		{fbvElement type="textarea" multilingual=true name="pubFreqPolicy" id="pubFreqPolicy" value=$pubFreqPolicy rich=true}
	{/fbvFormSection}
{/capture}
{include file="core:controllers/tab/settings/policies/form/policiesForm.tpl additionalFormContent=$additionalFormContent}
