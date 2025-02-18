{**
 * plugins/oaiMetadataFormats/marcxml/record.tpl
 *
 * Copyright (c) 2013-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * MARCXML-formatted metadata record for an article
 *}
<record
	xmlns="http://www.loc.gov/MARC21/slim"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.loc.gov/MARC21/slim https://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd">
	<leader>     nmb a2200000Iu 4500</leader>
	{if $publication->getData('datePublished')}
		<controlfield tag="008">"{$publication->getData('datePublished')|strtotime|date_format:"%y%m%d %Y"}                        eng  "</controlfield>
	{/if}
	{if $journal->getData('onlineIssn')}
		<datafield tag="022" ind1="#" ind2="#">
			<subfield code="a">{$journal->getData('onlineIssn')|escape}</subfield>
		</datafield>
	{/if}
	{if $journal->getData('printIssn')}
		<datafield tag="022" ind1="#" ind2="#">
			<subfield code="a">{$journal->getData('printIssn')|escape}</subfield>
		</datafield>
	{/if}
	{if $publication->getStoredPubId('doi')}
	<datafield tag="024" ind1="7" ind2="#">
		<subfield code="a">{$publication->getStoredPubId('doi')|escape}</subfield>
		<subfield code="2">doi</subfield>
	</datafield>
	{/if}

	<datafield tag="042" ind1=" " ind2=" ">
		<subfield code="a">dc</subfield>
	</datafield>
	<datafield tag="245" ind1="0" ind2="0">
		<subfield code="a">{$publication->getLocalizedTitle($journal->getPrimaryLocale())|escape}</subfield>
	</datafield>

	{assign var=authors value=$publication->getData('authors')}
	{foreach from=$authors item=author}
		<datafield tag="{if $authors|@count==1}100{else}720{/if}" ind1="1" ind2=" ">
			<subfield code="a">{$author->getFullName(false, true, $publicationLocale)|escape}</subfield>
			{foreach from=$author->getAffiliations() item=$affiliation}
				{if $affiliation->getRor()}<subfield code="u">{$affiliation->getRor()|escape}</subfield>
				{else}<subfield code="u">{$affiliation->getLocalizedName($publicationLocale)|escape}</subfield>{/if}
			{/foreach}
			{if $author->getUrl()}<subfield code="0">{$author->getUrl()|escape}</subfield>{/if}
			{if $author->getData('orcid') && $author->getData('orcidIsVerified')}<subfield code="0">{$author->getData('orcid')|escape}</subfield>{/if}
		</datafield>
	{/foreach}
	{if $subject}
		{foreach from=$subject item=$currentSubject}
			<datafield tag="653" ind1=" " ind2=" ">
				<subfield code="a">{$currentSubject|escape}</subfield>
			</datafield>
		{/foreach}
	{/if}
	{if $abstract}<datafield tag="520" ind1=" " ind2=" ">
		<subfield code="a">{$abstract|escape}</subfield>
	</datafield>{/if}

	{assign var=publisher value=$journal->getName($journal->getPrimaryLocale())}
	{if $journal->getData('publisherInstitution')}
		{assign var=publisher value=$journal->getData('publisherInstitution')}
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

	{foreach from=$publication->getData('galleys') item=galley}
		<datafield tag="856" ind1=" " ind2=" ">
			<subfield code="q">{$galley->getFileType()|escape}</subfield>
		</datafield>
	{/foreach}
	<datafield tag="856" ind1="4" ind2="0">
		<subfield code="u">{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$journal->getPath() page="article" op="view" path=$article->getBestId()|escape urlLocaleForPage=""}</subfield>
	</datafield>

	<datafield tag="786" ind1="0" ind2=" ">
		<subfield code="n">{$journal->getName($journal->getPrimaryLocale())|escape}; {$issue->getIssueIdentification()|escape}</subfield>
	</datafield>

	<datafield tag="546" ind1=" " ind2=" ">
		<subfield code="a">{$language}</subfield>
	</datafield>

	{if $publication->getData('coverage', $publicationLocale)}
		<datafield tag="500" ind1=" " ind2=" ">
			<subfield code="a">{$publication->getData('coverage', $publicationLocale)|escape}</subfield>
		</datafield>
	{/if}

	<datafield tag="540" ind1=" " ind2=" ">
		<subfield code="a">{translate key="submission.copyrightStatement" copyrightYear=$publication->getdata('copyrightYear') copyrightHolder=$publication->getData('copyrightHolder', $publicationLocale)|escape}</subfield>
	</datafield>
</record>
