{**
 * theses.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of thesis abstracts in plugin management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.thesis.manager.theses"}
{include file="common/header.tpl"}
{/strip}

<a name="theses"></a>

<ul class="menu">
	<li class="current"><a href="{plugin_url path="theses"}">{translate key="plugins.generic.thesis.manager.theses"}</a></li>
	<li><a href="{plugin_url path="settings"}">{translate key="plugins.generic.thesis.manager.settings"}</a></li>
</ul>

<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" action="{plugin_url path="theses"}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<br/>
	{translate key="plugins.generic.thesis.manager.dateApproved"}
	{translate key="common.between"}
	{html_select_date prefix="dateFrom" time=$dateFrom all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="$yearOffsetPast"}
	{translate key="common.and"}
	{html_select_date prefix="dateTo" time=$dateTo all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="$yearOffsetPast"}
	<input type="hidden" name="dateToHour" value="23" />
	<input type="hidden" name="dateToMinute" value="59" />
	<input type="hidden" name="dateToSecond" value="59" />
	<br/>
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%">{translate key="plugins.generic.thesis.manager.status"}</td>
		<td width="15%">{translate key="plugins.generic.thesis.manager.dateApproved"}</td>
		<td width="20%">{translate key="plugins.generic.thesis.manager.studentName"}</td>
		<td width="40%">{translate key="plugins.generic.thesis.manager.title"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=theses item=thesis}
	<tr valign="top">
		<td>{translate key=$thesis->getStatusString()}</td>
		<td>{$thesis->getDateApproved()|date_format:$dateFormatShort}</td>
		<td>{$thesis->getStudentFullName()|escape}</td>
		<td>{$thesis->getTitle()|escape}</td>
		<td><a href="{plugin_url path="edit" id=$thesis->getThesisId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{plugin_url path="delete" id=$thesis->getThesisId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.thesis.manager.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $theses->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $theses->wasEmpty() and $search != ""}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.thesis.manager.noResults"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{elseif $theses->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.thesis.manager.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$theses}</td>
		<td colspan="3" align="right">{page_links anchor="theses" name="theses" iterator=$theses}</td>
	</tr>
{/if}
</table>

<a href="{plugin_url path="create"}" class="action">{translate key="plugins.generic.thesis.manager.create"}</a>

{include file="common/footer.tpl"}
