{**
 * citation.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation BibTeX format
 *
 * $Id$
 *}
<div class="separator"></div>
<div id="citation">
{literal}
<pre style="font-size: 1.5em; white-space: pre-wrap; white-space: -moz-pre-wrap !important; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">@article{{/literal}{$journal->getLocalizedInitials()|bibtex_escape}{$articleId|bibtex_escape}{literal},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName|bibtex_escape} {$author->getLastName()|bibtex_escape}{if $i<$authorCount-1} {translate key="common.and"} {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getLocalizedTitle()|strip_tags|bibtex_escape}{literal}},
	journal = {{/literal}{$journal->getLocalizedTitle()|bibtex_escape}{literal}},
{/literal}{if $issue}{literal}	volume = {{/literal}{$issue->getVolume()|bibtex_escape}{literal}},
	number = {{/literal}{$issue->getNumber()|bibtex_escape}{literal}},{/literal}{/if}{literal}
	year = {{/literal}{$article->getDatePublished()|date_format:'%Y'}{literal}},
	keywords = {{/literal}{$article->getLocalizedSubject()|bibtex_escape}{literal}},
	abstract = {{/literal}{$article->getLocalizedAbstract()|strip_tags:false|bibtex_escape}{literal}},
{/literal}{assign var=onlineIssn value=$journal->getSetting('onlineIssn')}
{assign var=issn value=$journal->getSetting('issn')}{if $issn}{literal}	issn = {{/literal}{$issn|bibtex_escape}{literal}},{/literal}
{elseif $onlineIssn}{literal}	issn = {{/literal}{$onlineIssn|bibtex_escape}{literal}},{/literal}{/if}
{literal}	url = {{/literal}{url|bibtex_escape page="article" op="view" path=$article->getBestArticleId()}{literal}}
}
</pre>
{/literal}
</div>
