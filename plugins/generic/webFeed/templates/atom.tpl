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

	<updated>{$latestDate|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

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

	<generator uri="https://pkp.sfu.ca/ops/" version="{$systemVersion|escape}">Open Server Systems</generator>
	{if $journal->getLocalizedDescription()}
		{assign var="description" value=$journal->getLocalizedDescription()}
	{elseif $journal->getLocalizedData('searchDescription')}
		{assign var="description" value=$journal->getLocalizedData('searchDescription')}
	{/if}

	<subtitle type="html">{$description|strip|escape:"html"}</subtitle>

	{foreach from=$submissions item=item}
		{assign var="submission" value=$item.submission}
		<entry>
			{* required elements *}
			<id>{url page="article" op="view" path=$submission->getBestId()}</id>
			<title>{$submission->getLocalizedTitle()|strip|escape:"html"}</title>
			<updated>{$submission->getLastModified()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

			{* recommended elements *}

			{foreach from=$submission->getCurrentPublication()->getData('authors') item=author name=authorList}
				<author>
					<name>{$author->getFullName(false)|strip|escape:"html"}</name>
					{if $author->getEmail()}
						<email>{$author->getEmail()|strip|escape:"html"}</email>
					{/if}
				</author>
			{/foreach}{* authors *}

			<link rel="alternate" href="{url page="article" op="view" path=$submission->getBestId()}" />

			{if $submission->getLocalizedAbstract()}
				<summary type="html" xml:base="{url page="article" op="view" path=$submission->getBestId()}">{$submission->getLocalizedAbstract()|strip|escape:"html"}</summary>
			{/if}

			{* optional elements *}

			{foreach from=$item.identifiers item=identifier}
				<category term="{$identifier.value|strip|escape:"html"}" scheme="https://pkp.sfu.ca/ops/category/{$identifier.type|strip|escape:"html"}"/>
			{/foreach}{* categories *}

			{* <contributor/> *}

			{if $submission->getDatePublished()}
				<published>{$submission->getDatePublished()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
			{/if}

			{* <source/> *}
			<rights>{translate|escape key="submission.copyrightStatement" copyrightYear=$submission->getCopyrightYear() copyrightHolder=$submission->getLocalizedCopyrightHolder()}</rights>
		</entry>
	{/foreach}{* articles *}
</feed>
