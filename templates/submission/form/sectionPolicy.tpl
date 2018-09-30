{**
 * templates/submission/form/sectionPolicy.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section policy for submissions.
 *}
{assign var=class value="section-policy section-id-`$sectionId` `$class`"}

{if $hidden}
	{assign var=class value="`$class` pkp_helpers_display_none"}
{/if}

{fbvFormSection title="section.policy" class=$class}
	{$content}
{/fbvFormSection}
