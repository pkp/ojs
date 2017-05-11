{**
 * controllers/revealMore.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic markup for the reveal more widget.
 *
 * @uses $content string Content to handle
 * @uses $height int Max height of truncated content (pixels). Default: 192
 *}
{assign var=id value=10|uniqid}
{if !$height}
	{assign var=height value=192}
{/if}
<script>
	// Initialise JS handler.
	$(function() {ldelim}
		$('#revealMore-{$id}').pkpHandler(
			'$.pkp.controllers.RevealMoreHandler',
			{ldelim}
				height: {$height}
			{rdelim}
		);
	{rdelim});
</script>
<div id="revealMore-{$id}" class="pkp_controllers_revealMore">
	{$content}
	<div class="reveal_more_wrapper">
		<button href="#" class="revealMoreButton">
			{translate key="common.readMore"}
		</button>
	</div>
</div>
