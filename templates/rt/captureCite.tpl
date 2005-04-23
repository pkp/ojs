{**
 * captureCite.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 * $Id$
 *}

{assign var=pageTitle value="rst.captureCite"}

{include file="rt/header.tpl"}

{if $bibFormat == 'MLA'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()}, {$firstName}{if $i==$authorCount-2}, {translate key="rst.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
	{/foreach}

	"{$article->getArticleTitle()|escape}" <i>{$journal->getTitle()|escape}</i> [{translate key="rst.captureCite.online"}], {$issue->getVolume()} {$article->getDatePublished()|date_format:'%e %b %Y'}

{elseif $bibFormat == 'Turabian'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()}, {$firstName}{if $i==$authorCount-2}, {translate key="rst.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
	{/foreach}

	"{$article->getArticleTitle()|escape}" <i>{$journal->getTitle()|escape}</i> [{translate key="rst.captureCite.online"}], {translate key="issue.volume"} {$issue->getVolume()} {translate key="issue.number"} {$issue->getNumber()} ({$article->getDatePublished()|date_format:'%e %B %Y'|trim})

{elseif $bibFormat == 'CBE'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()}, {$firstName[0]}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
	{/foreach}

	{$article->getDatePublished()|date_format:'%Y %b %e'}. {$article->getArticleTitle()|escape}. {$journal->getTitle()|escape}. [{translate key="rst.captureCite.online"}] {$issue->getVolume()}:{$issue->getNumber()}

{elseif $bibFormat == 'BibTeX'}

{literal}
<pre style="font-size: 1.5em;">@article{{{/literal}{$journal->getSetting('journalInitials')}{literal}}{{/literal}{$articleId}{literal}},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{$author->getLastName()}, {assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName[0]}.{if $i<$authorCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getArticleTitle()|escape}{literal}},
	journal = {{/literal}{$journal->getTitle()|escape}{literal}},
	volume = {{/literal}{$issue->getVolume()}{literal}},
	number = {{/literal}{$issue->getNumber()}{literal}},
	year = {{/literal}{$article->getDatePublished()|date_format:'%Y'}{literal}},
{/literal}{assign var=issn value=$journal->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}{/if}{literal}
	url = {{/literal}{$pageUrl}/article/view/{$articleId}/{$galleyId}{literal}}
}
</pre>
{/literal}

{elseif $bibFormat == 'ABNT'}

	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()}, {$firstName[0]}.{if $i<$authorCount-1}; {/if}{/foreach}.
	{$article->getArticleTitle()}.
	<b>{$journal->getTitle()}</b>, {translate key="rst.captureCite.acaoLocation"}, {$issue->getVolume()}
	{$article->getDatePublished()|date_format:'%e %m %Y'}.

{else}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()}, {$firstName[0]}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
	{/foreach}

	{$article->getDatePublished()|date_format:'%Y %b %e'}.
	{$article->getArticleTitle()}.
	<i>{$journal->getTitle()}</i> [{translate key="rst.captureCite.online"}] {$issue->getVolume()}:{$issue->getNumber()}.
	{translate key="rst.captureCite.available"} <a target="_new" href="{$pageUrl}/article/view/{$articleId}/{$galleyId}">{$pageUrl}/article/view/{$articleId}/{$galleyId}</a>
{/if}

<br />
<br />

<div class="separator"></div>

<h3>{translate key="rst.captureCite.capture"}</h3>
<ul>
	<li>{translate key="rst.captureCite.capture.endNote" url="$requestPageUrl/captureCite/$articleId/$galleyId/endNote"}</li>
	<li>{translate key="rst.captureCite.capture.referenceManager" url="$requestPageUrl/captureCite/$articleId/$galleyId/referenceManager"}</li>
	<li>{translate key="rst.captureCite.capture.proCite" url="$requestPageUrl/captureCite/$articleId/$galleyId/proCite"}</li>
</ul>

{include file="rt/footer.tpl"}
