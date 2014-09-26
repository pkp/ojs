{**
 * block.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- help pop-up link.
 *
 * $Id$
 *}
<div class="block" id="sidebarHelp">
        <span class="blockTitle">Help</span>
        <ul>
		<li><a href="https://submit.escholarship.org/help/" target="_blank">Help Center</a></li>
                <li>{mailto text="Contact $abbreviation" address="$contactEmail"}</li>
        </ul>
</div>
