{**
 * plugins/oaiMetadataFormats/marc/record.tpl
 *
 * Copyright (c) 2013-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * MARC-formatted metadata record for an article
 *}
<oai_marc status="c" type="a" level="m" encLvl="3" catForm="u"
	xmlns="http://www.openarchives.org/OAI/1.1/oai_marc" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.openarchives.org/OAI/1.1/oai_marc http://www.openarchives.org/OAI/1.1/oai_marc.xsd">
	{if $publication->getData('datePublished')}
		<fixfield id="008">"{$publication->getData('datePublished')|strtotime|date_format:"%y%m%d %Y"}                        eng  "</fixfield>
	{/if}
	{if $journal->getData('onlineIssn')}
		<varfield id="022" i1="#" i2="#">
			<subfield label="$a">{$journal->getData('onlineIssn')|escape}</subfield>
		</varfield>
	{/if}
	{if $journal->getData('printIssn')}
		<varfield id="022" i1="#" i2="#">
			<subfield label="$a">{$journal->getData('printIssn')|escape}</subfield>
		</varfield>
	{/if}
	<varfield id="042" i1=" " i2=" ">
		<subfield label="a">dc</subfield>
	</varfield>
	<varfield id="245" i1="0" i2="0">
		<subfield label="a">{$publication->getLocalizedTitle($journal->getPrimaryLocale())|escape}</subfield>
	</varfield>

	{assign var=authors value=$publication->getData('authors')}
	{foreach from=$authors item=author}
		<varfield id="{if $authors|@count==1}100{else}720{/if}" i1="1" i2=" ">
			<subfield label="a">{$author->getFullName(false, true, $publicationLocale)|escape}</subfield>
			{foreach from=$author->getAffiliations() item=$affiliation}
				{if $affiliation->getRor()}<subfield code="u">{$affiliation->getRor()|escape}</subfield>
				{else}<subfield code="u">{$affiliation->getLocalizedName($publicationLocale)|escape}</subfield>{/if}
			{/foreach}
			{if $author->getUrl()}<subfield label="0">{$author->getUrl()|escape}</subfield>{/if}
			{if $author->getData('orcid') && $author->getData('orcidIsVerified')}<subfield label="0">{$author->getData('orcid')|escape}</subfield>{/if}
		</varfield>
	{/foreach}
	{if $subject}
		{foreach from=$subject item=$currentSubject}
			<varfield id="653" i1=" " i2=" ">
				<subfield label="a">{$currentSubject|escape}</subfield>
			</varfield>
		{/foreach}
	{/if}
	{if $abstract}<varfield id="520" i1=" " i2=" ">
		<subfield label="a">{$abstract|escape}</subfield>
	</varfield>{/if}

	{assign var=publisher value=$journal->getName($journal->getPrimaryLocale())}
	{if $journal->getData('publisherInstitution')}
		{assign var=publisher value=$journal->getData('publisherInstitution')}
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

	{foreach from=$publication->getData('galleys') item=galley}
		<varfield id="856" i1=" " i2=" ">
			<subfield label="q">{$galley->getFileType()|escape}</subfield>
		</varfield>
	{/foreach}
	<varfield id="856" i1="4" i2="0">
		<subfield label="u">{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$journal->getPath() page="article" op="view" path=$article->getBestId()|escape urlLocaleForPage=""}</subfield>
	</varfield>

	<varfield id="773" i1="0" i2=" ">
		<subfield label="t">{$journal->getName($journal->getPrimaryLocale())|escape};</subfield>
	        <subfield label="g">{$issue->getIssueIdentification()|escape}</subfield>
	</varfield>

	<varfield id="546" i1=" " i2=" ">
		<subfield label="a">{$language}</subfield>
	</varfield>

	{if $publication->getData('coverage', $publicationLocale)}
		<varfield id="500" i1=" " i2=" ">
			<subfield label="a">{$publication->getData('coverage', $publicationLocale)|escape}</subfield>
		</varfield>
	{/if}

	<varfield id="540" i1=" " i2=" ">
		<subfield label="a">{translate key="submission.copyrightStatement" copyrightYear=$publication->getdata('copyrightYear') copyrightHolder=$publication->getData('copyrightHolder', $publicationLocale)|escape}</subfield>
	</varfield>
</oai_marc>
