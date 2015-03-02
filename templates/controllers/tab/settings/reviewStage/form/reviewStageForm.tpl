{**
 * controllers/tab/settings/reviewStage/form/reviewStageForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review stage management form.
 *
 *}
{capture assign="additionalReviewFormContents"}
	{* Extend the basic review stage form with additional settings for OJS *}

	{fbvFormArea id="reviewOptions" title="manager.setup.reviewOptions" class="border"}
		<p>{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</p>
		{fbvFormSection title="manager.setup.reviewOptions.reviewerAccess" list=true}
			{fbvElement type="checkbox" id="reviewerAccessKeysEnabled" checked=$reviewerAccessKeysEnabled label="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}
			{fbvElement type="checkbox" id="restrictReviewerFileAccess" checked=$restrictReviewerFileAccess label="manager.setup.reviewOptions.restrictReviewerFileAccess"}
		{/fbvFormSection}
	{/fbvFormArea}
{/capture}
{include file="core:controllers/tab/settings/reviewStage/form/reviewStageForm.tpl"}
