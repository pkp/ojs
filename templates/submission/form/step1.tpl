{**
 * templates/submission/form/step1.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author monograph submission.
 *}
{capture assign="additionalFormContent2"}
	{include file="submission/form/section.tpl"}
{/capture}

{include file="core:submission/form/step1.tpl"}
