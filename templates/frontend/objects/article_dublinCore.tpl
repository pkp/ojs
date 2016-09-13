{**
 * templates/frontend/objects/article_dublinCore.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Print Dublin Core metadata for a article
 *
 * @uses $article Article The article to be displayed
 *}
<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />

{if $article->getSponsor(null)}{foreach from=$article->getSponsor(null) key=metaLocale item=metaValue}
	<meta name="DC.Contributor.Sponsor" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{if $article->getCoverage(null)}{foreach from=$article->getCoverage(null) key=metaLocale item=metaValue}
	<meta name="DC.Coverage" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{foreach from=$article->getAuthorString()|explode:", " item=dc_author}
	<meta name="DC.Creator.PersonalName" content="{$dc_author|escape}"/>
{/foreach}
{if is_a($article, 'PublishedArticle') && $article->getDatePublished()}
	<meta name="DC.Date.created" scheme="ISO8601" content="{$article->getDatePublished()|date_format:"%Y-%m-%d"}"/>
{/if}
	<meta name="DC.Date.dateSubmitted" scheme="ISO8601" content="{$article->getDateSubmitted()|date_format:"%Y-%m-%d"}"/>
{if $issue && $issue->getDatePublished()}
	<meta name="DC.Date.issued" scheme="ISO8601" content="{$issue->getDatePublished()|date_format:"%Y-%m-%d"}"/>
{/if}
	<meta name="DC.Date.modified" scheme="ISO8601" content="{$article->getDateStatusModified()|date_format:"%Y-%m-%d"}"/>
{if $article->getAbstract(null)}{foreach from=$article->getAbstract(null) key=metaLocale item=metaValue}
	<meta name="DC.Description" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{if is_a($article, 'PublishedArticle')}{foreach from=$article->getGalleys() item=dcGalleyFile}
	{if !is_a($dcGalleyFile, 'SupplementaryFile')}
		<meta name="DC.Format" scheme="IMT" content="{$dcGalleyFile->getFileType()|escape}"/>
	{/if}
{/foreach}{/if}
	<meta name="DC.Identifier" content="{$article->getBestArticleId($currentJournal)|escape}"/>
	{if $article->getPages()}
		<meta name="DC.Identifier.pageNumber" content="{$article->getPages()|escape}"/>
	{/if}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubId value=$pubIdPlugin->getPubId($article)}
		{if $pubId}
			<meta name="DC.Identifier.{$pubIdPlugin->getPubIdDisplayType()|escape}" content="{$pubId|escape}"/>
		{/if}
	{/foreach}
	<meta name="DC.Identifier.URI" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
	<meta name="DC.Language" scheme="ISO639-1" content="{$article->getLocale()|truncate:2:''}"/>
	<meta name="DC.Rights" content="{translate key="submission.copyrightStatement" copyrightHolder=$article->getLocalizedCopyrightHolder()|escape copyrightYear=$article->getCopyrightYear()|escape}" />
	<meta name="DC.Rights" content="{$article->getLicenseURL()|escape}"/>
	<meta name="DC.Source" content="{$currentJournal->getLocalizedName()|strip_tags|escape}"/>
	{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
	{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
	{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
	{/if}
	{if $issn}
		<meta name="DC.Source.ISSN" content="{$issn|strip_tags|escape}"/>
	{/if}
	{if $issue}
		<meta name="DC.Source.Issue" content="{$issue->getNumber()|strip_tags|escape}"/>
		<meta name="DC.Source.Volume" content="{$issue->getVolume()|strip_tags|escape}"/>
	{/if}
	<meta name="DC.Source.URI" content="{url journal=$currentJournal->getPath()}"/>
	{if $article->getSubject(null)}{foreach from=$article->getSubject(null) key=metaLocale item=metaValue}
		{foreach from=$metaValue|explode:"; " item=dcSubject}
			{if $dcSubject}
				<meta name="DC.Subject" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$dcSubject|escape}"/>
			{/if}
		{/foreach}
	{/foreach}{/if}
	<meta name="DC.Title" content="{$article->getLocalizedTitle()|escape}"/>
{foreach from=$article->getTitle(null) item=alternate key=metaLocale}
	{if $alternate != $article->getLocalizedTitle()}
		<meta name="DC.Title.Alternative" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$alternate|escape}"/>
	{/if}
{/foreach}
	<meta name="DC.Type" content="Text.Serial.Journal"/>
	<meta name="DC.Type.articleType" content="{$article->getSectionTitle()|strip_tags|escape}"/>
