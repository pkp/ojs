{**
 * templates/copyeditor/submission/copyedit.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the copyeditor's submission management table.
 *
 *}
<div id="copyedit">
<h3>{translate key="submission.copyediting"}</h3>

{if $currentJournal->getLocalizedSetting('copyeditInstructions') != ''}
<p><a href="javascript:openHelp('{url op="instructions" path="copy"}')" class="action">{translate key="submission.copyedit.instructions"}</a></p>
{/if}

<table width="100%" class="data">
	<tr>
		<td class="label" width="20%">{translate key="user.role.copyeditor"}</td>
		<td class="value" width="80%">{if $submission->getUserIdBySignoffType('SIGNOFF_COPYEDITING_INITIAL')}{$copyeditor->getFullName()|escape}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">
			<a class="action" href="{url op="viewMetadata" path=$submission->getId()}">{translate key="submission.reviewMetadata"}</a>
			{if $metaCitations}<a class="action" href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a>{/if}
		</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="35%">{translate key="submission.copyedit.initialCopyedit"}</td>
		{assign var="initialCopyeditSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_INITIAL')}
		<td>{$initialCopyeditSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$initialCopyeditSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>
			{if not $initialCopyeditSignoff->getDateNotified() or $initialCopyeditSignoff->getDateCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{url|assign:"url" op="completeCopyedit" articleId=$submission->getId()}
				{translate|assign:"confirmMessage" key="common.confirmComplete"}
				{icon name="mail" onclick="return confirm('$confirmMessage')" url=$url}
			{/if}
			{$initialCopyeditSignoff->getDateCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="4">
			{translate key="common.file"}:
			{if $initialCopyeditSignoff->getDateNotified() && $initialCopyeditFile}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$initialCopyeditSignoff->getFileId():$initialCopyeditSignoff->getFileRevision()}" class="file">{$initialCopyeditFile->getFileName()|escape}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
			<br />
			<form method="post" action="{url op="uploadCopyeditVersion"}"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getId()}" />
				<input type="hidden" name="copyeditStage" value="initial" />
				<input type="file" name="upload"{if not $initialCopyeditSignoff->getDateNotified() or $initialCopyeditSignoff->getDateCompleted()} disabled="disabled"{/if} class="uploadField" />
				<input type="submit" class="button" value="{translate key="common.upload"}"{if not $initialCopyeditSignoff->getDateNotified() or $initialCopyeditSignoff->getDateCompleted()} disabled="disabled"{/if} />
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>2.</td>
		{assign var="authorCopyeditSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_AUTHOR')}
		<td>{translate key="submission.copyedit.editorAuthorReview"}</td>
		<td>{$authorCopyeditSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$authorCopyeditSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$authorCopyeditSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="4">
			{translate key="common.file"}:
			{if $authorCopyeditSignoff->getDateCompleted() && $editorAuthorCopyeditFile}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$authorCopyeditSignoff->getFileId():$authorCopyeditSignoff->getFileRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()|escape}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>3.</td>
		{assign var="finalCopyeditSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_FINAL')}
		<td>{translate key="submission.copyedit.finalCopyedit"}</td>
		<td width="20%">{$finalCopyeditSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td width="20%">{$finalCopyeditSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td width="20%">
			{if not $finalCopyeditSignoff->getDateNotified() or $finalCopyeditSignoff->getDateCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{url|assign:"url" op="completeFinalCopyedit" articleId=$submission->getId()}
				{translate|assign:"confirmMessage" key="common.confirmComplete"}
				{icon name="mail" onclick="return confirm('$confirmMessage')" url=$url}
			{/if}
			{$finalCopyeditSignoff->getDateCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="4">
			{translate key="common.file"}:
			{if $finalCopyeditSignoff->getDateNotified() && $finalCopyeditFile}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$finalCopyeditSignoff->getFileId():$finalCopyeditSignoff->getFileRevision()}" class="file">{$finalCopyeditFile->getFileName()|escape}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
			<br />
			<form method="post" action="{url op="uploadCopyeditVersion"}"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getId()}" />
				<input type="hidden" name="copyeditStage" value="final" />
				<input type="file" name="upload"{if not $finalCopyeditSignoff->getDateNotified() or $finalCopyeditSignoff->getDateCompleted()} disabled="disabled"{/if} class="uploadField">
				<input type="submit" class="button" value="{translate key="common.upload"}"{if not $finalCopyeditSignoff->getDateNotified() or $finalCopyeditSignoff->getDateCompleted()} disabled="disabled"{/if} />
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.copyedit.copyeditComments"}
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
	<a href="javascript:openComments('{url op="viewCopyeditComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewCopyeditComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}
</div>
