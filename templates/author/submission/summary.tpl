{**
 * summary.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's submission summary table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="article.submission"}</h3>

{assign var="editor" value=$submission->getEditor()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%">
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{$submission->getAuthorString()} {icon name="mail" url="`$pageUrl`/user/email?redirectUrl=$urlEscaped&authorsArticleId=`$submission->getArticleId()`"}
		</td>
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
		<td class="label">{translate key="user.role.editor"}</td>
		<td>
			{if $editor}
				{assign var=emailString value="`$editor->getEditorFullName()` <`$editor->getEditorEmail()`>"}
				{assign var=emailStringEscaped value=$emailString|escape:"url"}
				{assign var=urlEscaped value=$currentUrl|escape:"url"}
				{assign var=subjectEscaped value=$submission->getArticleTitle()|escape:"url"}
				{$editor->getEditorFullName()} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&redirectUrl=$urlEscaped&subject=$subjectEscaped"}
			{else}
				{translate key="common.noneAssigned"}
			{/if}
		</td>
	</tr>
</table>
