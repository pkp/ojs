{**
 * plugins/blocks/information/block.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- information links.
 *
 *}
{if !empty($forReaders) || !empty($forAuthors) || !empty($forLibrarians)}
<div class="pkp_block block_information">
	<h2 class="title">{translate key="plugins.block.information.link"}</h2>
	<div class="content">
		<ul>
			{if !empty($forReaders)}
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="information" op="readers"}">
						{translate key="navigation.infoForReaders"}
					</a>
				</li>
			{/if}
			{if !empty($forAuthors)}
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="information" op="authors"}">
						{translate key="navigation.infoForAuthors"}
					</a>
				</li>
			{/if}
			{if !empty($forLibrarians)}
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="information" op="librarians"}">
						{translate key="navigation.infoForLibrarians"}
					</a>
				</li>
			{/if}
		</ul>
	</div>
</div>
{/if}
