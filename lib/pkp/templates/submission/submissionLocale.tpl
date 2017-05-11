{**
 * templates/submission/submissionLocale.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission's locale field. To be included in any form that wants to handle
 * submission metadata.
 *}
{if count($supportedSubmissionLocaleNames) == 1}
	{* There is only one supported submission locale; choose it invisibly *}
	{foreach from=$supportedSubmissionLocaleNames item=localeName key=locale}
		{fbvElement type="hidden" id="locale" value=$locale}
	{/foreach}
{else}
	{* There are several submission locales available; allow choice *}
	{fbvFormSection title="submission.submit.submissionLocale" for="locale"}
		{fbvElement label="submission.submit.submissionLocaleDescription" required="true" type="select" id="locale" from=$supportedSubmissionLocaleNames selected=$locale translate=false readonly=$readOnly size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}
{/if}{* count($supportedSubmissionLocaleNames) == 1 *}
