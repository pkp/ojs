{**
 * controllers/tab/settings/reviewStage/form/reviewStageForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review stage management form.
 *
 *}
{capture assign="additionalReviewFormOptions"}
	{* Extend the basic review stage form with additional settings for OJS *}

	{fbvFormSection label="manager.setup.reviewOptions.reviewerAccess" for="notAnId" description="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description" list=true}
		{fbvElement type="checkbox" id="reviewerAccessKeysEnabled" checked=$reviewerAccessKeysEnabled label="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}
		{fbvElement type="checkbox" id="restrictReviewerFileAccess" checked=$restrictReviewerFileAccess label="manager.setup.reviewOptions.restrictReviewerFileAccess"}
	{/fbvFormSection}
{/capture}
{include file="core:controllers/tab/settings/reviewStage/form/reviewStageForm.tpl"}
