{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- article metadata page.
 *
 * $Id$
 *}

{assign var=pageTitleTranslated value=$context->getTitle()}

{include file="rt/header.tpl"}

<script type="text/javascript">
{literal}
	function addKeywords(formIndex) {
		var termsGet = '';
		var termsPost = '';

		var searchForm = document.forms[formIndex];

		var elements = document.terms.elements;
		for (var i=0; i<elements.length; i++) {
			if (elements[i].type=='text') {
				var value = elements[i].value;
				if (value != '') {
					if (termsGet != '') {
						termsGet += '+';
						termsPost += ' ';
					}
					termsGet += value.replace(' ','+');
					termsPost += value;
				}
			}
		}
		var oldAction = searchForm.action;
		searchForm.action = oldAction.replace('KEYWORDS_HERE', termsGet);

		elements = searchForm.elements;
		for (var i=0; i<elements.length; i++) {
			if (elements[i].type=='hidden' && elements[i].value=='KEYWORDS_HERE') {
				elements[i].value = termsPost;
			}
		}

		if (searchForm.method=='post') searchForm.submit();
		else document.location = searchForm.action;
		return true;
	}
{/literal}
</script>

<h3>{$article->getArticleTitle()}</h3>

<form name="terms">

<p>{if $context->getDefineTerms()}{translate key="rst.context.defineTermsDescription"}{else}{translate key="rst.context.searchDescription"}{/if}</p>

<table class="data" width="100%">
	<tr valign="top">
		{if $context->getDefineTerms()}
			<td width="20%" class="label">{translate key="rst.context.termToDefine"}</td>
			<td width="80%" class="value"><input name="searchTerm" value="{$defineTerm}" length="40" class="textField" />
		{else}
			<td width="20%" class="label">{translate key="rst.context.searchTerms"}</td>
			<td width="80%" class="value">
				{foreach from=$keywords item=keyword name=keywords}
					<input name="searchTerm[]" value="{$keyword|trim|escape}" length="40" class="textField" />
					{if !$smarty.foreach.keywords.last}{translate key="rst.context.and"}{/if}
					<br />
				{/foreach}
			</td>
		{/if}
	</tr>
</table>

</form>

<div class="separator"></div>

<table class="listing" width="100%">
	{foreach from=$searches item=search key=key name=searches}
	<form name="search{$key+1}form" {if $search->getSearchPost()}method="post" {/if}action="{$search->getSearchUrl()|escape}{if $search->urlNeedsKeywords}KEYWORDS_HERE{/if}">
	{foreach from=$search->postParams item=postParam}
		<input type="hidden" name="{$postParam.name}" value="{if $postParam.needsKeywords}KEYWORDS_HERE{else}{$postParam.value}{/if}" />
	{/foreach}
	<tr valign="top">
		<td width="10%">
			<input value="{translate key="common.search"}" type="button" onClick="addKeywords({$key+1});" class="button" />
		</td>
		<td width="2%">{$key+1}.</td>
		<td width="88%"><a target="_new" href="{$search->getUrl()|escape}">{$search->getTitle()}</a></td>
	</tr>
	<tr><td colspan="3" class="{if $smarty.foreach.searches.last}end{/if}separator"></td></tr>
	</form>
	{/foreach}
</table>


<a href="javascript:window.close()">{translate key="common.close"}</a>

{include file="rt/footer.tpl"}
