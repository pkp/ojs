{**
 * suppFiles.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- supplementary files page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rst.suppFiles"}

{include file="rt/header.tpl"}

<h3>{$article->getArticleTitle()}</h3>
<i>{$article->getFirstAuthor()}</i>

<br />

<div class="separator"></div>

{foreach from=$article->getSuppFiles() item=suppFile key=key}
<h4>{$key+1}. {$suppFile->getTitle()}</h4>
<table class="data" width="100%">
<tr valign="top">
	<td class="label" width="20%">{translate key="common.subject"}</td>
	<td class="value" width="80%">
		{$suppFile->getSubject()}
	</td>
</tr>
<tr valign="top">
	<td class="label" width="20%">{translate key="common.type"}</td>
	<td class="value" width="80%">
		{$suppFile->getType()}
	</td>
</tr>
<tr valign="top">
	<td class="label" width="20%">&nbsp;</td>
	<td class="value" width="80%">
		<a href="{$pageUrl}/article/download/{$articleId}/{$suppFile->getFileId()}" class="action">{translate key="rst.suppFiles.download"}</a> ({$suppFile->getNiceFileSize()})&nbsp;&nbsp;&nbsp;&nbsp;<a href="{$pageUrl}/rt/suppFileMetadata/{$articleId}/{$galleyId}/{$suppFile->getSuppFileId()}" class="action">{translate key="rst.suppFiles.viewMetadata"}</a>
	</td>
</tr>
</table>

{/foreach}

{include file="rt/footer.tpl"}
