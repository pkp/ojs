{**
 * index.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 * $Id: index.tpl,v 1.13 2010/01/21 18:52:12 asmecher Exp $
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.sword.displayName"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementsByName("articleId[]");
	for (var i=0; i < elements.length; i++) {
			elements[i].checked = !elements[i].checked;
	}
}
// -->
{/literal}
</script>

<br/>
<form action="{plugin_url path="deposit"}" method="post" name="articles">

<div id="settings">
<table width="100%" class="data">
	<tr valign="top">
		<td class="label"><label for="swordUrl">{translate key="plugins.importexport.sword.depositUrl"}</label></td>
		<td class="value"><input type="text" id="swordUrl" name="swordUrl" value="{$swordUrl|escape}" size="50" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="swordUsername">{translate key="user.username"}</label></td>
		<td class="value"><input type="text" id="swordUsername" name="swordUsername" value="{$swordUsername|escape}" size="20" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="swordPassword">{translate key="user.password"}</label></td>
		<td class="value"><input type="password" id="swordPassword" name="swordPassword" value="{$swordPassword|escape}" size="20" /></td>
	</tr>
</table>
</div>

<div id="articles">
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="30%">{translate key="issue.issue"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="25%">{translate key="article.authors"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=articles item=articleData}
	{assign var=article value=$articleData.article}
	{assign var=issue value=$articleData.issue}
	<tr valign="top">
		<td><input type="checkbox" name="articleId[]" value="{$article->getId()}"/></td>
		<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$article->getLocalizedTitle()|strip_unsafe_html}</td>
		<td>{$article->getAuthorString()|escape}</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $articles->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $articles->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$articles}</td>
		<td colspan="3" align="right">{page_links anchor="articles" name="articles" iterator=$articles swordUrl=$swordUrl swordUsername=$swordUsername swordPassword=$swordPassword}</td>
	</tr>
{/if}
</table>
<p><input type="submit" value="{translate key="plugins.importexport.sword.deposit"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
</form>
</div>

{include file="common/footer.tpl"}
