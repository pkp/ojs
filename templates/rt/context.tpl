{**
 * context.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- context view page.
 *
 * $Id$
 *}
{assign var=pageTitleTranslated value=$context->getTitle()|capitalize}
{include file="rt/header.tpl"}

<script type="text/javascript">
{literal}
<!--
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
					termsGet += value.replace(/ /g,'+');
					termsPost += value;
				}
			}
		}

		// Add the search terms to the action URL if necessary
		var newAction = searchForm.action;
		newAction = newAction.replace(/{\$formKeywords}/g, termsGet);
		{/literal}{foreach from=$searchParams item=param}{literal}
		newAction = newAction.replace(/{\${/literal}{$param|escape}{literal}}/g, document.additionalParams.{/literal}{$param|escape}{literal}.value.replace(/ /g,'+'));
		{/literal}{/foreach}{literal}
		searchForm.action = newAction;

		// Add the search terms to the POST fields if necessary
		elements = searchForm.elements;
		for (var i=0; i<elements.length; i++) {
			if (elements[i].type=='hidden') {
				elements[i].value = elements[i].value.replace(/{\$formKeywords}/g, termsPost);
				{/literal}{foreach from=$searchParams item=param}{literal}
				elements[i].value = elements[i].value.replace(/{\${/literal}{$param|escape}{literal}}/g, document.additionalParams.{/literal}{$param|escape}{literal}.value);
				{/literal}{/foreach}{literal}
			}
		}

		// Submit the form via POST or GET as appropriate.
		if (searchForm.method=='post') {
			searchForm.submit();
		} else {
			document.location = searchForm.action;
		}
		return true;
	}
// -->
{/literal}
</script>

<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>


<p>{if $context->getDefineTerms()}{translate key="rt.context.defineTermsDescription"}{elseif $context->getAuthorTerms()}{translate key="rt.context.authorTermsDescription"}{elseif $context->getCitedBy()}{translate key="rt.context.citesContextDescription}{else}{translate key="rt.context.searchDescription"}{/if}</p>

<table class="data" width="100%">
	<form name="terms">
	{if $context->getDefineTerms()}
		<tr valign="top">
			<td width="20%" class="label">{translate key="rt.context.termToDefine"}</td>
			<td width="80%" class="value"><input name="searchTerm" value="{$defineTerm|escape}" length="40" class="textField" />
		</tr>
	{elseif $context->getAuthorTerms() || $context->getCitedBy()}
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
	{elseif $context->getGeoTerms()}
		<tr valign="top">
			<td width="20%" class="label">{translate key="rt.context.termToDefine"}</td>
			<td width="80%" class="value"><input name="searchTerm" value="{$coverageGeo|escape}" length="40" class="textField" />
		</tr>
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
	</form>


	<form name="additionalParams">
	{foreach from=$searchValues key=paramKey item=value}
		<tr valign="top">
			<td width="20%" class="label">
				{if $paramKey == 'author'}{translate key="user.role.author"}
				{elseif $paramKey == 'coverageGeo'}{translate key="article.coverageGeo"}
				{elseif $paramKey == 'title'}{translate key="article.title"}
				{/if}
			</td>
			<td width="80%" class="value">
					<input name="{$paramKey|escape}" value="{$value|escape}" length="40" class="textField" />
			</td>
	{/foreach}
	</form>
</table>

<div class="separator"></div>

<table class="listing" width="100%">
	{foreach from=$searches item=search key=key name=searches}
	<form name="search{$key+1}form" method="{if $search->getSearchPost()}post{else}get{/if}" action="{$search->getSearchUrl()|escape}">
	{foreach from=$search->postParams item=postParam}
		<input type="hidden" name="{$postParam.name|escape}" value="{$postParam.value|escape}" />
	{/foreach}
	<tr valign="top">
		<td width="10%">
			<input value="{translate key="common.search"}" type="button" onclick="addKeywords({$key+2});" class="button" />
		</td>
		<td width="2%">{$key+1}.</td>
		<td width="88%">{$search->getTitle()|escape} <a target="_new" href="{$search->getUrl()|escape}" class="action">{translate key="navigation.about"}</a></td>
	</tr>
	<tr><td colspan="3" class="{if $smarty.foreach.searches.last}end{/if}separator">&nbsp;</td></tr>
	</form>
	{/foreach}
</table>

<a href="http://pkp.sfu.ca/ojs/suggestRt" target="_new" class="action">{translate key="rt.search.suggest"}</a><br/>

{include file="rt/footer.tpl"}
