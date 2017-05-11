{**
 * templates/user/interestsInput.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Keyword input control for user interests
 *}
<script>
	$(document).ready(function(){ldelim}
		$("#{$FBV_id|escape}").find(".interests").tagit({ldelim}
			fieldName: 'interests[]',
			allowSpaces: true,
			autocomplete: {ldelim}
				source: function(request, response) {ldelim}
					$.ajax({ldelim}
						url: {url|json_encode router=$smarty.const.ROUTE_PAGE page='user' op='getInterests' escape=false},
						data: {ldelim}'term': request.term{rdelim},
						dataType: 'json',
						success: function(jsonData) {ldelim}
							if (jsonData.status == true) {ldelim}
								response(jsonData.content);
							{rdelim}
						{rdelim}
					{rdelim});
				{rdelim}
			{rdelim}
		{rdelim});
	{rdelim});
</script>

<div id="{$FBV_id|escape}">
	<!-- The container which will be processed by tag-it.js as the interests widget -->
	<ul class="interests">
		{if $FBV_interests}{foreach from=$FBV_interests item=interest}<li class="hidden">{$interest|escape}</li>{/foreach}{/if}
	</ul>
	{if $FBV_label_content}<span>{$FBV_label_content}</span>{/if}
</div>
