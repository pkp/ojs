{**
 * plugins/oaiMetadataFormats/marc/record.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * MARC-formatted metadata record for an article
 *}
<oai_marc status="c" type="a" level="m" encLvl="3" catForm="u"
	xmlns="http://www.openarchives.org/OAI/1.1/oai_marc" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.openarchives.org/OAI/1.1/oai_marc http://www.openarchives.org/OAI/1.1/oai_marc.xsd">
	{if $article->getDatePublished()}
		<fixfield id="008">"{$article->getDatePublished()|strtotime|date_format:"%y%m%d %Y"}                        eng  "</fixfield>
	{/if}
	{if $journal->getSetting('onlineIssn')}
		<varfield id="022" i1="#" i2="#">
			<subfield label="$a">{$journal->getSetting('onlineIssn')|escape}</subfield>
		</varfield>
	{/if}
	{if $journal->getSetting('printIssn')}
		<varfield id="022" i1="#" i2="#">
			<subfield label="$a">{$journal->getSetting('printIssn')|escape}</subfield>
		</varfield>
	{/if}
	<varfield id="042" i1=" " i2=" ">
		<subfield label="a">dc</subfield>
	</varfield>
	<varfield id="245" i1="0" i2="0">
		<subfield label="a">{$article->getTitle($journal->getPrimaryLocale())|escape}</subfield>
	</varfield>

	{assign var=authors value=$article->getAuthors()}
	{foreach from=$authors item=author}
		<varfield id="{if $authors|@count==1}100{else}720{/if}" i1="1" i2=" ">
			<subfield label="a">{$author->getFullName(false, true)|escape}</subfield>
			{assign var=affiliation value=$author->getAffiliation($journal->getPrimaryLocale())}
			{if $affiliation}<subfield label="u">{$affiliation|escape}</subfield>{/if}
			{if $author->getUrl()}<subfield label="0">{$author->getUrl()|escape}</subfield>{/if}
			{if $author->getData('orcid')}<subfield label="0">{$author->getData('orcid')|escape}</subfield>{/if}
		</varfield>
	{/foreach}
	{if $subject}<varfield id="653" i1=" " i2=" ">
		<subfield label="a">{$subject|escape}</subfield>
	</varfield>{/if}
	{if $abstract}<varfield id="520" i1=" " i2=" ">
		<subfield label="a">{$abstract|escape}</subfield>
	</varfield>{/if}

	{assign var=publisher value=$journal->getName($journal->getPrimaryLocale())}
	{if $journal->getSetting('publisherInstitution')}
		{assign var=publisher value=$journal->getSetting('publisherInstitution')}
	{/if}
	<varfield id="260" i1=" " i2=" ">
		<subfield label="b">{$publisher|escape}</subfield>
	</varfield>
	<varfield id="260" i1=" " i2=" ">
		<subfield label="c">{$issue->getDatePublished()}</subfield>
	</varfield>

	{assign var=identifyType value=$section->getIdentifyType($journal->getPrimaryLocale())}
	{if $identifyType}<varfield id="655" i1=" " i2="7">
		<subfield label="a">{$identifyType|escape}</subfield>
	</varfield>{/if}

	{foreach from=$article->getGalleys() item=galley}
		<varfield id="856" i1=" " i2=" ">
			<subfield label="q">{$galley->getFileType()|escape}</subfield>
		</varfield>
	{/foreach}
	<varfield id="856" i1="4" i2="0">
		<subfield label="u">{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()|escape}</subfield>
	</varfield>

	<varfield id="786" i1="0" i2=" ">
		<subfield label="n">{$journal->getName($journal->getPrimaryLocale())|escape}; {$issue->getIssueIdentification()|escape}</subfield>
	</varfield>

	<varfield id="546" i1=" " i2=" ">
		<subfield label="a">{$language}</subfield>
	</varfield>

	{if $article->getCoverage($journal->getPrimaryLocale())}
		<varfield id="500" i1=" " i2=" ">
			<subfield label="a">{$article->getCoverage($journal->getPrimaryLocale())|escape}</subfield>
		</varfield>
	{/if}

	<varfield id="540" i1=" " i2=" ">
		<subfield label="a">{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getCopyrightHolder($journal->getPrimaryLocale())|escape}</subfield>
	</varfield>
</oai_marc>
