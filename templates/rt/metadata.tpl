{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- article metadata page.
 *
 * $Id$
 *}
{assign var=pageTitle value="rt.viewMetadata"}
{include file="rt/header.tpl"}

<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>

<br />

<table class="listing" width="100%">
	<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="25%" colspan="2">{translate key="rt.metadata.dublinCore"}</td>
		<td class="heading" width="25%">{translate key="rt.metadata.pkpItem"}</td>
		<td class="heading" width="50%">{translate key="rt.metadata.forThisDocument"}</td>
	</tr>
	<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>

<tr valign="top">
	<td>1.</td>
	<td>{translate key="rt.metadata.dublinCore.title"}</td>
	<td>{translate key="rt.metadata.pkp.title"}</td>
	<td>{$article->getArticleTitle()|strip_unsafe_html}</td>
</tr>
{foreach from=$article->getAuthors() item=author}
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>2.</td>
	<td width="25%">{translate key="rt.metadata.dublinCore.primaryAuthor"}</td>
	<td>{translate key="rt.metadata.pkp.primaryAuthor"}</td>
	<td>
		{$author->getFullName()|escape}{if $author->getAffiliation()}; {$author->getAffiliation()|escape}{/if}{if $author->getCountry()}; {$author->getCountryLocalized()|escape}{/if}
		</td>
</tr>
{/foreach}
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>3.</td>
	<td>{translate key="rt.metadata.dublinCore.subject"}</td>
	<td>{translate key="rt.metadata.pkp.discipline"}</td>
	<td>{$article->getArticleDiscipline()|escape}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>3.</td>
	<td>{translate key="rt.metadata.dublinCore.subject"}</td>
	<td>{translate key="rt.metadata.pkp.subject"}</td>
	<td>{$article->getArticleSubject()|escape}</td>
</tr>
{if $article->getArticleSubjectClass()}
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>3.</td>
	<td>{translate key="rt.metadata.dublinCore.subject"}</td>
	<td>{translate key="rt.metadata.pkp.subjectClass"}</td>
	<td>{$article->getArticleSubjectClass()|escape}</td>
</tr>
{/if}
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>4.</td>
	<td>{translate key="rt.metadata.dublinCore.description"}</td>
	<td>{translate key="rt.metadata.pkp.abstract"}</td>
	<td>{if $article->getArticleAbstract()}{$article->getArticleAbstract()|strip_unsafe_html|nl2br}{/if}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>5.</td>
	<td>{translate key="rt.metadata.dublinCore.publisher"}</td>
	<td>{translate key="rt.metadata.pkp.publisher"}</td>
	{assign var=pubUrl value=$journalSettings.publisher.url|escape}
	<td>{if $pubUrl}<a target="_new" href="{$pubUrl}">{/if}{$journalSettings.publisher.institution|escape}{if $pubUrl}</a>{/if}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>6.</td>
	<td>{translate key="rt.metadata.dublinCore.contributor"}</td>
	<td>{translate key="rt.metadata.pkp.sponsors"}</td>
	<td>{$article->getArticleSponsor()|escape}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>7.</td>
	<td>{translate key="rt.metadata.dublinCore.date"}</td>
	<td>{translate key="rt.metadata.pkp.date"}</td>
	<td>{$article->getDatePublished()|date_format:$dateFormatShort}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>8.</td>
	<td>{translate key="rt.metadata.dublinCore.type"}</td>
	<td>{translate key="rt.metadata.pkp.genre"}</td>
	<td>{if $section && $section->getSectionIdentifyType()}{$section->getSectionIdentifyType()|escape}{else}{translate key="rt.metadata.pkp.peerReviewed"}{/if}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>8.</td>
	<td>{translate key="rt.metadata.dublinCore.type"}</td>
	<td>{translate key="rt.metadata.pkp.type"}</td>
	<td>{$article->getArticleType()|escape}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>9.</td>
	<td>{translate key="rt.metadata.dublinCore.format"}</td>
	<td>{translate key="rt.metadata.pkp.format"}</td>
	<td>
		{foreach from=$article->getGalleys() item=galley name=galleys}
			{$galley->getGalleyLabel()|escape}{if !$smarty.foreach.galleys.last}, {/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>10.</td>
	<td>{translate key="rt.metadata.dublinCore.identifier"}</td>
	<td>{translate key="rt.metadata.pkp.uri"}</td>
	<td><a target="_new" href="{url page="article" op="view" path=$articleId}">{url page="article" op="view" path=$articleId}</a></td>
</tr>
{assign var=doi value=$article->getDOI()}
{if $doi}
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>10.</td>
	<td>{translate key="rt.metadata.dublinCore.identifier"}</td>
	<td>{translate key="rt.metadata.pkp.doi"}</td>
	<td>{$doi|escape}</a></td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
{/if}
<tr valign="top">
	<td>11.</td>
	<td>{translate key="rt.metadata.dublinCore.source"}</td>
	<td>{translate key="rt.metadata.pkp.source"}</td>
	<td>{$currentJournal->getJournalTitle()|escape}{if $issue}; {$issue->getIssueIdentification()|escape}{/if}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>12.</td>
	<td>{translate key="rt.metadata.dublinCore.language"}</td>
	<td>{translate key="rt.metadata.pkp.language"}</td>
	<td>{$article->getLanguage()|escape}</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
{if $journalRt->getSupplementaryFiles()}
<tr valign="top">
	<td>13.</td>
	<td>{translate key="rt.metadata.dublinCore.relation"}</td>
	<td>{translate key="rt.metadata.pkp.suppFiles"}</td>
	<td>
		{foreach from=$article->getSuppFiles() item=suppFile}
			<a href="{url page="article" op="downloadSuppFile" path=$articleId|to_array:$suppFile->getBestSuppFileId($currentJournal)}">{$suppFile->getSuppFileTitle()|escape}</a> ({$suppFile->getNiceFileSize()})<br />
		{/foreach}
	</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
{/if}
<tr valign="top">
	<td>14.</td>
	<td>{translate key="rt.metadata.dublinCore.coverage"}</td>
	<td>{translate key="rt.metadata.pkp.coverage"}</td>
	<td>
		{if $article->getArticleCoverageGeo()}{$article->getArticleCoverageGeo()|escape}{assign var=notFirstItem value=1}{/if}{if $article->getArticleCoverageChron()}{if $notFirstItem}, <br/>{/if}{$article->getArticleCoverageChron()|escape}{assign var=notFirstItem value=1}{/if}{if $article->getArticleCoverageSample()}{if $notFirstItem}, <br/>{/if}{$article->getArticleCoverageSample()|escape}{assign var=notFirstItem value=1}{/if}
	</td>
</tr>
<tr><td colspan="4" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>15.</td>
	<td>{translate key="rt.metadata.dublinCore.rights"}</td>
	<td>{translate key="rt.metadata.pkp.copyright"}</td>
	<td>{$currentJournal->getLocalizedSetting('copyrightNotice')|nl2br}</td>
</tr>
</table>

{include file="rt/footer.tpl"}
