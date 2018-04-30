{**
 * controllers/tab/settings/permissions/form/permissionSettingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Permissions form (extends the built-in one).
 *
 *}
{capture assign="additionalFormContent"}

	{fbvFormSection label="manager.subscriptionPolicies.authorSelfArchiveDescription" list=true}
		{fbvElement type="checkbox" id="enableAuthorSelfArchive" name="enableAuthorSelfArchive" value=1 checked=$enableAuthorSelfArchive label="manager.subscriptionPolicies.authorSelfArchive" disabled=$scheduledTasksEnabled|compare:0}
		{fbvElement type="textarea" id="authorSelfArchivePolicy" value=$authorSelfArchivePolicy rich=true multilingual=true}
	{/fbvFormSection}

	{fbvFormSection list=true title="manager.setup.copyrightYearBasis"}
		{fbvElement type="radio" id="copyrightYearBasis-issue" name="copyrightYearBasis" value="issue" checked=$copyrightYearBasis|compare:"issue" label="manager.setup.copyrightYearBasis.issue"}
		{fbvElement type="radio" id="copyrightYearBasis-submission" name="copyrightYearBasis" value="submission" checked=$copyrightYearBasis|compare:"submission" label="manager.setup.copyrightYearBasis.article"}
	{/fbvFormSection}
{/capture}
{include file="core:controllers/tab/settings/permissions/form/permissionSettingsForm.tpl additionalFormContent=$additionalFormContent}
