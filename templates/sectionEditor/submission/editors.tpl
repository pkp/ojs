{**
 * editors.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission editors table.
 *
 * $Id$
 *}
<a name="editors"></a>
<h3>{translate key="user.role.editors"}</h3>
<form action="{url op="setEditorFlags"}" method="post">
<input type="hidden" name="articleId" value="{$submission->getArticleId()}"/>
<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="{if $isEditor}20%{else}25%{/if}">&nbsp;</td>
		<td width="30%">&nbsp;</td>
		<td width="10%">{translate key="submission.review"}</td>
		<td width="10%">{translate key="submission.editing"}</td>
		<td width="{if $isEditor}20%{else}25%{/if}">{translate key="submission.request"}</td>
		{if $isEditor}<td width="10%">{translate key="common.action"}</td>{/if}
	</tr>
	{assign var=editAssignments value=$submission->getEditAssignments()}
	{foreach from=$editAssignments item=editAssignment name=editAssignments}
	{if $editAssignment->getEditorId() == $userId}
		{assign var=selfAssigned value=1}
	{/if}
		<tr valign="top">
			<td>{if $editAssignment->getIsEditor()}{translate key="user.role.editor"}{else}{translate key="user.role.sectionEditor"}{/if}</td>
			<td>
				{assign var=emailString value="`$editAssignment->getEditorFullName()` <`$editAssignment->getEditorEmail()`>"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags articleId=$submission->getArticleId()}
				{$editAssignment->getEditorFullName()|escape} {icon name="mail" url=$url}
			</td>
			<td>
				&nbsp;&nbsp;<input
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
			<td>
				&nbsp;&nbsp;<input
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
			{if $isEditor}
				<td><a href="{url op="deleteEditAssignment" path=$editAssignment->getEditId()}" class="action">{translate key="common.delete"}</a></td>
			{/if}
		</tr>
	{foreachelse}
		<tr><td colspan="{if $isEditor}6{else}5{/if}" class="nodata">{translate key="common.noneAssigned"}</td></tr>
	{/foreach}
</table>
{if $isEditor}
	<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>&nbsp;&nbsp;
	<a href="{url op="assignEditor" path="sectionEditor" articleId=$submission->getArticleId()}" class="action">{translate key="editor.article.assignSectionEditor"}</a>
	|&nbsp;<a href="{url op="assignEditor" path="editor" articleId=$submission->getArticleId()}" class="action">{translate key="editor.article.assignEditor"}</a>
	{if !$selfAssigned}|&nbsp;<a href="{url op="assignEditor" path="editor" editorId=$userId articleId=$submission->getArticleId()}" class="action">{translate key="common.addSelf"}</a>{/if}
{/if}
</form>
