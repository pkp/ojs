{**
 * view.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v1. For full terms see the file docs/COPYING.
 *
 * View thesis abstract. 
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$thesis->getTitle()}
{include file="common/header.tpl"}

<table width="100%">
	<tr valign="top">
		<td>{$thesis->getStudentFullName()|escape}</td>
	</tr>
	<tr valign="top">
		<td>{$thesis->getDepartment()|escape}, {$thesis->getUniversity()|escape}</td>
	</tr>
	<tr valign="top">
		<td>{$thesis->getDateApproved()|date_format:"%B, %Y"}</td>
	</tr>
	<tr valign="top">
		<td><a href="{$thesis->getUrl()|escape}">{$thesis->getUrl()|escape}</a></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
	</tr>
	<tr valign="top">
		<td><h4>{translate key="plugins.generic.thesis.abstract"}</h4></td>
	</tr>
	<tr valign="top">
		<td>{$thesis->getAbstract()|strip_unsafe_html|nl2br}</td>
	</tr>
</table>

{include file="common/footer.tpl"}
