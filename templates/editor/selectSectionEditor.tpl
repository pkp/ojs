{**
 * selectSectionEditor.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List copyeditors and give the ability to select a copyeditor.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

{assign var="start" value="A"|ord}

<h3>{translate key="editor.article.selectSectionEditor"}</h3>

<form name="submit" method="post" action="{$requestPageUrl}/assignEditor/{$articleId}">
	<select name="searchField" class="selectMenu">
		{html_options_translate options=$fieldOptions}
	</select>
	<select name="searchMatch" class="selectMenu">
		<option value="contains">{translate key="form.contains"}</option>
		<option value="is">{translate key="form.is"}</option>
	</select>
	<input type="text" name="search" class="textField">&nbsp;<input type="submit" value="{translate key="common.search"}" class="button">&nbsp;&nbsp;{section loop=26 name=letters}<a href="{$requestPageUrl}/assignEditor/{$articleId}?search_initial={$smarty.section.letters.index+$start|chr}">{$smarty.section.letters.index+$start|chr}</a>{/section}
</form>
<br/>

<table width="100%" class="listing">
<tr><td colspan="2" class="headseparator"></tr>
<tr valign="top">
	<td class="heading" width="80%">{translate key="user.name"}</td>
	<td class="heading" width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="2" class="headseparator"></tr>
{foreach from=$sectionEditors item=sectionEditor name=editors}
<tr valign="top">
	<td><a class="action" href="{$pageUrl}/userProfile/{$sectionEditor->getUserId()}">{$sectionEditor->getFullName()}</a></td>
	<td><a class="action" href="{$pageUrl}/editor/assignEditor/{$articleId}/{$sectionEditor->getUserId()}">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="2" class="{if $smarty.foreach.editors.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="2" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
