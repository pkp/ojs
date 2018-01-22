{**
 * plugins/oaiMetadataFormats/marcxml/record.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * MARCXML-formatted metadata record for an article
 *}
<record
	xmlns="http://www.loc.gov/MARC21/slim"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd">
	<leader>     nmb a2200000Iu 4500</leader>
	{if $article->getDatePublished()}
		<controlfield tag="008">"{$article->getDatePublished()|strtotime|date_format:"%y%m%d %Y"}                        eng  "</controlfield>
	{/if}
	{if $journal->getSetting('onlineIssn')}
		<datafield tag="022" ind1="#" ind2="#">
			<subfield code="$a">{$journal->getSetting('onlineIssn')|escape}</subfield>
		</datafield>
	{/if}
	{if $journal->getSetting('printIssn')}
		<datafield tag="022" ind1="#" ind2="#">
			<subfield code="$a">{$journal->getSetting('printIssn')|escape}</subfield>
		</datafield>
	{/if}
	<datafield tag="042" ind1=" " ind2=" ">
		<subfield code="a">dc</subfield>
	</datafield>
	<datafield tag="245" ind1="0" ind2="0">
		<subfield code="a">{$article->getTitle($journal->getPrimaryLocale())|escape}</subfield>
	</datafield>

	{assign var=authors value=$article->getAuthors()}
	{foreach from=$authors item=author}
		<datafield tag="{if $authors|@count==1}100{else}720{/if}" ind1="1" ind2=" ">
			<subfield code="a">{$author->getFullName(true)|escape}</subfield>
			{assign var=affiliation value=$author->getAffiliation($journal->getPrimaryLocale())}
			{if $affiliation}<subfield code="u">{$affiliation|escape}</subfield>{/if}
			{if $author->getUrl()}<subfield code="0">{$author->getUrl()|escape}</subfield>{/if}
			{if $author->getData('orcid')}<subfield code="0">{$author->getData('orcid')|escape}</subfield>{/if}
		</datafield>
	{/foreach}
	{if $subject}<datafield tag="653" ind1=" " ind2=" ">
		<subfield code="a">{$subject|escape}</subfield>
	</datafield>{/if}
	{if $abstract}<datafield tag="520" ind1=" " ind2=" ">
		<subfield code="a">{$abstract|escape}</subfield>
	</datafield>{/if}

	{assign var=publisher value=$journal->getName($journal->getPrimaryLocale())}
	{if $journal->getSetting('publisherInstitution')}
		{assign var=publisher value=$journal->getSetting('publisherInstitution')}
	{/if}
	<datafield tag="260" ind1=" " ind2=" ">
		<subfield code="b">{$publisher|escape}</subfield>
	</datafield>
	<dataField tag="260" ind1=" " ind2=" ">
		<subfield code="c">{$issue->getDatePublished()}</subfield>
	</dataField>

	{assign var=identifyType value=$section->getIdentifyType($journal->getPrimaryLocale())}
	{if $identifyType}<datafield tag="655" ind1=" " ind2="7">
		<subfield code="a">{$identifyType|escape}</subfield>
	</datafield>{/if}

	{foreach from=$article->getGalleys() item=galley}
		<datafield tag="856" ind1=" " ind2=" ">
			<subfield code="q">{$galley->getFileType()|escape}</subfield>
		</datafield>
	{/foreach}
	<datafield tag="856" ind1="4" ind2="0">
		<subfield code="u">{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()|escape}</subfield>
	</datafield>

	<datafield tag="786" ind1="0" ind2=" ">
		<subfield code="n">{$journal->getName($journal->getPrimaryLocale())|escape}; {$issue->getIssueIdentification()|escape}</subfield>
	</datafield>

	<datafield tag="546" ind1=" " ind2=" ">
		<subfield code="a">{$language}</subfield>
	</datafield>

	{if $article->getCoverage($journal->getPrimaryLocale())}
		<datafield tag="500" ind1=" " ind2=" ">
			<subfield code="a">{$article->getCoverage($journal->getPrimaryLocale())|escape}</subfield>
		</datafield>
	{/if}

	<datafield tag="540" ind1=" " ind2=" ">
		<subfield code="a">{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getCopyrightHolder($journal->getPrimaryLocale())|escape}</subfield>
	</datafield>
</record>
