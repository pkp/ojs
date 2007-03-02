<?xml version="1.0" encoding="{$defaultCharset}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$journal->getTitle()|escape:"html"|strip|strip_tags}</title>
		<link>{$journal->getUrl()}</link>
		{if $journal->getDescription()}
			{assign var="description" value=$journal->getDescription()}
		{elseif $journal->getSetting('journalDescription')}
			{assign var="description" value=$journal->getSetting('journalDescription')}
		{elseif $journal->getSetting('searchDescription')}
			{assign var="description" value=$journal->getSetting('searchDescription')}
		{/if}
		<description>{$description|escape:"html"|strip|strip_tags}</description>

		{* optional elements *}
	    {if $journal->getLocale()}
	    <language>{$journal->getLocale()|replace:'_':'-'|escape:"html"|strip|strip_tags}</language>
	    {/if}
	    {if $journal->getSetting('copyrightNotice')}
	    <copyright>{$journal->getSetting('copyrightNotice')|escape:"html"|strip|strip_tags}</copyright>
	    {/if}
	    {if $journal->getSetting('contactEmail')}
		<managingEditor>{$journal->getSetting('contactEmail')|escape:"html"|strip|strip_tags}{if $journal->getSetting('contactName')} ({$journal->getSetting('contactName')|escape:"html"|strip|strip_tags}){/if}</managingEditor>
		{/if}
	    {if $journal->getSetting('supportEmail')}
		<webMaster>{$journal->getSetting('supportEmail')|escape:"html"|strip|strip_tags}{if $journal->getSetting('contactName')} ({$journal->getSetting('supportName')|escape:"html"|strip|strip_tags}){/if}</webMaster>
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
				<title>{$article->getArticleTitle()|escape:"html"|strip|strip_tags}</title>
				<link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>
				<description>{$article->getAbstract()|escape:"html"|strip|strip_tags}</description>

				{* optional elements *}
		        {foreach from=$article->getAuthors() item=author name=authorList}
		        	{if $author->getEmail()}
        		    <author>{$author->getEmail()|escape:"html"|strip|strip_tags} ({$author->getFullName()|escape:"html"|strip|strip_tags})</author>
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
