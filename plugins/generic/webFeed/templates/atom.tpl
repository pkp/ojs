<?xml version="1.0" encoding="utf-8"?>
<!--<?xml version="1.0" encoding="{$defaultCharset}"?>-->
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$journal->getTitle()|strip_tags|escape:"html"}</title>
	<link rel="alternate" href="{$journal->getUrl()}" />
	<link rel="self" type="application/atom+xml" href="{$journal->getUrl()}/issue/feed" />
    {if $journal->getDescription()}
	<subtitle>{$journal->getDescription()|strip_tags|strip|escape:"html"}</subtitle>
    {/if}
	<updated>{$issue->getDatePublished()|date_format:"%Y-%m-%dT%H:%M:%SZ"}</updated>
    <id>{$journal->getUrl()}/issue/feed</id>

{foreach name=sections from=$publishedArticles item=section key=sectionId}
{foreach from=$section.articles item=article}
	<entry>
	  	<author>
        {foreach from=$article->getAuthors() item=author name=authorList}
            <name>{$author->getFullName()|strip_tags|escape:"html"}</name>
		{/foreach}
        </author>
		<title>{$article->getArticleTitle()|strip_tags|escape:"html"}</title>
		<link rel="alternate" href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}" />
		<id>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</id>
		<updated>{$article->getLastModified()|date_format:"%Y-%m-%dT%H:%M:%SZ"}</updated>
        {if $article->getAbstract()}
		<content type="html" xml:base="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}">&lt;p&gt;{$article->getAbstract()|strip|escape:"html"}&lt;/p&gt;</content>
        {/if}
	</entry>
{/foreach}
{/foreach}

</feed>
