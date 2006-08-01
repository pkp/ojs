{if ( $displayPage eq "all" ) || ($displayPage eq "issue" && $issue) }
<div class="block">
	<a href="{url page="feed" op="atom"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/atom10_logo.gif" order="0" alt="" border="0" /></a>
	<br/>
	<a href="{url page="feed" op="rss2"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/rss20_logo.gif" order="0" alt="" border="0" /></a>
	<br/>
	<a href="{url page="feed" op="rss"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/rss10_logo.gif" order="0" alt="" border="0" /></a>
</div>
{/if}