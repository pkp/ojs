{**
 * templates/submission/form/sectionPolicy.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section policy for submissions.
 *}
{assign var=class value="section-policy section-id-`$sectionId` `$class`"}

{fbvFormSection title="section.policy" class=$class}
	{$content}
{/fbvFormSection}
