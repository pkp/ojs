{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}

{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{elseif $submissionsErrors || $issuesErrors}
	<h2>{translate key="plugins.importexport.common.errorsOccured"}</h2>
	{foreach from=$issuesErrors item=issuesErrorMessages name=issuesErrors}
		{if $issuesErrorMessages|@count > 0}
			<p>{$smarty.foreach.issuesErrors.iteration}. {translate key="issue.issue"}</p>
			<ul>
				{foreach from=$issuesErrorMessages item=issuesErrorMessage}
					<li>{$issuesErrorMessage|escape}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
	{foreach from=$submissionsErrors item=submissionsErrorMessages name=submissionsErrors}
		{if $submissionsErrorMessages|@count > 0}
			<p>{$smarty.foreach.submissionsErrors.iteration}. {translate key="submission.submission"}</p>
			<ul>
				{foreach from=$submissionsErrorMessages item=submissionsErrorMessage}
					<li>{$submissionsErrorMessage|escape}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
{else}
	{translate key="plugins.importexport.native.importComplete"}
	<ul>
		{foreach from=$content item=contentItem}
			<li>
				{if is_a($contentItem, 'Submission')}
					{$contentItem->getLocalizedTitle()|strip_unsafe_html}</li>
				{else}
					{$contentItem->getIssueIdentification()|escape}
				{/if}
			</li>
		{/foreach}
	</ul>
{/if}