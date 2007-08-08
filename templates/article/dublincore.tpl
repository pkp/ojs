{**
 * dublincore.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dublin Core metadata elements for articles.
 *
 * $Id$
 *}
<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />

{* DC.Contributor.PersonalName (reviewer) *}
{if $article->getSponsor()}
	<meta name="DC.Contributor.Sponsor" content="{$article->getSponsor()|strip_tags|escape}"/>
{/if}
{if $article->getCoverageSample()}
	<meta name="DC.Coverage" content="{$article->getCoverageSample()|strip_tags|escape}"/>
{/if}
{if $article->getCoverageGeo()}
	<meta name="DC.Coverage.spatial" content="{$article->getCoverageGeo()|strip_tags|escape}"/>
{/if}
{if $article->getCoverageChron()}
	<meta name="DC.Coverage.temporal" content="{$article->getCoverageChron()|strip_tags|escape}"/>
{/if}
{foreach from=$article->getAuthorString()|explode:", " item=dc_author}
	<meta name="DC.Creator.PersonalName" content="{$dc_author|escape}"/>
{/foreach}
{if $issue->getOpenAccessDate()}
	<meta name="DC.Date.available" scheme="ISO8601" content="{$issue->getOpenAccessDate()|date_format:"%Y-%m-%d"}"/>
{/if}
	<meta name="DC.Date.created" scheme="ISO8601" content="{$article->getDatePublished()|date_format:"%Y-%m-%d"}"/>
{* DC.Date.dateAccepted (editor submission DAO) *}
{* DC.Date.dateCopyrighted *}
{* DC.Date.dateReveiwed (revised file DAO) *}
	<meta name="DC.Date.dateSubmitted" scheme="ISO8601" content="{$article->getDateSubmitted()|date_format:"%Y-%m-%d"}"/>
	<meta name="DC.Date.issued" scheme="ISO8601" content="{$issue->getDatePublished()|date_format:"%Y-%m-%d"}"/>
	<meta name="DC.Date.modified" scheme="ISO8601" content="{$article->getDateStatusModified()|date_format:"%Y-%m-%d"}"/>
{if $article->getArticleAbstract()}
	<meta name="DC.Description" content="{$article->getArticleAbstract()|strip_tags|escape}"/>
{/if}
{foreach from=$article->getGalleys() item=dc_galley}
	<meta name="DC.Format" scheme="IMT" content="{$dc_galley->getFileType()|escape}"/>		
{/foreach}
	<meta name="DC.Identifier" content="{$article->getBestArticleId($currentJournal)|escape}"/>
{if $article->getPages()}
	<meta name="DC.Identifier.pageNumber" content="{$article->getPages()|escape}"/>
{/if}
{if $article->getDOI()}
	<meta name="DC.Identifier.DOI" content="{$article->getDOI()|escape}"/>
{/if}
	<meta name="DC.Identifier.URI" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
	<meta name="DC.Language" scheme="ISO639-1" content="{$article->getLanguage()|strip_tags|escape}"/>
	<meta name="DC.Publisher" content="{$siteTitle|strip_tags|escape}"/>
{* DC.Publisher.Address (email addr) *}
{if $currentJournal->getSetting('copyrightNotice')}
	<meta name="DC.Rights" content="{$currentJournal->getSetting('copyrightNotice')|strip_tags|escape}"/>
{/if}
{* DC.Rights.accessRights *}
	<meta name="DC.Source" content="{$currentJournal->getTitle()|strip_tags|escape}"/>
{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
{/if}
{if $issn}
	<meta name="DC.Source.ISSN" content="{$issn|strip_tags|escape}"/>
{/if}
	<meta name="DC.Source.Issue" content="{$issue->getNumber()|strip_tags|escape}"/>
	<meta name="DC.Source.URI" content="{$currentJournal->getUrl()|strip_tags|escape}"/>
	<meta name="DC.Source.Volume" content="{$issue->getVolume()|strip_tags|escape}"/>
{foreach from=$article->getSubject()|explode:"; " item=dc_subject}
{if $dc_subject}
	<meta name="DC.Subject" content="{$dc_subject|escape}"/>
{/if}
{/foreach}
	<meta name="DC.Title" content="{$article->getArticleTitle()|strip_tags|escape}"/>
{if $article->getTitleAlt1()}
	<meta name="DC.Title.Alternative" content="{$article->getTitleAlt1()|strip_tags|escape}"/>
{/if}
{if $article->getTitleAlt2()}
	<meta name="DC.Title.Alternative" content="{$article->getTitleAlt2()|strip_tags|escape}"/>
{/if}
	<meta name="DC.Type" content="Text.Serial.Journal"/>
	<meta name="DC.Type.articleType" content="{$article->getSectionTitle()|strip_tags|escape}"/>	