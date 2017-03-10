{**
 * plugins/citationFormats/bibtex/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation BibTeX format
 *
 *}
<div class="separator"></div>
<div id="citation">
{literal}
<textarea style="width:100%;height:20em;font-family:monospace;font-size:0.85em;line-height:1.2em;">@article{{/literal}{$journal->getLocalizedAcronym()|bibtex_escape}{$articleId|bibtex_escape}{literal},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName|bibtex_escape} {$author->getLastName()|bibtex_escape}{if $i<$authorCount-1} {translate key="common.and"} {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getLocalizedTitle()|strip_tags|bibtex_escape}{literal}},
	journal = {{/literal}{$journal->getLocalizedName()|bibtex_escape}{literal}},
{/literal}{if $issue}{if $issue->getShowVolume()}{literal}	volume = {{/literal}{$issue->getVolume()|bibtex_escape}{literal}},
{/literal}{/if}{if $issue->getShowNumber()}{literal}	number = {{/literal}{$issue->getNumber()|bibtex_escape}{literal}},
{/literal}{/if}{/if}{literal}	year = {{/literal}{if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y'}{else}{$issue->getYear()|escape}{/if}{literal}},
	keywords = {{/literal}{$article->getLocalizedSubject()|bibtex_escape}{literal}},
	abstract = {{/literal}{$article->getLocalizedAbstract()|strip_tags:false|bibtex_escape}{literal}},
{/literal}{assign var=onlineIssn value=$journal->getSetting('onlineIssn')}
{assign var=issn value=$journal->getSetting('issn')}{if $issn}{literal}	issn = {{/literal}{$issn|bibtex_escape}{literal}},{/literal}
{elseif $onlineIssn}{literal}	issn = {{/literal}{$onlineIssn|bibtex_escape}{literal}},
{/literal}{/if}{if count($article->getPageArray()) > 0}	pages = {literal}{{/literal}{foreach from=$article->getPageArray() item=range name=pages}{$range[0]|escape}{if $range[1]}--{$range[1]|escape}{if !$smarty.foreach.pages.last},{/if}{/if}{/foreach}{literal}},
{/literal}{/if}{if $article->getStoredPubId('doi')}	doi = {ldelim}{$article->getStoredPubId('doi')|escape}{rdelim},
{/if}
	url = {ldelim}{url|bibtex_escape page="article" op="version" path=$article->getBestArticleId()|to_array:$version}{rdelim}
{rdelim}
</textarea>
</div>
