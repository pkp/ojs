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
	<meta name="DC.Title" content="{$article->getArticleTitle()|escape}"/>
{if $article->getTitleAlt1()}
	<meta name="DC.Title.Alternative" content="{$article->getTitleAlt1()|escape}"/>
{/if}
{if $article->getTitleAlt2()}
	<meta name="DC.Title.Alternative" content="{$article->getTitleAlt2()|escape}"/>
{/if}
	<meta name="DC.Creator" content="{$article->getFirstAuthor()|escape}"/>
{foreach from=$article->getSubject()|explode:"; " item=dc_subject}
	<meta name="DC.Subject" content="{$dc_subject}"/>
{/foreach}
{if $article->getArticleAbstract()}
	<meta name="DC.Description" content="{$article->getArticleAbstract()|strip_tags}"/>
{/if}
	<meta name="DC.Publisher" content="{$currentJournal->getTitle()|escape}"/>
	<meta name="DC.Publisher.Address" content="{$currentJournal->getSetting('contactEmail')}"/>
{foreach from=$article->getAuthorString()|explode:", " item=dc_author}
	<meta name="DC.Contributor" content="{$dc_author}"/>
{/foreach}
	<meta name="DC.Date" scheme="ISO8601" content="{$article->getDatePublished()|date_format:"%Y-%m-%d"}"/>
	<meta name="DC.Type" content="Text.Serial.Journal"/>
{foreach from=$article->getGalleys() item=dc_galley}
	<meta name="DC.Format" scheme="IMT" content="{$dc_galley->getFileType()}"/>		
{/foreach}
	<meta name="DC.Identifier" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
	<meta name="DC.Source" content="{$currentJournal->getUrl()|escape}"/>
	<meta name="DC.Language" scheme="ISO639-1" content="{$article->getLanguage()|escape}"/>
	<meta name="DC.Relation" content="World"/>
{if $article->getCoverageGeo()}
	<meta name="DC.Coverage" content="{$article->getCoverageGeo()|escape}"/>
{/if}
{if $article->getCoverageChron()}
	<meta name="DC.Coverage" content="{$article->getCoverageChron()|escape}"/>
{/if}
{if $article->getCoverageSample()}
	<meta name="DC.Coverage" content="{$article->getCoverageSample()|escape}"/>
{/if}
	<meta name="DC.Rights" content="{$currentJournal->getSetting('copyrightNotice')|strip_tags}"/>
