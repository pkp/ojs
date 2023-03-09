{**
 * plugins/generic/webFeed/templates/atom.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{url page="feed" op="feed"}</id>
	<title>{$journal->getLocalizedName()|escape:"html"|strip}</title>

	<updated>{$latestDate|date_format:"Y-m-d\TH:i:sP"}</updated>

	{* recommended elements *}
	{if $journal->getData('contactName')}
		<author>
			<name>{$journal->getData('contactName')|strip|escape:"html"}</name>
			{if $journal->getData('contactEmail')}
			<email>{$journal->getData('contactEmail')|strip|escape:"html"}</email>
			{/if}
		</author>
	{/if}

	<link rel="alternate" href="{url journal=$journal->getPath()}" />
	<link rel="self" type="application/atom+xml" href="{url page="feed" op="atom"}" />

	{* optional elements *}

	{* <category/> *}
	{* <contributor/> *}

	<generator uri="https://pkp.sfu.ca/ojs/" version="{$systemVersion|escape}">Open Journal Systems</generator>
	{if $journal->getLocalizedDescription()}
		{assign var="description" value=$journal->getLocalizedDescription()}
	{elseif $journal->getLocalizedData('searchDescription')}
		{assign var="description" value=$journal->getLocalizedData('searchDescription')}
	{/if}

	<subtitle type="html">{$description|strip|escape:"html"}</subtitle>

	{foreach from=$submissions item=item}
		{assign var=submission value=$item.submission}
		{assign var=publication value=$submission->getCurrentPublication()}
		<entry>
			{* required elements *}
			<id>{url page="article" op="view" path=$submission->getBestId()}</id>
			<title>{$publication->getLocalizedTitle()|strip|escape:"html"}</title>
			<updated>{$publication->getData('lastModified')|date_format:"Y-m-d\TH:i:sP"}</updated>

			{* recommended elements *}

			{foreach from=$publication->getData('authors') item=author}
				<author>
					<name>{$author->getFullName(false)|strip|escape:"html"}</name>
					{if $author->getEmail()}
						<email>{$author->getEmail()|strip|escape:"html"}</email>
					{/if}
				</author>
			{/foreach}{* authors *}

			<link rel="alternate" href="{url page="article" op="view" path=$submission->getBestId()}" />

			{if $publication->getLocalizedData('abstract') || $includeIdentifiers}
				<summary type="html" xml:base="{url page="article" op="view" path=$submission->getBestId()}">
					{if $includeIdentifiers}
						{foreach from=$item.identifiers item=identifier}
							{$identifier.label|strip|escape:"html"}: {', '|implode:$identifier.values|strip|escape:"html"}&lt;br /&gt;
						{/foreach}{* summary identifiers *}
						&lt;br /&gt;
					{/if}
					{$publication->getLocalizedData('abstract')|strip|escape:"html"}
				</summary>
			{/if}

			{* optional elements *}

			{foreach from=$item.identifiers item=identifier}
				{foreach from=$identifier.values item=value}
					<category term="{$value|strip|escape:"html"}" label="{$identifier.label|strip|escape:"html"}" scheme="https://pkp.sfu.ca/ojs/category/{$identifier.type|strip|escape:"html"}"/>
				{/foreach}
			{/foreach}{* categories *}

			{* <contributor/> *}

			<published>{$publication->getData('datePublished')|date_format:"Y-m-d\TH:i:sP"}</published>

			{* <source/> *}
			<rights>{translate|escape key="submission.copyrightStatement" copyrightYear=$publication->getData('copyrightYear') copyrightHolder=$publication->getLocalizedData('copyrightHolder')}</rights>
		</entry>
	{/foreach}{* submissions *}
</feed>
