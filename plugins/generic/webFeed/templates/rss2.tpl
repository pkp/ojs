{**
 * plugins/generic/webFeed/templates/rss2.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 *}
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://web.resource.org/cc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<atom:link href="{$feedUrl}" rel="self" type="application/rss+xml" />
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
		{if $journal->getPrimaryLocale()}
			<language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
		{/if}

		{if $journal->getLocalizedData('licenseTerms')}
			<copyright>{$journal->getLocalizedData('licenseTerms')|strip|escape:"html"}</copyright>
		{/if}

		{if $journal->getData('contactEmail')}
			<managingEditor>{$journal->getData('contactEmail')|strip|escape:"html"}{if $journal->getData('contactName')} ({$journal->getData('contactName')|strip|escape:"html"}){/if}</managingEditor>
		{/if}

		{if $journal->getData('supportEmail')}
			<webMaster>{$journal->getData('supportEmail')|strip|escape:"html"}{if $journal->getData('contactName')} ({$journal->getData('supportName')|strip|escape:"html"}){/if}</webMaster>
		{/if}

		<pubDate>{$latestDate|date_format:$smarty.const.DATE_RSS}</pubDate>

		{* <lastBuildDate/> *}
		{* <category/> *}
		{* <creativeCommons:license/> *}

		<generator>OJS {$systemVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach from=$submissions item=item}
			{assign var=submission value=$item.submission}
			{assign var=publication value=$submission->getCurrentPublication()}
			<item>
				{* required elements *}
				<title>{$publication->getLocalizedTitle()|strip|escape:"html"}</title>
				<link>{url page="article" op="view" path=$submission->getBestId()}</link>
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

				{* optional elements *}
				{* <author/> *}
				<dc:creator>{$publication->getAuthorString($userGroups)|escape:"html"}</dc:creator>

				{foreach from=$item.identifiers item=identifier}
					{foreach from=$identifier.values item=value}
						<category domain="https://pkp.sfu.ca/ojs/category/{$identifier.type|strip|escape:"html"}">{$value|strip|escape:"html"}</category>
					{/foreach}
				{/foreach}{* categories *}
				{* <comments/> *}
				{* <source/> *}

				<dc:rights>
					{translate|escape key="submission.copyrightStatement" copyrightYear=$publication->getData('copyrightYear') copyrightHolder=$publication->getLocalizedData('copyrightHolder')}
					{$publication->getData('licenseUrl')|escape}
				</dc:rights>
				<cc:license {if $publication->getData('accessStatus') == \APP\submission\Submission::ARTICLE_ACCESS_OPEN && $publication->isCCLicense()}rdf:resource="{$publication->getData('licenseUrl')|escape}"{/if} />
				<guid isPermaLink="true">{url page="article" op="view" path=$submission->getBestId()}</guid>
				<pubDate>{$publication->getData('datePublished')|date_format:$smarty.const.DATE_RSS}</pubDate>
			</item>
		{/foreach}{* articles *}
	</channel>
</rss>
