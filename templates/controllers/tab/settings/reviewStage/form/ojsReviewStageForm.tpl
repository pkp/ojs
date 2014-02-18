{**
 * controllers/tab/settings/reviewStage/form/ojsReviewStageForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review stage management form.
 *
 *}
{capture assign="additionalReviewFormContents"}
	{* Extend the basic review stage form with additional settings for OJS *}

	{fbvFormArea id="reviewProcess" title="manager.setup.reviewProcess" class="border"}
		<p>{translate key="manager.setup.reviewProcessDescription"}</p>

		{fbvFormSection title="manager.setup.reviewProcessStandard" list=true}
			{fbvElement type="radio" id="mailSubmissionsToReviewers-0" name="mailSubmissionsToReviewers" value="0" checked=$mailSubmissionsToReviewers|compare:false label="manager.setup.reviewProcessStandardDescription"}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.reviewProcessEmail" list=true}
			{fbvElement type="radio" id="mailSubmissionsToReviewers-1" name="mailSubmissionsToReviewers" value="1" checked=$mailSubmissionsToReviewers|compare:true label="manager.setup.reviewProcessEmailDescription"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="reviewOptions" title="manager.setup.reviewOptions" class="border"}
		<p>{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</p>
		{fbvFormSection title="manager.setup.reviewOptions.reviewerAccess" list=true}
			{fbvElement type="checkbox" id="reviewerAccessKeysEnabled" checked=$reviewerAccessKeysEnabled label="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}
			{fbvElement type="checkbox" id="restrictReviewerFileAccess" checked=$restrictReviewerFileAccess label="manager.setup.reviewOptions.restrictReviewerFileAccess"}
		{/fbvFormSection}
	{/fbvFormArea}
{/capture}
{include file="controllers/tab/settings/reviewStage/form/reviewStageForm.tpl"}
