{$body}

{$journal->getTitle()}
{$issue->getIssueIdentification()}
{translate key="issue.toc"}
{url page="issue" op="view" path=$issue->getBestIssueId()}

{foreach name=sections from=$publishedArticles item=section key=sectionId}
{if $section.title}{$section.title}{/if}

--------
{foreach from=$section.articles item=article}
{$article->getArticleTitle()|strip_tags}{if $article->getPages()} ({$article->getPages()}){/if}

{foreach from=$article->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}


{/foreach}

{/foreach}
{literal}{$templateSignature}{/literal}
