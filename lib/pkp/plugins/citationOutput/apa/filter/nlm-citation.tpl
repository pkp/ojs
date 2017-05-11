{**
 * nlm-citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * APA citation output format template (NLM citation schema based)
 *
 * NB: We don't use translation here as the texts are defined in the standard.
 *}
{strip}
	<p style="text-indent:-2em;margin-left:2em">
		{assign var=mainTitle value=$nlm30Source|escape|regex_replace:'/^([^:]+:?).*$/':'$1'}
		{assign var=subTitle value=$nlm30Source|escape|regex_replace:'/^[^:]+:?/':''|@trim|@ucfirst}
		{if $nlm30PersonGroupPersonGroupTypeAuthor}
			{capture assign=authors}{include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeAuthor reversed=true}{/capture}{$authors}
		{else}{$mainTitle}{if $subTitle} {$subTitle}{/if} {/if}
		{if $nlm30Date}({$nlm30Date|truncate:4:''}){/if}
		{if $nlm30PublicationType == 'book'}
			{if $nlm30ChapterTitle}
				{literal} {/literal}{$nlm30ChapterTitle|escape}
				{if $nlm30PersonGroupPersonGroupTypeEditor}
					. In {include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeEditor}{if count($nlm30PersonGroupPersonGroupTypeEditor)>1}(Eds.), {else}(Ed.), {/if}
				{else}
					{literal}. In {/literal}
				{/if}
			{else}
				{literal}. {/literal}
			{/if}
			{if $nlm30PersonGroupPersonGroupTypeAuthor}<i>{$mainTitle}{if $subTitle} {$subTitle}{/if}</i>{/if}
			{if $nlm30ChapterTitle && $nlm30Fpage} (p{if $nlm30Lpage}p{/if}. {$nlm30Fpage}{if $nlm30Lpage}-{$nlm30Lpage}{/if}){/if}
			{if $nlm30PersonGroupPersonGroupTypeAuthor || ($nlm30ChapterTitle && $nlm30Fpage)}. {/if}
			{if $nlm30PublisherLoc}{$nlm30PublisherLoc|escape}{/if}
			{if $nlm30PublisherName}: {$nlm30PublisherName|escape}{/if}.
		{elseif $nlm30PublicationType == 'journal'}
			. {$nlm30ArticleTitle|escape}. <i>{$mainTitle}{if $subTitle} {$subTitle}{/if}, </i>
			{if $nlm30Volume}{$nlm30Volume|escape}{/if}
			{if $nlm30Issue}{if $nlm30Volume}({$nlm30Issue|escape}){else}{$nlm30Issue|escape}{/if}{/if}
			{if $nlm30Volume || $nlm30Issue}, {/if}
			{if $nlm30Fpage}{$nlm30Fpage}{if $nlm30Lpage}-{$nlm30Lpage}{/if}.{/if}
		{elseif $nlm30PublicationType == 'conf-proc'}
			. <i>{$nlm30ArticleTitle|escape}. </i>
			Paper presented at {$nlm30ConfName|escape}{if $nlm30ConfSponsor} of {$nlm30ConfSponsor|escape}{/if}
			{if $nlm30ConfLoc}, {$nlm30ConfLoc|escape}{/if}.
		{/if}
		{if $nlm30Uri} Retrieved from {$nlm30Uri|escape}{/if}
		{if $nlm30PubIdPubIdTypePmid} pmid:{$nlm30PubIdPubIdTypePmid}{/if}
		{if $nlm30PubIdPubIdTypeDoi} doi:{$nlm30PubIdPubIdTypeDoi|escape}{/if} <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q={if count($nlm30PersonGroupPersonGroupTypeAuthor)}author:%22{$nlm30PersonGroupPersonGroupTypeAuthor[0]->getStatement('surname')|escape:'url'}%22+{/if}%22{if $nlm30ConfName}{$nlm30ConfName|escape:'url'}{else}{$nlm30Source|escape:'url'}{/if}%22+{$nlm30ArticleTitle|escape:'url'}{if $nlm30PubIdPubIdTypeDoi}+{$nlm30PubIdPubIdTypeDoi|escape:'url'}{/if}" target="_blank">{$smarty.const.GOOGLE_SCHOLAR_TAG}</a>
	</p>
{/strip}
