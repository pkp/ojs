{**
 * nlm-citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ABNT citation output format template (NLM citation schema based)
 *}
{strip}
	<p>
		{assign var=mainTitle value=$nlm30Source|escape|regex_replace:'/^([^:]+:?).*$/':'$1'}
		{assign var=subTitle value=$nlm30Source|escape|regex_replace:'/^[^:]+:?/':''|@trim|@ucfirst}
		{include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeAuthor}
		{if $nlm30PublicationType == 'book'}
			{if $nlm30ChapterTitle}
				{$nlm30ChapterTitle|escape}
				{if $nlm30PersonGroupPersonGroupTypeEditor}
					. In: {include file="nlm-citation-persons.tpl" persons=$nlm30PersonGroupPersonGroupTypeEditor}{literal}(Ed.). {/literal}
				{else}
					{literal}. In: ________. {/literal}
				{/if}
			{/if}
			<i>{$mainTitle}</i>{if $subTitle} {$subTitle}{/if}.
			{if $nlm30PublisherLoc} {$nlm30PublisherLoc|escape}:{/if}
			{if $nlm30PublisherName} {$nlm30PublisherName|escape},{/if}
			{if $nlm30Date} {$nlm30Date|truncate:4:''}.{/if}
			{if $nlm30Size} {$nlm30Size} p.{/if}
			{if $nlm30Series} ({$nlm30Series|escape}{if $nlm30Volume}, v.{$nlm30Volume|escape}{/if}){/if}
		{elseif $nlm30PublicationType == 'journal'}
			{$nlm30ArticleTitle}. <i>{$mainTitle}</i>{if $subTitle} {$subTitle}{/if}, {if $nlm30PublisherLoc|escape}{$nlm30PublisherLoc|escape},{/if}
			{if $nlm30Volume} v.{$nlm30Volume|escape},{/if}
			{if $nlm30Issue} n.{$nlm30Issue|escape},{/if}
			{if $nlm30Fpage} p.{$nlm30Fpage}{if $nlm30Lpage}-{$nlm30Lpage}{/if},{/if}
			{if strlen($nlm30Date)>4}{$nlm30Date|date_format:' %b %Y'|lower}{elseif strlen($nlm30Date)==4}{$nlm30Date|cat:'-01'|date_format:' %Y'|lower}{/if}.
		{/if}
		{if $nlm30PubIdPubIdTypePmid} pmid:{$nlm30PubIdPubIdTypePmid}.{/if}
		{if $nlm30PubIdPubIdTypeDoi} doi:{$nlm30PubIdPubIdTypeDoi}.{/if} <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q={if count($nlm30PersonGroupPersonGroupTypeAuthor)}author:%22{$nlm30PersonGroupPersonGroupTypeAuthor[0]->getStatement('surname')|escape:'url'}%22+{/if}%22{if $nlm30ConfName}{$nlm30ConfName|escape:'url'}{else}{$nlm30Source|escape:'url'}{/if}%22+{$nlm30ArticleTitle|escape:'url'}{if $nlm30PubIdPubIdTypeDoi}+{$nlm30PubIdPubIdTypeDoi|escape:'url'}{/if}" target="_blank">{$smarty.const.GOOGLE_SCHOLAR_TAG}</a>
	</p>
{/strip}
