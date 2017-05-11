{**
 * nlm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * NLM Journal Publication Tag Set 3.0 XML document output.
 *
 * FIXME: This currently is partial NLM XML output (reference lists). Create
 * full NLM mark-up when we have full document support.
 *}
<ref-list>
	{foreach from=$citationsOutput key=seq item=citationOutput}
		<ref id="B{$seq}">
			<label>{$seq}</label>
			{$citationOutput}
		</ref>
	{/foreach}
</ref-list>
