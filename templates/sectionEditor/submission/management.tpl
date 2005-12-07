{**
 * management.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission management table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="article.submission"}</h3>

{assign var="submissionFile" value=$submission->getSubmissionFile()}
{assign var="suppFiles" value=$submission->getSuppFiles()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" colspan="2" class="value">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl authorsArticleId=$submission->getArticleId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td colspan="2" class="value">{$submission->getArticleTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.originalFile"}</td>
		<td colspan="2" class="value">
			{if $submissionFile}
				<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$submissionFile->getFileId()}" class="file">{$submissionFile->getFileName()|escape}</a>&nbsp;&nbsp;{$submissionFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td colspan="2" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{url op="editSuppFile" path=$submission->getArticleId()|to_array:$suppFile->getSuppFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;<a href="{url op="editSuppFile" path=$submission->getArticleId()|to_array:$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;&nbsp;&nbsp;{if !$notFirst}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" path=$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>{/if}<br />
				{assign var=notFirst value=1}
			{foreachelse}
				{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" path=$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value="`$submitter->getFullName()` <`$submitter->getEmail()`>"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags}
			{$submitter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateSubmitted"}</td>
		<td>{$submission->getDateSubmitted()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td class="value">{$submission->getSectionTitle()|escape}</td>
		<td class="value"><form action="{url op="updateSection" path=$submission->getArticleId()}" method="post">{translate key="submission.changeSection"} <select name="section" size="1" class="selectMenu">{html_options options=$sections selected=$submission->getSectionId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	<tr><td>&nbsp;</td><td class="value" colspan="2" valign="top">
		<form action="{url op="setEditorFlags"}" method="post">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}"/>
		<table width="100%" class="listing">
			<tr><td colspan="{if $isEditor}6{else}5{/if}" class="headseparator">&nbsp;</td></tr>
			<tr class="heading" valign="bottom">
				<td width="30%">{translate key="user.role.editor"}</td>
				<td align="center" width="10%">{translate key="submission.review"}</td>
				<td align="center" width="10%">{translate key="submission.editing"}</td>
				<td width="{if $isEditor}20%{else}25%{/if}">{translate key="submission.request"}</td>
				<td width="{if $isEditor}20%{else}25%{/if}">{translate key="submission.underway"}</td>
				{if $isEditor}<td width="10%">{translate key="common.action"}</td>{/if}
			</tr>
			<tr><td colspan="{if $isEditor}6{else}5{/if}" class="headseparator">&nbsp;</td></tr>
			{assign var=editAssignments value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment name=editAssignments}
			{if $editAssignment->getEditorId() == $userId}
				{assign var=selfAssigned value=1}
			{/if}
				<tr valign="top">
					<td>
						{assign var=emailString value="`$editAssignment->getEditorFullName()` <`$editAssignment->getEditorEmail()`>"}
						{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags}
						{$editAssignment->getEditorFullName()|escape} {icon name="mail" url=$url}
					</td>
					<td align="center">
						<input
							type="checkbox"
							name="canReview-{$editAssignment->getEditId()}"
							{if $editAssignment->getIsEditor()}
								checked="checked"
								disabled="disabled"
							{else}
								{if $editAssignment->getCanReview()} checked="checked"{/if}
								{if !$isEditor}disabled="disabled"{/if}
							{/if}
						/>
					</td>
					<td align="center">
						<input
							type="checkbox"
							name="canEdit-{$editAssignment->getEditId()}"
							{if $editAssignment->getIsEditor()}
								checked="checked"
								disabled="disabled"
							{else}
								{if $editAssignment->getCanEdit()} checked="checked"{/if}
								{if !$isEditor}disabled="disabled"{/if}
							{/if}
						/>
					</td>
					<td>{if $editAssignment->getDateNotified()}{$editAssignment->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
					<td>{if $editAssignment->getDateUnderway()}{$editAssignment->getDateUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
					{if $isEditor}
						<td><a href="{url op="deleteEditAssignment" path=$editAssignment->getEditId()}" class="action">{translate key="common.delete"}</a></td>
					{/if}
				</tr>
				<tr><td colspan="{if $isEditor}6{else}5{/if}" class="{if $smarty.foreach.editAssignments.last}end{/if}separator">&nbsp;</td></tr>
			{foreachelse}
				<tr><td colspan="{if $isEditor}6{else}5{/if}" class="nodata">{translate key="common.noneAssigned"}</td></tr>
				<tr><td colspan="{if $isEditor}6{else}5{/if}" class="endseparator">&nbsp;</td></tr>
			{/foreach}
		</table>
		{if $isEditor}
			<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>&nbsp;&nbsp;
			<a href="{url op="assignEditor" path="sectionEditor" articleId=$submission->getArticleId()}" class="action">{translate key="editor.article.assignSectionEditor"}</a>
			| <a href="{url op="assignEditor" path="editor" articleId=$submission->getArticleId()}" class="action">{translate key="editor.article.assignEditor"}</a>
			{if !$selfAssigned}| <a href="{url op="assignEditor" path="editor" editorId=$userId articleId=$submission->getArticleId()}" class="action">{translate key="common.addSelf"}</a>{/if}
		{/if}
		</form>
	</td></tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|strip_unsafe_html|nl2br}</td>
	</tr>
	{/if}
	{if $publishedArticle}
	<tr>
		<td class="label">{translate key="submission.abstractViews"}</td>
		<td>{$publishedArticle->getViews()}</td>
	</tr>
	{/if}
</table>
