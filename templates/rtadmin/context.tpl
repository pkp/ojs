{**
 * context.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin context editing
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.contexts.edit.editContext"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/editContext/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="rt.admin.contexts.metadata"}</a></li>
	<li><a href="{$requestPageUrl}/searches/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="rt.searches"}</a></li>
</ul>

<br />

<form action="{$requestPageUrl}/saveContext/{$version->getVersionId()}/{$context->getContextId()}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="title">{translate key="rt.context.title"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="title" id="title" value="{$context->getTitle()|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="abbrev">{translate key="rt.context.abbrev"}</label></td>
		<td class="value"><input type="text" class="textField" name="abbrev" id="abbrev" value="{$context->getAbbrev()|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="order">{translate key="rt.context.order"}</label></td>
		<td class="value"><input type="text" class="textField" name="order" id="order" value="{$context->getOrder()|escape}" size="5" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="description">{translate key="rt.context.description"}</label></td>
		<td class="value">
			<textarea class="textArea" name="description" id="description" rows="5" cols="60">{$context->getDescription()|escape}</textarea>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="rt.admin.contexts.options"}</label></td>
		<td class="value">
			<table width="100%" class="data">
				<tr valign="top">
					<td width="3%"><input type="checkbox" name="authorTerms" id="authorTerms" {if $context->getAuthorTerms()}checked="checked"{/if} /></td>
					<td><label for="authorTerms">{translate key="rt.admin.contexts.options.authorTerms"}</label></td>
				</tr>
				<tr valign="top">
					<td><input type="checkbox" name="defineTerms" id="defineTerms" {if $context->getDefineTerms()}checked="checked"{/if} /></td>
					<td><label for="defineTerms">{translate key="rt.admin.contexts.options.defineTerms" requestPageUrl=$requestPageUrl}</label></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/contexts/{$version->getVersionId()}" /></p>

</form>

{include file="common/footer.tpl"}
