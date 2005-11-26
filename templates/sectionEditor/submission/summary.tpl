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
<h3>{translate key="article.submission"}</h3>

{assign var="editor" value=$submission->getEditor()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl authorsArticleId=$submission->getArticleId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td>{$submission->getArticleTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td>{$submission->getSectionTitle()|escape}</td>
	</tr>
	<tr>
		<td class="label">{translate key="user.role.sectionEditor"}</td>
		<td>
			{if $editor}
				{assign var=emailString value="`$editor->getEditorFullName()` <`$editor->getEditorEmail()`>"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags}
				{$editor->getEditorFullName()|escape} {icon name="mail" url=$url}
			{else}
				{translate key="common.noneAssigned"}
			{/if}
		</td>
	</tr>
</table>
