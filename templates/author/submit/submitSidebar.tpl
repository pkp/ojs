{**
 * submitSidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Sidebar accompanying article submission pages.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="author.submit.stepsToSubmit"}</div>
<div class="sidebarBlock">
<ol>
<li>{if $submitStep != 1 && $submissionProgress >= 1}<a href="{$pageUrl}/author/submit/1?articleId={$articleId}">{elseif $submitStep == 1}<b>{/if}{translate key="author.submit.start"}{if $submitStep != 1 && $submissionProgress >= 1}</a>{elseif $submitStep == 1}</b>{/if}</li>
<li>{if $submitStep != 2 && $submissionProgress >= 2}<a href="{$pageUrl}/author/submit/2?articleId={$articleId}">{elseif $submitStep == 2}<b>{/if}{translate key="author.submit.metadata"}{if $submitStep != 2 && $submissionProgress >= 2}</a>{elseif $submitStep == 2}</b>{/if}</li>
<li>{if $submitStep != 3 && $submissionProgress >= 3}<a href="{$pageUrl}/author/submit/3?articleId={$articleId}">{elseif $submitStep == 3}<b>{/if}{translate key="author.submit.upload"}{if $submitStep != 3 && $submissionProgress >= 3}</a>{elseif $submitStep == 3}</b>{/if}</li>
<li>{if $submitStep != 4 && $submissionProgress >= 4}<a href="{$pageUrl}/author/submit/4?articleId={$articleId}">{elseif $submitStep == 4}<b>{/if}{translate key="author.submit.supplementaryFiles"}{if $submitStep != 4 && $submissionProgress >= 4}</a>{elseif $submitStep == 4}</b>{/if}</li>
<li>{if $submitStep != 5 && $submissionProgress >= 5}<a href="{$pageUrl}/author/submit/5?articleId={$articleId}">{elseif $submitStep == 5}<b>{/if}{translate key="author.submit.confirmation"}{if $submitStep != 5 && $submissionProgress >= 5}</a>{elseif $submitStep == 5}</b>{/if}</li>
</ol>
</div>
