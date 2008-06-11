{**
 * googlescholar.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Metadata elements for articles based on preferred types for Google Scholar
 *
 * $Id$
 *}
	<meta name="gs_meta_revision" content="1.1" />
	<meta name="citation_journal_title" content="{$currentJournal->getJournalTitle()|strip_tags|escape}"/>
{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
{/if}
{if $issn}
	<meta name="citation_issn" content="{$issn|strip_tags|escape}"/>
{/if}
	<meta name="citation_authors" content="{foreach name="authors" from=$article->getAuthors() item=author}{$author->getLastName()|escape}, {$author->getFirstName()|escape}{if $author->getMiddleName() != ""} {$author->getMiddleName()|escape}{/if}{if !$smarty.foreach.authors.last}; {/if}{/foreach}"/>
	<meta name="citation_title" content="{$article->getArticleTitle()|strip_tags|escape}"/>
	<meta name="citation_date" content="{$article->getDatePublished()|date_format:"%d/%m/%Y"}"/>
	<meta name="citation_volume" content="{$issue->getVolume()|strip_tags|escape}"/>
	<meta name="citation_issue" content="{$issue->getNumber()|strip_tags|escape}"/>
{if $article->getPages()}
	<meta name="citation_firstpage" content="{$article->getPages()|escape}"/>
{/if}
{if $article->getDOI()}
	<meta name="citation_doi" content="{$article->getDOI()|escape}"/>
{/if}
	<meta name="citation_abstract_html_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
{foreach from=$article->getGalleys() item=dc_galley}
{if $dc_galley->getFileType()=="application/pdf"}
	<meta name="citation_pdf_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$dc_galley->getBestGalleyId($currentJournal)}"/>
{else}
	<meta name="citation_fulltext_html_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$dc_galley->getBestGalleyId($currentJournal)}"/>
{/if}
{/foreach}
