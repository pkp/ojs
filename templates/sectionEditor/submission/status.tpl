{**
 * status.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission status table.
 *
 * $Id$
 *}

<a name="status"></a>
<h3>{translate key="submission.status"}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="submission.status"}</td>
		<td width="30%" class="value">FIXME</td>
		<td width="50%" class="value">
			{if $submission->getStatus()}
				<a href="{$requestPageUrl}/archiveSubmission/{$submission->getArticleId()}" class="action">{translate key="editor.article.archiveSubmission"}</a>
			{else}
				<a href="{$requestPageUrl}/restoreToQueue/{$submission->getArticleId()}" class="action">{translate key="editor.article.restoreToQueue"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.initiated"}</td>
		<td colspan="2" class="value">FIXME</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.lastModified"}</td>
		<td colspan="2" class="value">FIXME</td>
	</tr>
</table>
