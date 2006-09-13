{if $currentJournal}
<div class="block">
	<span class="blockTitle">{translate key="navigation.info"}</span>
	<ul>
		<li><a href="{url page="information" op="readers"}">{translate key="navigation.infoForReaders"}</a></li>
		<li><a href="{url page="information" op="authors"}">{translate key="navigation.infoForAuthors"}</a></li>
		<li><a href="{url page="information" op="librarians"}">{translate key="navigation.infoForLibrarians"}</a></li>		
	</ul>
</div>
{/if}