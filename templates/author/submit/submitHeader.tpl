{**
 * submitHeader.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for the article submission pages.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div>{if $submitStep > 1}<a href="{$pageUrl}/author/submit/{$submitStep-1}?articleId={$articleId}">{else}<span class="disabledText">{/if}&lt;&lt; {translate key="navigation.previousStep"}{if $submitStep > 1}</a>{else}</span>{/if} | {if $submitStep < 5 && $submissionProgress > $submitStep}<a href="{$pageUrl}/author/submit/{$submitStep+1}?articleId={$articleId}">{else}<span class="disabledText">{/if}{translate key="navigation.nextStep"} &gt;&gt;{if $submitStep < 5 && $submissionProgress > $submitStep}</a>{else}</span>{/if}</div>

<br />

<div class="stepBlockContainer">
{if $submitStep != 1 && $submissionProgress >= 1}<a href="{$pageUrl}/author/submit/1?articleId={$articleId}">{/if}
<div class="{if $submitStep == 1}stepBlock{elseif $submitStep != 1 && $submissionProgress >= 1}stepBlockDisabled{else}stepBlockUnavailable{/if}"><span class="stepNumber">{translate key="navigation.stepNumber" step=1}</span><br />{translate key="author.submit.start"}</div>{if $submitStep != 1 && $submissionProgress >= 1}</a>{/if}

{if $submitStep != 2 && $submissionProgress >= 2}<a href="{$pageUrl}/author/submit/2?articleId={$articleId}">{/if}
<div class="{if $submitStep == 2}stepBlock{elseif $submitStep != 2 && $submissionProgress >= 2}stepBlockDisabled{else}stepBlockUnavailable{/if}"><span class="stepNumber">{translate key="navigation.stepNumber" step=2}</span><br />{translate key="author.submit.metadata"}</div>{if $submitStep != 2 && $submissionProgress >= 2}</a>{/if}

{if $submitStep != 3 && $submissionProgress >= 3}<a href="{$pageUrl}/author/submit/3?articleId={$articleId}">{/if}
<div class="{if $submitStep == 3}stepBlock{elseif $submitStep != 3 && $submissionProgress >= 3}stepBlockDisabled{else}stepBlockUnavailable{/if}"><span class="stepNumber">{translate key="navigation.stepNumber" step=3}</span><br />{translate key="author.submit.upload"}</div>{if $submitStep != 3 && $submissionProgress >= 3}</a>{/if}

{if $submitStep != 4 && $submissionProgress >= 4}<a href="{$pageUrl}/author/submit/4?articleId={$articleId}">{/if}
<div class="{if $submitStep == 4}stepBlock{elseif $submitStep != 4 && $submissionProgress >= 4}stepBlockDisabled{else}stepBlockUnavailable{/if}"><span class="stepNumber">{translate key="navigation.stepNumber" step=4}</span><br />{translate key="author.submit.supplementaryFiles"}</div>{if $submitStep != 4 && $submissionProgress >= 4}</a>{/if}

{if $submitStep != 5 && $submissionProgress >= 5}<a href="{$pageUrl}/author/submit/5?articleId={$articleId}">{/if}
<div class="{if $submitStep == 5}stepBlock{elseif $submitStep != 5 && $submissionProgress >= 5}stepBlockDisabled{else}stepBlockUnavailable{/if}"><span class="stepNumber">{translate key="navigation.stepNumber" step=5}</span><br />{translate key="author.submit.confirmation"}</div>{if $submitStep != 5 && $submissionProgress >= 5}</a>{/if}

</div>

<br />
