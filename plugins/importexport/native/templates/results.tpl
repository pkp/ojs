{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Result of operations this plugin performed
 *}

{if $submissionsWarnings || $issuesWarnings || $sectionWarnings}
	<h2>{translate key="plugins.importexport.common.warningsEncountered"}</h2>
	{foreach from=$issuesWarnings item=issuesWarningMessages name=issuesWarnings}
		{if $issuesWarningMessages|@count > 0}
			<p>{$smarty.foreach.issuesWarnings.iteration}. {translate key="issue.issue"}</p>
			<ul>
				{foreach from=$issuesWarningMessages item=issuesWarningMessage}
					<li>{$issuesWarningMessage|escape}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
	{foreach from=$sectionsWarnings item=sectionsWarningMessages name=sectionsWarnings}
		{if $sectionsWarningMessages|@count > 0}
			<p>{$smarty.foreach.sectionsWarnings.iteration}. {translate key="section.section"}</p>
			<ul>
				{foreach from=$sectionsWarningMessages item=sectionsWarningMessage}
					<li>{$sectionsWarningMessage|escape}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
	{foreach from=$submissionsWarnings item=submissionsWarningMessages name=submissionsWarnings}
		{if $submissionsWarningMessages|@count > 0}
			<p>{$smarty.foreach.submissionsWarnings.iteration}. {translate key="submission.submission"}</p>
			<ul>
				{foreach from=$submissionsWarningMessages item=submissionsWarningMessage}
					<li>{$submissionsWarningMessage|escape}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
{/if}
{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{elseif $submissionsErrors || $issuesErrors || $sectionErrors}
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
	{foreach from=$sectionsErrors item=sectionsErrorMessages name=sectionsErrors}
		{if $sectionsErrorMessages|@count > 0}
			<p>{$smarty.foreach.sectionsErrors.iteration}. {translate key="section.section"}</p>
			<ul>
				{foreach from=$sectionsErrorMessages item=sectionsErrorMessage}
					<li>{$sectionsErrorMessage|escape}</li>
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