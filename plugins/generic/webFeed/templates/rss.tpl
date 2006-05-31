<?xml version="1.0" encoding="utf-8"?>
<!--<?xml version="1.0" encoding="{$defaultCharset}"?>-->
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns="http://purl.org/rss/1.0/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
   <channel rdf:about="{$journal->getUrl()}">
      <title>{$journal->getTitle()|strip_tags|escape:"html"}</title>
      <link>{url page="issue" op="current"}</link>
      {if $journal->getDescription()}
      <description>{$journal->getDescription()|strip_tags|strip|escape:"html"}</description>
      {/if}
      <dc:publisher>{$publisher|strip_tags|escape:"html"}</dc:publisher>
      <dc:language>en</dc:language>
      <prism:publicationName>{$journal->getTitle()|strip_tags|escape:"html"}</prism:publicationName>
      <prism:issn>{$journal->getSetting('issn')}</prism:issn>
      <prism:copyright>{$journal->getSetting('copyrightNotice')|strip_tags|strip|escape:"html"}</prism:copyright>
      <items>
          <rdf:Seq>
            {foreach name=sections from=$publishedArticles item=section key=sectionId}
            {foreach from=$section.articles item=article}
            <rdf:li rdf:resource="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
            {/foreach}
            {/foreach}
          </rdf:Seq>
      </items>
   </channel>
   
{foreach name=sections from=$publishedArticles item=section key=sectionId}
{foreach from=$section.articles item=article}
   <item>
      <title>{$article->getTitle()|strip_tags|escape:"html"}</title>
      {if $article->getAbstract()}
      <description>{$article->getAbstract()|strip_tags|strip|escape:"html"}</description>
      {/if}
      <link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>
      {foreach from=$article->getAuthors() item=author name=authorList}
      <dc:creator>{$author->getFullName()|strip_tags|escape:"html"}</dc:creator>
      {/foreach}
      <dc:date>{$article->getLastModified()|date_format:"%Y-%m-%d"}</dc:date>
      <prism:publicationName>{$journal->getTitle()|strip_tags|escape:"html"}</prism:publicationName>
      <prism:issn>{$journal->getSetting('issn')}</prism:issn>
      <prism:volume>{$issue->getVolume()}</prism:volume>
      <prism:startingPage>{$article->getPages()}</prism:startingPage><!--FIXME-->
      <prism:endingPage>{$article->getPages()}</prism:endingPage><!--FIXME-->
      <prism:publicationDate>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</prism:publicationDate>
   </item>
{/foreach}
{/foreach}

</rdf:RDF>

