{**
 * plugins/generic/webFeed/templates/rss.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/"
	xmlns:cc="http://web.resource.org/cc/"
	xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/">

	<channel rdf:about="{url journal=$journal->getPath()}">
		{* required elements *}
		<title>{$journal->getLocalizedName()|strip|escape:"html"}</title>
		<link>{url journal=$journal->getPath()}</link>

		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedData('searchDescription')}
			{assign var="description" value=$journal->getLocalizedData('searchDescription')}
		{/if}

		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{assign var="publisherInstitution" value=$journal->getData('publisherInstitution')}
		{if $publisherInstitution}
			<dc:publisher>{$publisherInstitution|strip|escape:"html"}</dc:publisher>
		{/if}

		{if $journal->getPrimaryLocale()}
			<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<prism:publicationName>{$journal->getLocalizedName()|strip|escape:"html"}</prism:publicationName>

		{if $journal->getData('printIssn')}
			{assign var="ISSN" value=$journal->getData('printIssn')}
		{elseif $journal->getData('onlineIssn')}
			{assign var="ISSN" value=$journal->getData('onlineIssn')}
		{/if}

		{if $ISSN}
			<prism:issn>{$ISSN|escape}</prism:issn>
		{/if}

		{if $journal->getLocalizedData('licenseTerms')}
			<prism:copyright>{$journal->getLocalizedData('licenseTerms')|strip|escape:"html"}</prism:copyright>
		{/if}

		<items>
			<rdf:Seq>
			{foreach from=$submissions item=item}
				<rdf:li rdf:resource="{url page="article" op="view" path=$item.submission->getBestId()}"/>
			{/foreach}{* articles *}
			</rdf:Seq>
		</items>
	</channel>

{foreach from=$submissions item=item}
	{assign var=submission value=$item.submission}
	{assign var=publication value=$submission->getCurrentPublication()}
	<item rdf:about="{url page="article" op="view" path=$submission->getBestId()}">

		{* required elements *}
		<title>{$publication->getLocalizedTitle()|strip|escape:"html"}</title>
		<link>{url page="article" op="view" path=$submission->getBestId()}</link>

		{* optional elements *}
		{if $publication->getLocalizedData('abstract') || $includeIdentifiers}
			<description>
				{if $includeIdentifiers}
					{foreach from=$item.identifiers item=identifier}
						{$identifier.label|strip|escape:"html"}: {', '|implode:$identifier.values|strip|escape:"html"}&lt;br /&gt;
					{/foreach}{* categories *}
					&lt;br /&gt;
				{/if}
				{$publication->getLocalizedData('abstract')|strip|escape:"html"}
			</description>
		{/if}

		{foreach from=$item.identifiers item=identifier}
			{foreach from=$identifier.values item=value}
				<dc:subject>
					<rdf:Description>
						<taxo:topic rdf:resource="https://pkp.sfu.ca/ojs/category/{$identifier.type|strip|escape:"html"}" />
						<rdf:value>{$value|strip|escape:"html"}</rdf:value>
					</rdf:Description>
				</dc:subject>
			{/foreach}
		{/foreach}{* categories *}

		{foreach from=$publication->getData('authors') item=author name=authorList}
			<dc:creator>{$author->getFullName(false)|strip|escape:"html"}</dc:creator>
		{/foreach}

		<dc:rights>
			{translate|escape key="submission.copyrightStatement" copyrightYear=$publication->getData('copyrightYear') copyrightHolder=$publication->getLocalizedData('copyrightHolder')}
			{$publication->getData('licenseUrl')|escape}
		</dc:rights>
		<cc:license {if $publication->getData('accessStatus') == \APP\submission\Submission::ARTICLE_ACCESS_OPEN && $publication->isCCLicense()}rdf:resource="{$publication->getData('licenseUrl')|escape}"{/if} />

		<dc:date>{$publication->getData('datePublished')|date_format:"%Y-%m-%d"}</dc:date>
		<prism:publicationDate>{$publication->getData('datePublished')|date_format:"%Y-%m-%d"}</prism:publicationDate>

		{if $publication->getStartingPage()}
			<prism:startingPage>{$publication->getStartingPage()|escape}</prism:startingPage>
		{/if}
		{if $publication->getEndingPage()}
			<prism:endingPage>{$publication->getEndingPage()|escape}</prism:endingPage>
		{/if}

		{if $publication->getDoi()}
			<prism:doi>{$publication->getDoi()|escape}</prism:doi>
		{/if}
	</item>
{/foreach}{* articles *}

</rdf:RDF>
