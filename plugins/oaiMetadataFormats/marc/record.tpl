{**
 * plugins/oaiMetadataFormats/marc/record.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
		<varfield tag="022" ind1="#" ind2="#">
			<subfield label="$a">{$journal->getSetting('onlineIssn')|escape}</subfield>
		</varfield>
	{/if}
	{if $journal->getSetting('printIssn')}
		<varfield tag="022" ind1="#" ind2="#">
			<subfield label="$a">{$journal->getSetting('printIssn')|escape}</subfield>
		</varfield>
	{/if}
	<varfield tag="042" ind1=" " ind2=" ">
		<subfield label="a">dc</subfield>
	</varfield>
	<varfield tag="245" ind1="0" ind2="0">
		<subfield label="a">{$article->getTitle($journal->getPrimaryLocale())|escape}</subfield>
	</varfield>

	{assign var=authors value=$article->getAuthors()}
	{foreach from=$authors item=author}
		<varfield tag="{if $authors|@count==1}100{else}720{/if}" ind1="1" ind2=" ">
			<subfield label="a">{$author->getFullName(true)|escape}</subfield>
			{assign var=affiliation value=$author->getAffiliation($journal->getPrimaryLocale())}
			{if $affiliation}<subfield label="u">{$affiliation|escape}</subfield>{/if}
			{if $author->getUrl()}<subfield label="0">{$author->getUrl()|escape}</subfield>{/if}
			{if $author->getData('orcid')}<subfield label="0">{$author->getData('orcid')|escape}</subfield>{/if}
		</varfield>
	{/foreach}
	{if $subject}<varfield tag="653" ind1=" " ind2=" ">
		<subfield label="a">{$subject|escape}</subfield>
	</varfield>{/if}
	{if $abstract}<varfield tag="520" ind1=" " ind2=" ">
		<subfield label="a">{$abstract|escape}</subfield>
	</varfield>{/if}

	{assign var=publisher value=$journal->getTitle($journal->getPrimaryLocale())}
	{if $journal->getSetting('publisherInstitution')}
		{assign var=publisher value=$journal->getSetting('publisherInstitution')}
	{/if}
	<varfield tag="260" ind1=" " ind2=" ">
		<subfield label="b">{$publisher|escape}</subfield>
	</varfield>
	<dataField tag="260" ind1=" " ind2=" ">
		<subfield label="c">{$issue->getDatePublished()}</subfield>
	</dataField>

	{assign var=identifyType value=$section->getIdentifyType($journal->getPrimaryLocale())}
	{if $identifyType}<varfield tag="655" ind1=" " ind2="7">
		<subfield label="a">{$identifyType|escape}</subfield>
	</varfield>{/if}

	{foreach from=$article->getGalleys() item=galley}
		<varfield tag="856" ind1=" " ind2=" ">
			<subfield label="q">{$galley->getFileType()|escape}</subfield>
		</varfield>
	{/foreach}
	<varfield tag="856" ind1="4" ind2="0">
		<subfield label="u">{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()|escape}</subfield>
	</varfield>

	<varfield tag="786" ind1="0" ind2=" ">
		<subfield label="n">{$journal->getTitle($journal->getPrimaryLocale())|escape}; {$issue->getIssueIdentification()|escape}</subfield>
	</varfield>

	<varfield tag="546" ind1=" " ind2=" ">
		<subfield label="a">{$language}</subfield>
	</varfield>

	{foreach from=$article->getSuppFiles() item=suppFile}
		<varfield tag="787" ind1="0" ind2=" ">
			<subfield label="n">{url journal=$journal->getPath() page="article" op="download" path=$article->getId()|to_array:$suppFile->getFileId()|escape}</subfield>
		</varfield>
	{/foreach}

	{if $article->getCoverageGeo($journal->getPrimaryLocale())}
		<varfield tag="500" ind1=" " ind2=" ">
			<subfield label="a">{$article->getCoverageGeo($journal->getPrimaryLocale())|escape}</subfield>
		</varfield>
	{/if}
	{if $article->getCoverageChron($journal->getPrimaryLocale())}
		<varfield tag="500" ind1=" " ind2=" ">
			<subfield label="a">{$article->getCoverageChron($journal->getPrimaryLocale())|escape}</subfield>
		</varfield>
	{/if}
	{if $article->getCoverageSample($journal->getPrimaryLocale())}
		<varfield tag="500" ind1=" " ind2=" ">
			<subfield label="a">{$article->getCoverageSample($journal->getPrimaryLocale())|escape}</subfield>
		</varfield>
	{/if}

	<varfield tag="540" ind1=" " ind2=" ">
		<subfield label="a">{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getCopyrightHolder($journal->getPrimaryLocale())|escape}</subfield>
	</varfield>
</oai_marc>
