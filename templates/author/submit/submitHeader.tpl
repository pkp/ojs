{**
 * submitHeader.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for the article submission pages.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="author.submit"}
{include file="common/header.tpl"}

<ul class="steplist">
<li{if $submitStep == 1} class="current"{/if}>{if $submitStep != 1 && $submissionProgress >= 1}<a href="{$pageUrl}/author/submit/1?articleId={$articleId}">{/if}
{translate key="author.submit.start"}{if $submitStep != 1 && $submissionProgress >= 1}</a>{/if}</li>

<li{if $submitStep == 2} class="current"{/if}>{if $submitStep != 2 && $submissionProgress >= 2}<a href="{$pageUrl}/author/submit/2?articleId={$articleId}">{/if}
{translate key="author.submit.metadata"}{if $submitStep != 2 && $submissionProgress >= 2}</a>{/if}</li>

<li{if $submitStep == 3} class="current"{/if}>{if $submitStep != 3 && $submissionProgress >= 3}<a href="{$pageUrl}/author/submit/3?articleId={$articleId}">{/if}
{translate key="author.submit.upload"}{if $submitStep != 3 && $submissionProgress >= 3}</a>{/if}</li>

<li{if $submitStep == 4} class="current"{/if}>{if $submitStep != 4 && $submissionProgress >= 4}<a href="{$pageUrl}/author/submit/4?articleId={$articleId}">{/if}
{translate key="author.submit.supplementaryFiles"}{if $submitStep != 4 && $submissionProgress >= 4}</a>{/if}</li>

<li{if $submitStep == 5} class="current"{/if}>{if $submitStep != 5 && $submissionProgress >= 5}<a href="{$pageUrl}/author/submit/5?articleId={$articleId}">{/if}
{translate key="author.submit.confirmation"}{if $submitStep != 5 && $submissionProgress >= 5}</a>{/if}</li>
</ul>
