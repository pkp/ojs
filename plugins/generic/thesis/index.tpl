{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of thesis abstract titles. 
 *
 * $Id$
 *}
{assign var="pageTitle" value="plugins.generic.thesis.theses"}
{include file="common/header.tpl"}

<a name="theses"></a>

{if $thesisIntroduction != ""}
	{$thesisIntroduction|nl2br}
	<br />
	<br />
{/if}

<a href="{url op="submit"}" class="action">{translate key="plugins.generic.thesis.submitLink"}</a>

<br />
<br />

<form method="post" action="{url op="theses"}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=theses item=thesis}
	<tr valign="top">
		<td width="80%">{$thesis->getTitle()|escape}</td>
		<td width="20%" align="right"><a class="file" href="{url op="view" path=$thesis->getThesisId()}">{translate key="plugins.generic.thesis.view"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="2" style="padding-left: 30px;font-style: italic;">{$thesis->getStudentFullName(true)|escape}<br />{$thesis->getDepartment()|escape}, {$thesis->getUniversity()|escape}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $theses->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $theses->wasEmpty() and $search != ""}
	<tr>
		<td colspan="2" class="nodata">{translate key="plugins.generic.thesis.noResults"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{elseif $theses->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="plugins.generic.thesis.noneExist"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$theses}</td>
		<td align="right">{page_links anchor="theses" name="theses" iterator=$theses}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
