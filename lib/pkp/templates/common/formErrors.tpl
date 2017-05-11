{**
 * lib/pkp/templates/common/formErrors.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List errors that occurred during form processing.
 *}
{if $isError}
	<div id="formErrors">
		<span class="pkp_form_error">{translate key="form.errorsOccurred"}:</span>
		<ul class="pkp_form_error_list">
		{foreach key=field item=message from=$errors}
			<li><a href="#{$field|escape}">{$message}</a></li>
		{/foreach}
		</ul>
	</div>
	<script>{literal}
		<!--
		// Jump to form errors.
		window.location.hash="formErrors";
		// -->
	{/literal}</script>
{/if}
