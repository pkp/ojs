{**
 * plugins/generic/openAIRE/projectIDSearchResults.tpl
 *
 * Copyright (c) 2015-2017 University of Pittsburgh
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contributed by 4Science (http://www.4science.it).
 *
 * openAIRE projects search results
 *
 *}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<title>{translate key='plugins.generic.openAIRE.searchPageTitle'}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />

	{if $displayFavicon}<link rel="icon" href="{$faviconDir}/{$displayFavicon.uploadName|escape:"url"}" type="{$displayFavicon.mimeType|escape}" />{/if}

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/compiled.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/comments.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<!-- Base Jquery -->
	{if $allowCDN}<script type="text/javascript" src="//www.google.com/jsapi"></script>
	<script type="text/javascript">{literal}
		// Provide a local fallback if the CDN cannot be reached
		if (typeof google == 'undefined') {
			document.write(unescape("%3Cscript src='{/literal}{$baseUrl}{literal}/lib/pkp/js/lib/jquery/jquery.min.js' type='text/javascript'%3E%3C/script%3E"));
			document.write(unescape("%3Cscript src='{/literal}{$baseUrl}{literal}/lib/pkp/js/lib/jquery/plugins/jqueryUi.min.js' type='text/javascript'%3E%3C/script%3E"));
		} else {
			google.load("jquery", "{/literal}{$smarty.const.CDN_JQUERY_VERSION}{literal}");
			google.load("jqueryui", "{/literal}{$smarty.const.CDN_JQUERY_UI_VERSION}{literal}");
		}
	{/literal}</script>
	{else}
	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/lib/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jqueryUi.min.js"></script>
	{/if}

	<!-- Compiled scripts -->
	{if $useMinifiedJavaScript}
		<script type="text/javascript" src="{$baseUrl}/js/pkp.min.js"></script>
	{else}
		{include file="common/minifiedScripts.tpl"}
	{/if}

	{$additionalHeadData}
</head>

{literal}
	<script type="text/javascript">
		$(document).ready(function(){
			$("#openaireSearchResultsSubmit").click(function(event) {
				event.preventDefault();
				var projectSelected = $.parseJSON($("input[name=project]:checked").val());
				if (projectSelected != null) {
					opener.document.getElementById("projectID").value = projectSelected[0].projectID;
					opener.document.getElementById("projectTitle").value = (projectSelected[0].title)?projectSelected[0].title:'';
					opener.document.getElementById("projectFunder").value = (projectSelected[0].funder)?projectSelected[0].funder:'';
					opener.document.getElementById("projectFundingProgram").value = (projectSelected[0].fundingProgram)?projectSelected[0].fundingProgram:'';
					window.close();
				}
			}); 
			
			$("#openaireSearchResultsBack").click(function(event) {
				document.location.href = "{/literal}{url|escape:"javascript" page="openaireapi" op="searchProject" targetOp="form" escape=false}{literal}";
			});
		});
	</script>
{/literal}

<h3>{translate key='plugins.generic.openAIRE.searchPageTitle'}</h3>
<div id="content">
	<p>{translate key='plugins.generic.openAIRE.searchResultsList'}</p>
	<form action="#" method="post" id="issuesForm">
		<input type="hidden" name="target" value="issue" />
		<table width="100%" class="listing">
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>
			<tr class="heading" valign="top">
				<td width="5%">&nbsp;</td>
				<td width="20%">{translate key="plugins.generic.openAIRE.projectID"}</td>
				<td width="55%">{translate key="plugins.generic.openAIRE.projectTitle"}</td>
				<td width="10%">{translate key="plugins.generic.openAIRE.projectFunder"}</td>
				<td width="10%">{translate key="plugins.generic.openAIRE.projectFundingProgram"}</td>
			</tr>
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>

			{iterate from=openaireSearchResults item=project}
				<tr valign="top">
					<td><input type="radio" name="project" value='[{$project|@json_encode}]'/></td>
					<td>{$project.projectID|escape}</td>
					<td>{$project.title|escape}</td>
					<td>{$project.funder|escape}</td>
					<td>{$project.fundingProgram|escape}</td>
				</tr>
				<tr>
					<td colspan="5" class="separator">&nbsp;</td>
				</tr>
			{/iterate}
			{if $openaireSearchResults->wasEmpty()}
				<tr valign="top">
					<td colspan="5">{translate key='plugins.generic.openAIRE.noResults'}</td>
				</tr>
				<tr>
					<td colspan="5" class="endseparator">&nbsp;</td>
				</tr>
			{else}
				<tr>
					<td colspan="2" align="left">{page_info iterator=$openaireSearchResults}</td>
					<td colspan="3" align="right">{page_links anchor="projects" name="projects" targetOp="search" searchBy=$searchType searchValue=$searchValue iterator=$openaireSearchResults}</td>
				</tr>
			{/if}
		</table>
		<p>
			<input id="openaireSearchResultsSubmit" type="submit" value="{translate key='plugins.generic.openAIRE.submitAction'}" class="button defaultButton">            
			<input id="openaireSearchResultsBack" type="button" value="{translate key='common.back'}" class="button">
			<input type="button" value="{translate key='common.close'}" class="button" onclick="window.close();">
		</p>
	</form>
</div>
