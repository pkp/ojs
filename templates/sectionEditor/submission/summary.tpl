{**
 * summary.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission summary table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="submission.submission"}</h3>

{assign var="editor" value=$submission->getEditor()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%">{$submission->getAuthorString()} {icon name="mail" url="FIXME"}</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td>{$submission->getArticleTitle()}</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td>{$submission->getSectionTitle()}</td>
	</tr>
	<tr>
		<td class="label">{translate key="editor.article.editor"}</td>
		<td>{if $editor}{$editor->getEditorFullName()} {icon name="mail" url="mailto:`$editor->getEditorEmail()`"}{else}{translate key="common.noneAssigned"}{/if}</td>
	</tr>
</table>
