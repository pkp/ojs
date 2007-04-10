{**
 * submission.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Layout editor's view of submission details.
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="submission.page.editing" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}

{assign var=layoutAssignment value=$submission->getLayoutAssignment()}
{assign var=proofAssignment value=$submission->getProofAssignment()}
{assign var=layoutFile value=$layoutAssignment->getLayoutFile()}

{include file="layoutEditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="layoutEditor/submission/layout.tpl"}

<div class="separator"></div>

{include file="layoutEditor/submission/proofread.tpl"}

<div class="separator"></div>

{include file="layoutEditor/submission/scheduling.tpl"}

{include file="common/footer.tpl"}
