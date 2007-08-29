{**
 * citation.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 * $Id$
 *}

<div class="separator"></div>

<form action="http://www.refworks.com/express/expressimport.asp?vendor=Public%20Knowledge%20Project&filter=BibTeX&encoding=65001" method="post" target="RefWorksMain">
	<textarea name="ImportData" rows=15 cols=70>{literal}@article{{{/literal}{$journal->getJournalInitials()|escape}{literal}}{{/literal}{$articleId|escape}{literal}},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{$author->getLastName()|escape}, {assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName[0]|escape}.{if $i<$authorCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getArticleTitle()|strip_unsafe_html}{literal}},
	journal = {{/literal}{$journal->getJournalTitle()|escape}{literal}},
{/literal}{if $issue}{literal}	volume = {{/literal}{$issue->getVolume()|escape}{literal}},
	number = {{/literal}{$issue->getNumber()|escape}{literal}},{/literal}{/if}{literal}
	year = {{/literal}{$article->getDatePublished()|date_format:'%Y'}{literal}},
{/literal}{assign var=issn value=$journal->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}{/if}

{literal}	url = {{/literal}{$articleUrl}{literal}}
}{/literal}</textarea>
	<br />
	<input type="submit" class="button defaultButton" name="Submit" value="{translate key="plugins.citationFormats.refWorks.export"}" />
</form>
