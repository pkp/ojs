{**
 * plugins/generic/thesis/view.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v1. For full terms see the file docs/COPYING.
 *
 * View thesis abstract.
 *
 *}
{strip}
{assign var="pageCrumbTitleTranslated" value=$thesis->getStudentLastName()}
{assign var="pageTitleTranslated" value=$thesis->getTitle()}
{include file="common/header.tpl"}
{/strip}

<table>
	<tr>
		<td>{$thesis->getStudentFullName()|escape}{if $thesis->getStudentEmailPublish()} ({$thesis->getStudentEmail()|escape}){/if}</td>
	</tr>
	<tr>
		<td>{$thesis->getDepartment()|escape}, {$thesis->getUniversity()|escape}</td>
	</tr>
	<tr>
		<td>{$thesis->getDateApproved()|date_format:"%B, %Y"}</td>
	</tr>
	{if $thesis->getUrl()}
	<tr>
		<td><a href="{$thesis->getUrl()|escape}">{translate key="plugins.generic.thesis.fullText"}</a></td>
	</tr>
	{/if}
	<tr>
		<td>&nbsp;</td>
	</tr>
	{if $thesis->getStudentBio()}
	<tr>
		<td>{$thesis->getStudentBio()|strip_unsafe_html|nl2br}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	{/if}
	<tr>
		<td><h4>{translate key="plugins.generic.thesis.abstract"}</h4></td>
	</tr>
	<tr>
		<td>{$thesis->getAbstract()|strip_unsafe_html|nl2br}</td>
	</tr>
</table>

{include file="common/footer.tpl"}
