{**
 * block.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- information links.
 *
 * $Id$
 *}
{if !empty($forReaders) || !empty($forAuthors) || !empty($forLibrarians)}
<div class="block" id="sidebarInformation">
	<span class="blockTitle">{$abbreviation}</span>
	<ul>
		<li><a href="{url page="about"}">About Us</a></li>
                <li><a href="{url page="about" op="submissions"}">Submission Guidelines</a></li>
                <li><a href="{url page="about" op="editorialPolicies" anchor="peerReviewProcess"}">Review Guidelines</a></li>
                <li>{mailto text="Contact Us" address="$contactEmail"}</li>
	</ul>
</div>
{/if}
