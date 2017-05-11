{**
 * nlm-citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Vancouver citation output format template (NLM citation schema based)
 *}
{strip}
	<p>
		{assign var=mainTitle value=$nlm30Source|escape|regex_replace:'/^([^:]+:?).*$/':'$1'}
		{assign var=subTitle value=$nlm30Source|escape|regex_replace:'/^[^:]+:?/':''|@trim|@ucfirst}
		{include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeAuthor editor=false}
		{if $nlm30PublicationType == 'book'}
			{if $nlm30ChapterTitle}{$nlm30ChapterTitle|escape}. In: {/if}
			{$mainTitle}{if $subTitle} {$subTitle}{/if}.{literal} {/literal}
			{if $nlm30ChapterTitle}
				{if $nlm30PersonGroupPersonGroupTypeEditor}
					{include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeEditor editor=true}
					, editor{if count($nlm30PersonGroupPersonGroupTypeEditor) >1}s. {else}. {/if}
				{/if}
			{/if}
			{if $nlm30Edition}{$nlm30Edition}. {/if}
			{if $nlm30PublisherLoc}{$nlm30PublisherLoc|escape}: {/if}
			{if $nlm30PublisherName}{$nlm30PublisherName|escape}; {/if}
			{if $nlm30Date}{$nlm30Date|truncate:4:''}{/if}
			{if $nlm30ChapterTitle}
				{if $nlm30Fpage}. p. {$nlm30Fpage}{if $nlm30Lpage}-{$nlm30Lpage}{/if}{/if}
			{/if}
		{elseif $nlm30PublicationType == 'journal'}
			{$nlm30ArticleTitle}. {$mainTitle}{if $subTitle} {$subTitle}{/if}{if strlen($nlm30Date)>4} {$nlm30Date|date_format:'%Y %b'}{elseif strlen($nlm30Date)==4} {$nlm30Date|cat:'-01'|date_format:'%Y'}{/if}
			{if $nlm30Volume};{$nlm30Volume}{if $nlm30Issue}({$nlm30Issue}){/if}{/if}
			{if $nlm30Fpage}:{$nlm30Fpage}{if $nlm30Lpage}-{$nlm30Lpage}{/if}{/if}
		{elseif $nlm30PublicationType == 'conf-proc'}
			{$nlm30ArticleTitle}. Proceedings of {$nlm30ConfName|escape}. {if $nlm30ConfSponsor}{$nlm30ConfSponsor|escape}. {/if}
			{if $nlm30ConfLoc}{$nlm30ConfLoc}: {/if}{if $nlm30Date}{$nlm30Date|truncate:4:''}{/if}
		{/if}
		{if strlen($nlm30DateInCitationContentTypeAccessDate) > 4} [cited {$nlm30DateInCitationContentTypeAccessDate|date_format:'%Y %b %e'}]{/if}.
		{if $nlm30Uri} Available from: {$nlm30Uri}{/if}
		{if $nlm30PubIdPubIdTypePmid} PubMed PMID: {$nlm30PubIdPubIdTypePmid}.{/if}
		{if $nlm30PubIdPubIdTypeDoi} doi: {$nlm30PubIdPubIdTypeDoi}.{/if} <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q={if count($nlm30PersonGroupPersonGroupTypeAuthor)}author:%22{$nlm30PersonGroupPersonGroupTypeAuthor[0]->getStatement('surname')|escape:'url'}%22+{/if}%22{if $nlm30ConfName}{$nlm30ConfName|escape:'url'}{else}{$nlm30Source|escape:'url'}{/if}%22+{$nlm30ArticleTitle|escape:'url'}{if $nlm30PubIdPubIdTypeDoi}+{$nlm30PubIdPubIdTypeDoi|escape:'url'}{/if}" target="_blank">{$smarty.const.GOOGLE_SCHOLAR_TAG}</a>
	</p>
{/strip}
