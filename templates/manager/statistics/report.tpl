{**
 * report.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 * $Id$
 *
 * NOTE: This template generates a CSV-style (although tab-delimited) file
 * suitable for importing into a spreadsheet. Smarty comments are used to
 * comment out whitespace that is present for readability.
 *
 * Spacing is extremely critical in this template; for example, if there's a
 * blank line that isn't commented out using Smarty comments, it's probably
 * there for a reason.
 *
 *}
{translate key="article.submissionId"}{*
*}{if $report->hasField('authors') || $report->hasField('affiliations')}{section name="authors" start=0 loop=$report->getMaxAuthors()}{if $report->hasField('authors')}	{translate key="user.role.author"}{/if}{if $report->hasField('affiliations')}	{translate key="user.affiliations"}{/if}{/section}{/if}{*
*}{if $report->hasField('title')}	{translate key="article.title"}{/if}{*
*}{if $report->hasField('section')}	{translate key="section.section"}{/if}{*
*}{if $report->hasField('dateSubmitted')}	{translate key="common.dateSubmitted"}{/if}{*
*}{if $report->hasField('editor')}	{translate key="user.role.editor"}{/if}{*
*}{if $report->hasField('reviewers')}{section name="reviewers" start=0 loop=$report->getMaxReviewers()}	{translate key="user.role.reviewer"}{/section}{/if}{*
*}{if $report->hasField('status')}	{translate key="common.status"}{/if}{*
*}{if $report->hasField('dateDecided')}	{translate key="manager.statistics.reports.dateDecided"}{/if}

{iterate from=report item=row}
{$row.submissionId}{*
*}{if $report->hasField('authors') || $report->hasField('affiliations')}{section name="authors" start=0 loop=$report->getMaxAuthors()}{assign var=index value=$smarty.section.authors.index}{if $report->hasField('authors')}	{$row.authors[$index]}{/if}{if $report->hasField('affiliations')}	{$row.affiliations[$index]}{/if}{/section}{/if}{*
*}{if $report->hasField('title')}	{$row.title|strip_tags}{/if}{*
*}{if $report->hasField('section')}	{$row.section|strip_tags}{/if}{*
*}{if $report->hasField('dateSubmitted')}	{$row.dateSubmitted|date_format:$dateFormatShort}{/if}{*
*}{if $report->hasField('editor')}	{$row.editor}{/if}{*
*}{if $report->hasField('reviewers')}{section name="reviewers" start=0 loop=$report->getMaxReviewers()}{assign var=index value=$smarty.section.reviewers.index}	{$row.reviewers[$index]}{/section}{/if}{*
*}{if $report->hasField('status')}	{*
	*}{if $row.status == STATUS_ARCHIVED}{translate key="submissions.archived"}{*
	*}{elseif $row.status == STATUS_SCHEDULED}{translate key="submissions.scheduled"}{*
	*}{elseif $row.status == STATUS_PUBLISHED}{translate key="submissions.published"}{*
	*}{elseif $row.status == STATUS_DECLINED}{translate key="submissions.declined"}{*
	*}{else}{translate key="submissions.queued"}{*
	*}{/if}{*
*}{/if}{*
*}{if $report->hasField('dateDecided')}	{$row.dateDecided|date_format:$dateFormatShort}{/if}

{/iterate}
