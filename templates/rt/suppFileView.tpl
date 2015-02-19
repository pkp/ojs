{**
 * templates/rt/suppFileView.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read-only view of supplementary file information.
 *
 *}
{strip}
{assign var="pageTitle" value="article.suppFile"}
{include file="rt/header.tpl"}
{/strip}

<div id="suppFileView">
<h3>{$article->getLocalizedTitle()|strip_unsafe_html}</h3>

<br />

<div id="supplementarFileData">
<h3>{translate key="author.submit.supplementaryFileData"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.title"}</td>
		<td width="80%" class="value">{$suppFile->getSuppFileTitle()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="author.submit.suppFile.createrOrOwner"}</td>
		<td class="value">{$suppFile->getSuppFileCreator()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.subject"}</td>
		<td class="value">{$suppFile->getSuppFileSubject()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.type"}</td>
		<td class="value">{$suppFile->getType()|escape|default:$suppFile->getSuppFileTypeOther()|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="author.submit.suppFile.briefDescription"}</td>
		<td class="value">{$suppFile->getSuppFileDescription()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.publisher"}</td>
		<td class="value">{$suppFile->getSuppFilePublisher()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="author.submit.suppFile.contributorOrSponsor"}</td>
		<td class="value">{$suppFile->getSuppFileSponsor()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.date"}</td>
		<td class="value">{$suppFile->getDateCreated()|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.source"}</td>
		<td class="value">{$suppFile->getSuppFileSource()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.language"}</td>
		<td class="value">{$suppFile->getLanguage()|escape|default:"&mdash;"}</td>
	</tr>
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{if $issue->getPublished()}
			{assign var=pubId value=$pubIdPlugin->getPubId($suppFile)}
		{else}
			{assign var=pubId value=$pubIdPlugin->getPubId($suppFile, true)}{* Preview rather than assign a pubId *}
		{/if}
		{if $pubId}
			<tr valign="top">
				<td>{$pubIdPlugin->getPubIdFullName()|escape}</td>
				<td><a target="_new" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}</a></td>
			</tr>
		{/if}
	{/foreach}
</table>
</div>

<div class="separator"></div>

<div id="supplementaryFileUpload">
<h3>{translate key="author.submit.supplementaryFileUpload"}</h3>

<table width="100%" class="data">
{if $suppFile}
{if $suppFile->getRemoteURL()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.layout.galleyRemoteURL"}</td>
		<td width="80%" class="value"><a href="{$suppFile->getRemoteURL()|escape}">{$suppFile->getRemoteURL()|escape}</a></td>
	</tr>
{else}
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.fileName"}</td>
		<td width="80%" class="value"><a href="{url page="article" op="downloadSuppFile" path=$articleId|to_array:$suppFile->getBestSuppFileId($currentJournal)}">{$suppFile->getFileName()|escape}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.originalFileName"}</td>
		<td class="value">{$suppFile->getOriginalFileName()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.fileSize"}</td>
		<td class="value">{$suppFile->getNiceFileSize()}</td>
	</tr>
	<tr valign="top">
		<td class="infoLabel">{translate key="common.dateUploaded"}</td>
		<td class="value">{$suppFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
	</tr>
	</table>
{/if}
{else}
	<tr valign="top">
		<td colspan="2" class="noResults">{translate key="author.submit.suppFile.noFile"}</td>
	</tr>
{/if}
</table>
</div>
</div>

{include file="rt/footer.tpl"}

