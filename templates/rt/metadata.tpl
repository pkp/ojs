{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- article metadata page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rst.articleMetadata"}

{include file="rt/header.tpl"}

<h3>{$article->getArticleTitle()}</h3>
<i>{$article->getFirstAuthor()}</i>

<br /><br />

<table class="listing" width="100%">
	<tr><td colspan="3" class="headseparator"></td></tr>
	<tr valign="top">
		<td class="heading" width="25%">{translate key="rst.metadata.dublinCore"}</td>
		<td class="heading" width="25%">{translate key="rst.metadata.pkpItem"}</td>
		<td class="heading" width="50%">{translate key="rst.metadata.forThisDocument"}</td>
	</tr>
	<tr><td colspan="3" class="headseparator"></td></tr>

<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.title"}</td>
	<td>{translate key="rst.metadata.pkp.title"}</td>
	<td>{$article->getArticleTitle()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.primaryAuthor"}</td>
	<td>{translate key="rst.metadata.pkp.primaryAuthor"}</td>
	{assign var=authors value=$article->getAuthors()}
	{assign var=firstAuthor value=$authors[0]}
	<td>{$firstAuthor->getFullName()}{if $firstAuthor->getAffiliation()}, {$firstAuthor->getAffiliation()}{/if}{if $firstAuthor->getEmail()}, {$firstAuthor->getEmail()}{/if}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.subject"}</td>
	<td>{translate key="rst.metadata.pkp.discipline"}</td>
	<td>{$article->getDiscipline()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.subject"}</td>
	<td>{translate key="rst.metadata.pkp.subject"}</td>
	<td>{$article->getSubject()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.description"}</td>
	<td>{translate key="rst.metadata.pkp.abstract"}</td>
	<td>{$article->getAbstract()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.publisher"}</td>
	<td>{translate key="rst.metadata.pkp.publisher"}</td>
	{assign var=pubUrl value=$journalSettings.publisher.url}
	<td>{if $pubUrl}<a target="_new" href="{$pubUrl}">{/if}{$journalSettings.publisher.institution}{if $pubUrl}</a>{/if}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.contributor"}</td>
	<td>{translate key="rst.metadata.pkp.sponsors"}</td>
	<td>
		{foreach from=$journalSettings.contributors item=contributor}
			{assign var=contUrl value=$contributor.url}
			{if $contUrl}<a target="_new" href="{$contUrl}">{/if}{$contributor.name}{if $contUrl}</a>{/if}<br/>
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.date"}</td>
	<td>{translate key="rst.metadata.pkp.date"}</td>
	<td>{$publishedArticle->getDatePublished()|date_format:$dateFormatShort}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.type"}</td>
	<td>{translate key="rst.metadata.pkp.genre"}</td>
	<td>{translate key="rst.metadata.pkp.peerReviewed"}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.type"}</td>
	<td>{translate key="rst.metadata.pkp.type"}</td>
	<td>{$article->getType()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.format"}</td>
	<td>{translate key="rst.metadata.pkp.format"}</td>
	<td>
		{foreach from=$publishedArticle->getGalleys() item=galley name=galleys}
			{$galley->getLabel()}{if !$smarty.foreach.galleys.last}, {/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.identifier"}</td>
	<td>{translate key="rst.metadata.pkp.uri"}</td>
	<td><a target="_new" href="{$pageUrl}/article/view/{$articleId}/{$galleyId}">{$pageUrl|escape}/article/view/{$articleId}/{$galleyId}</a></td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.source"}</td>
	<td>{translate key="rst.metadata.pkp.source"}</td>
	<td>{$issue->getIssueIdentification()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.language"}</td>
	<td>{translate key="rst.metadata.pkp.language"}</td>
	<td>{$article->getLanguage()}</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
{if $journalRt->getSupplementaryFiles()}
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.relation"}</td>
	<td>{translate key="rst.metadata.pkp.suppFiles"}</td>
	<td>
		{foreach from=$publishedArticle->getSuppFiles() item=suppFile}
			<a href="{$pageUrl}/article/download/{$articleId}/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a> ({$suppFile->getNiceFileSize()})<br />
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
{/if}
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.coverage"}</td>
	<td>{translate key="rst.metadata.pkp.coverage"}</td>
	<td>
		{if $article->getCoverageGeo()}{$article->getCoverageGeo()}{assign var=notFirstItem value=1}{/if}{if $article->getCoverageChron()}{if $notFirstItem}, <br/>{/if}{$article->getCoverageChron()}{assign var=notFirstItem value=1}{/if}{if $article->getCoverageSample()}{if $notFirstItem}, <br/>{/if}{$article->getCoverageSample()}{assign var=notFirstItem value=1}{/if}
	</td>
</tr>
<tr><td colspan="3" class="separator"></td></tr>
<tr valign="top">
	<td>{translate key="rst.metadata.dublinCore.rights"}</td>
	<td>{translate key="rst.metadata.pkp.copyright"}</td>
	<td>{$journalSettings.copyrightNotice|nl2br}</td>
</tr>
</table>

{include file="rt/footer.tpl"}
