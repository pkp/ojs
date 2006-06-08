<?xml version="1.0" encoding="{$defaultCharset}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$journal->getTitle()|strip_tags|escape:"html"}</title>
		<link>{$journal->getUrl()}</link>
		{if $journal->getDescription()}
			{assign var="description" value=$journal->getDescription()}
		{elseif $journal->getSetting('journalDescription')}
			{assign var="description" value=$journal->getSetting('journalDescription')}
		{elseif $journal->getSetting('searchDescription')}
			{assign var="description" value=$journal->getSetting('searchDescription')}
		{/if}
		<description>{$description|strip_tags|strip|escape:"html"}</description>

		{* optional elements *}
	    {if $journal->getLocale()}
	    <language>{$journal->getLocale()|replace:'_':'-'|strip_tags|escape:"html"}</language>
	    {/if}
	    {if $journal->getSetting('copyrightNotice')}
	    <copyright>{$journal->getSetting('copyrightNotice')|strip_tags|strip|escape:"html"}</copyright>
	    {/if}
	    {if $journal->getSetting('contactEmail')}
		<managingEditor>{$journal->getSetting('contactEmail')|strip_tags|strip|escape:"html"}{if $journal->getSetting('contactName')} ({$journal->getSetting('contactName')|strip_tags|strip|escape:"html"}){/if}</managingEditor>
		{/if}
	    {if $journal->getSetting('supportEmail')}
		<webMaster>{$journal->getSetting('supportEmail')|strip_tags|strip|escape:"html"}{if $journal->getSetting('contactName')} ({$journal->getSetting('supportName')|strip_tags|strip|escape:"html"}){/if}</webMaster>
		{/if}
		<pubDate>{$issue->getDatePublished()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
		{* <lastBuildDate/> *}
		{* <category/> *}
		{* <creativeCommons:license/> *}
		<generator>OJS {$ojsVersion}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach name=sections from=$publishedArticles item=section key=sectionId}
		{foreach from=$section.articles item=article}
			<item>
				{* required elements *}
				<title>{$article->getArticleTitle()|strip_tags|escape:"html"}</title>
				<link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>
				<description>{$article->getAbstract()|strip|escape:"html"}</description>

				{* optional elements *}
		        {foreach from=$article->getAuthors() item=author name=authorList}
		        	{if $author->getPrimaryContact()}
        		    <author>{$author->getEmail()|strip_tags|escape:"html"} ({$author->getFullName()|strip_tags|escape:"html"})</author>
        		    {/if}
				{/foreach}
				{* <category/> *}
				{* <comments/> *}
				{* <source/> *}
				<guid isPermaLink="true">{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</guid>
				<pubDate>{$article->getDatePublished()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
			</item>
		{/foreach}
		{/foreach}
	</channel>
</rss>
