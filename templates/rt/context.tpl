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

		// Get a list of search terms
		var elements = document.terms.elements;
		for (var i=0; i<elements.length; i++) {
			if (elements[i].type=='text') {
				var value = elements[i].value;

				if (value != '' && (i==0 || elements[i-1].type!='checkbox' || elements[i-1].checked)) {
					if (termsGet != '') {
						termsGet += '+AND+';
						termsPost += ' AND ';
					}
					termsGet += value.replace(' ','+');
					termsPost += value;
				}
			}
		}

		// Add the search terms to the action URL if necessary
		var oldAction = searchForm.action;
		searchForm.action = oldAction.replace('KEYWORDS_HERE', termsGet);

		// Add the search terms to the POST fields if necessary
		elements = searchForm.elements;
		for (var i=0; i<elements.length; i++) {
			if (elements[i].type=='hidden' && elements[i].value=='KEYWORDS_HERE') {
				elements[i].value = termsPost;
			}
		}

		// Submit the form via POST or GET as appropriate.
		if (searchForm.method=='post') searchForm.submit();
		else document.location = searchForm.action;
		return true;
	}
{/literal}
</script>

<h3>"{$article->getArticleTitle()}"</h3>

<form name="terms">

<p>{if $context->getDefineTerms()}{translate key="rt.context.defineTermsDescription"}{elseif $context->getAuthorTerms()}{translate key="rt.context.authorTermsDescription"}{else}{translate key="rt.context.searchDescription"}{/if}</p>

<table class="data" width="100%">
	{if $context->getDefineTerms()}
		<tr valign="top">
			<td width="20%" class="label">{translate key="rt.context.termToDefine"}</td>
			<td width="80%" class="value"><input name="searchTerm" value="{$defineTerm}" length="40" class="textField" />
		</tr>
	{elseif $context->getAuthorTerms()}
		{foreach from=$article->getAuthors() item=author key=key}
			<tr valign="top">
				<td width="20%" class="label" align="right">
					<input type="checkbox" checked="checked" style="checkbox" name="searchTerm{$key+1}Check" value="1" />
				</td>
				<td width="80%" class="value">
					<input name="searchTerm{$key+1}" value="{$author->getFullName()|escape}" length="40" class="textField" />
				</td>
			</tr>
		{/foreach}
	{else}
		<tr valign="top">
			<td width="20%" class="label">{translate key="rt.context.searchTerms"}</td>
			<td width="80%" class="value">
				{foreach from=$keywords item=keyword name=keywords key=key}
					<input name="searchTerm{$key+1}" value="{$keyword|trim|escape}" length="40" class="textField" />
					{if !$smarty.foreach.keywords.last}{translate key="rt.context.and"}{/if}
					<br />
				{/foreach}
			</td>
		</tr>
	{/if}
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
		<td width="88%">{$search->getTitle()} <a target="_new" href="{$search->getUrl()|escape}" class="action">{translate key="navigation.about"}</a></td>
	</tr>
	<tr><td colspan="3" class="{if $smarty.foreach.searches.last}end{/if}separator">&nbsp;</td></tr>
	</form>
	{/foreach}
</table>

{include file="rt/footer.tpl"}
