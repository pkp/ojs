{**
 * templates/controllers/grid/articleGalleys/versioning.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Versioning view for files: connection with article version.
 *}

{* Display version history of this galley 	
{include file="frontend/objects/file_revisions.tpl" parent=$article galley=$galley} 	*}	

{fbvFormArea id="fileVersioning"}
	{translate key="submissions.versions.current"}
	{assign var=currentVersion value=$galley->getCurrentFileVersion($galley->getFileId())}
	<table>
		<thead><td>Name</td><td>Date</td><td>According Metadata version</td></thead>
		<tbody>
			<tr><td>
				{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$currentVersion} 
			</td> 
			<td>
				{$currentVersion->getDateModified()|date_format}
			</td> 
			<td>
				{if $submissionSettingsRevisions} 
					{fbvElement type="select" name="submissionSettingsRevision" id="submissionSettingsRevision" from=$submissionSettingsRevisions translate=false selected=$currentSubmissionSettingsRevision}
				{/if}
			</td></tr>
		</tbody>	
	</table>
{assign var=otherRevisions value=$galley->getOtherRevisions($galley->getFileId())} 
	{if $otherRevisions}
		{translate key="submissions.versions.previous"}
		<table>
			<thead><td>Name</td><td>Date</td><td>According Metadata version</td></thead>
			<tbody>
			{foreach from=$otherRevisions item=revision}
				<tr>
					<td>{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$revision}</td>
					<td>
						{$revision->getDateModified()|date_format}
					</td> 
					<td>
						{if $submissionSettingsRevisions} 
							{fbvElement type="select" name="submissionSettingsRevision" id="submissionSettingsRevision" from=$submissionSettingsRevisions translate=false selected=$currentSubmissionSettingsRevision}
						{/if}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>	
	{/if}	
{/fbvFormArea}	

{fbvFormButtons submitText="common.save"}
