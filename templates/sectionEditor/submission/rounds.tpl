{**
 * rounds.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate displaying past rounds for a submission.
 *
 * $Id$
 *}

<a name="rounds"></a>
<h3>{translate key="sectionEditor.regrets.regretsAndCancels"}</h3>

<table width="100%" class="listing">
	<tr valign="top">
		<td class="heading" width="30%">{translate key="user.name"}</td>
		<td class="heading" width="15%">{translate key="submission.request"}</td>
		<td class="heading" width="15%">{translate key="sectionEditor.regrets.result"}</td>
	</tr>
{foreach from=$cancelsAndRegrets item=cancelOrRegret}
	<tr valign="top">
		<td>{$cancelOrRegret->getReviewerFullName()}</td>
		<td>
			{if $cancelOrRegret->getDateNotified()}
				{$cancelOrRegret->getDateNotified()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $cancelOrRegret->getCancelled()}
				{translate key="common.cancelled"}
			{else}
				{translate key="sectionEditor.regrets.regret"}
			{/if}
		</td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td class="nodata">{translate key="common.none}</td>
	</tr>
{/foreach}
</table>

<div class="separator"></div>

{section name=round loop=$numRounds}
{assign var=roundAssignments value=$reviewAssignments[$smarty.section.round.index]}
{assign var=roundDecisions value=$editorDecisions[$smarty.section.round.index]}

<h3>{translate key="sectionEditor.regrets.reviewRound" round=$smarty.section.round.index+1}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="editor.article.reviewVersion"}</td>
		<td class="data" width="80%">FIXME</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.supplementaryFiles"}</td>
		<td class="data" width="80%">
			FIXME
		</td>
	</tr>
</table>

{assign var="start" value="A"|ord}

{foreach from=$roundAssignments item=reviewAssignment key=reviewKey}

<div class="separator"></div>
<h4>{translate key="user.role.reviewer"} {$reviewKey+$start|chr} {$reviewAssignment->getReviewerFullName()}</h4>

<p>FIXME</p>

{/foreach}

<div class="separator"></div>

<h3>{translate key="sectionEditor.regrets.decisionRound" round=$smarty.section.round.index+1}</h3>

<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%">{translate key="editor.article.decision"}</td>
		<td class="value" width="80%">
			{foreach from=$roundDecisions item=editorDecision key=decisionKey}
				{if $decisionKey neq 0} | {/if}
				{assign var="decision" value=$editorDecision.decision}
				{translate key=$editorDecisionOptions.$decision} {$editorDecision.dateDecided|date_format:$dateFormatShort}
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.editorAuthorComments"}</td>
		<td class="value" width="80%">FIXME</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.authorVersion"}</td>
		<td class="value" width="80%">FIXME</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.editorVersion"}</td>
		<td class="value" width="80%">FIXME</td>
	</tr>
</table>

<div class="separator"></div>


{/section}

