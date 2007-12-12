OJS 2.2.0 Release Notes
CVS tag: ojs-2_2_0-0
Release date: Dec 11, 2007
=================================


Configuration Changes
---------------------

See config.TEMPLATE.inc.php for a description and examples of all supported
configuration parameters.

New config.inc.php parameters:
	- general:allow_url_fopen - Allows disabling of url-based fopen calls,
		even if the feature is available in PHP. If this is disabled,
		the FileWrapper.inc.php class (classes/file) will be used.
	- cache:web_cache - Enables or disables page caching for the most
		commonly-accessed and least dynamic pages. Note that this
		feature may not be appropriate for journals that are not open-
		access, as this kind of caching is incompatible with
		authentication.
	- cache:web_cache_hours - Controls the time-to-live for contents of the
		aforementioned web cache.
	- security:allowed_html - Allows the direct specification of which HTML
		tags are allowed for unpriveleged users in fields that allow
		limited HTML tags (e.g. article titles).
	- email:default_envelope_sender - The envelope sender is configurable
		on a per-journal basis, but if one is specified here, it will
		be used if not overridden by a journal's setting.
	- email:time_between_emails - For non-priveleged users, this restricts
		abuse of the email system by requiring a certain amount of time
		between messages.
	- email:max_recipients - For non-priveleged users, this restricts abuse
		of the email system by limiting the number of recipients in a
		single message.
	- email:require_validation - Enables or disables a process requiring
		emails to be validated before user accounts are activated.
	- email:validation_timeout - The number of days between account creation
		and deletion in which an account must be validated in order for
		it to persist.
	- captcha:captcha - Enable or disable captcha testing.
	- captcha:captcha_on_register - Enable or disable captcha testing in the
		user registration process.
	- captcha:captcha_on_comments - Enable or disable captcha testing in the
		comment posting process.
	- captcha:font_location - Specifies the location of a truetype font for
		use in the captcha test image generation.
	- proxy:http_host - Allows specification of a HTTP proxy host for
		requests to outside servers
	- proxy:http_port - Allows specification of an HTTP proxy port number
	- proxy:proxy_username - Allows specification of a username for HTTP
		proxy authentication
	- proxy:proxy_password - Allows specification of a password for HTTP
		proxy authentication


New Features
------------
	- #1912# Add plugin to display custom pages
	- #1935# Reading Tools Google Scholar Update for 2.1 and beyond
	- #2208# Wording for Editor Decision
	- #2231# Scheduling refinements
	- #2249# order of uploading files
	- #2252# User import does not support sha1 encryption
	- #2263# "Editor to enter" should include "declined to review"
	- #2264# Template Typo in setup
	- #2265# Add transcoding support for emails
	- #2267# Improved wording on Setup for CE, LE and PR
	- #2268# Help wording improvement for Peer Review
	- #2269# Setup 2.6 and 4.3 coordination and unification
	- #2273# Clarify the move from copyedit to layout
	- #2274# Searching/indexing code needs to use UTF-8 compatible calls
	- #2277# Merge users does not display all users
	- #2283# Error in Processing Journal Section criterion - abstract
	- #2289# Add role information to deeper enrollment pages
	- #2290# Option to NOT display journal section in About the Journal
	- #2291# Issue identification in terms of Volume, Number, Year, Title
	- #2293# When archiving a submission, a needless pop-up emerges.
	- #2295# Improvements to installer process/usability
	- #2304# Authors should not be able to add Supplementary File afte...
	- #2305# Add Review Metadata link to Proofreading
	- #2306# Add databases links to Select Reviewer page
	- #2308# Add a font size selector to the right-hand frame
	- #2309# Need a more prominent start submission process
	- #2311# Option for JM to add templates for Editor Decision's Noti...
	- #2312# Use localization keys in Help
	- #2318# Add support for plugin help
	- #2323# Option to display article views for authors
	- #2326# Handling of Preprints in Help
	- #2330# Improve instructions for Copyediting and Proofreading to...
	- #2331# When Viewing Proof and returning to submission, one is lo...
	- #2336# Ability to enoll/un-enroll myself as an author and/or rev...
	- #2337# Create New User needs a special welcome email
	- #2338# Reviewer can only see submission after agreeing to review
	- #2347# Update ADODB to the newest release.
	- #2348# Update Smarty to the newest release.
	- #2350# Add Capcha-like service for user comments
	- #2354# Remove "CC my account" feature from editor's "notify users"
	- #2361# Optimize reader's interface
	- #2366# When Related Items is disabled, other Reading Tools reque...
	- #2368# Some database field lengths should be increased.
	- #2369# Unable to re-start proofreading process with Proofreader
	- #2370# Add email signature field to user profiles
	- #2374# Improve and publish automatic documentation (doxygen)
	- #2380# Add transcode option to CLI migration usage
	- #2381# Manager's "Prepared Emails" list should be able to send
	- #2382# Need to indicate which version was forwarded to C/E, foll...
	- #2384# RT corrections
	- #2392# Improve reference use for [section]Editor's lists
	- #2394# Add Layout Manager plugin
	- #2398# HookRegistry::clear should return hooks
	- #2399# Add CMS plugin
	- #2400# Add MJ's XML Galley plugin to CVS
	- #2402# Refactor locale handling code.
	- #2412# Add de_DE German locale
	- #2413# Add date header to outgoing emails
	- #2416# Add anchors for paging links
	- #2420# Add optional explanation on login form
	- #2421# Add "email me my password" option to relevant forms
	- #2424# Change currencies to use cached XML file instead of datab...
	- #2426# Add "Display information about this section in About" opt...
	- #2427# Add translation tools plugin
	- #2460# Layout manager plugin kills extra sidebar blocks
	- #2461# Add custom section ordering support to native import plugin
	- #2467# Allow linking to HTML galley images
	- #2468# French locale typos
	- #2470# Add site-wide CSS upload
	- #2471# Add site-wide envelope sender setting
	- #2473# Set disposition to "download" for XML exports
	- #2475# Submission language default is hard-coded as English (en)
	- #2477# DOI should be available in reading tools metadata display
	- #2479# SubscriptionManagerHandler::saveSubscriptionPolicies() do...
	- #2480# Localize "editorial process" image
	- #2484# Translation updates for 2.2
	- #2485# Custom section ordering cleanup code needed
	- #2486# Add phpAdsNew integration plugin
	- #2488# Translation plugin tune-ups
	- #2489# Selective Display of Journal Sections in About
	- #2490# Update Subscription Type Public Display Column and Code t...
	- #2492# Allow manual re-ordering of published issues / correction...
	- #2495# Copyrigth Notice changes to incorporate Creative Commons
	- #2497# Add user agent filtering for view counts
	- #2500# Add Citation Searching Options to Layout
	- #2501# Revise People listing to enable greater differentiation
	- #2503# Improve SMTP mailer error handling
	- #2507# User's Edit Profile should include available roles
	- #2509# HTML image galleys can only be included once per document
	- #2510# Address email privacy/security concerns
	- #2513# Editors need a means of searching for a submission
	- #2547# XML-Derived Article Galley Plugin
	- #2555# Reviewer recommendation constants should be in ReviewAssi...
	- #2563# "About" link from submission page should open in new window.
	- #2578# Add support for email validation on registration
	- #2586# Harvester 2.x and OCS 2.x backports
	- #2644# Rewording of Setup 2.6
	- #2652# Add support for RefWorks citation extractions in Reading ...
	- #2670# PHP's mime_content_type function is deprecated
	- #2671# Thesis Abstract Enhancements
	- #2675# Extend IP address field
	- #2678# Copyedit and Layout Comments, Proofreading Corrections Em...
	- #2680# Automate assignment of Section Editors
	- #2681# Create new reviewer (editor)
	- #2682# CrossRef tune-ups
	- #2683# Highlight actionable items for editor in Review and Editi...
	- #2685# Submission process rewordings
	- #2687# Handle non-UTF characters being submitted in forms
	- #2694# APA citation format needs review
	- #2696# Reference tune-up in Google Scholar plugin
	- #2700# Add formal steps and request to C/E, Layout and Proofread...
	- #2701# Editor needs to be able to upload to all three C/E stages.
	- #2702# Remove templates/issue/header.tpl
	- #2707# Adding a new group of people, outside of Editorial Team t...
	- #2712# History should record if an email is skipped as opposed t...
	- #2716# Allow subscription manager to create users
	- #2770# Rename "All Users" in Journal Manager's interface
	- #2774# Add change-date in Google Scholar plugin
	- #2786# Update copyright dates
	- #2808# fr_CA JournalPath
	- #2810# Add caching mechanism for static pages
	- #2818# Add Japanese locale
	- #2821# Add Croatian support
	- #2842# Update pt_BR translation
	- #2886# Add Vietnamese support
	- #2887# Review version should be sufficient for sending to copyed...
	- #2889# Email co-authors of Editor Decision
	- #2893# Use regular password hash when default auth ID specified.
	- #2898# Use Site Title text on site TOC
	- #2900# Add Google Analytics plugin
	- #2902# Conflict of Interest statement for co-authors
	- #2903# Update TinyMCE plugin and include in OJS core
	- #2904# Add unique div IDs to standard page elements
	- #2905# Extend custom footer to article view
	- #2906# Add phpMyVisites plugin
	- #2912# ability to add site-only logo
	- #2916# Add request method checking for POST forms
	- #2921# Add support for locale-specific stylesheets
	- #2922# Add theme support (and Jon Whipple's themes)
	- #2927# Add a "source" parameter (à là "login") to the Registra...
	- #2938# Reviewer request emails should display reviewer email
	- #2941# Files browser lists should be sorted
	- #2942# Reading Tool Updates
	- #2947# Move font sizer CSS down in inclusion list
	- #2948# Modify import class to use core for character encoding no...
	- #2952# Move allowed HTML into config.inc.php
	- #2955# Add variable replacements to HTML galleys
	- #2961# Localization overhaul
	- #2964# Implement payment support
	- #2969# Add support for HTML galley highlighting by referrer URL
	- #2970# Add HTML META elements for article indexing
	- #2988# Add Cache-Control headers
	- #2996# Merge Chia-ning's RT updates 04/09/07
	- #2997# Add default plugin activation
	- #3018# Icons showing open vs restricted access on galley links
	- #3022# Update Smarty to current
	- #3024# Add Reviewing Interests to Manager's role list search engine

Bug Fixes
---------
	- #2230# When a section does not use abstracts, the resulting "det...
	- #2261# View all users does not display all users
	- #2266# Can't upload file
	- #2270# Layout Comments uses * and required does not work
	- #2278# character encoding of locale not changing to ISO-8859-1 d...
	- #2294# Weak email validation creates invalid feeds
	- #2321# PluginRegistry.inc.php mixing directory separators
	- #2322# Incorrect breadcrumb on reviewer's page for a paper
	- #2325# Back-port Harvester2 indexing stupidity check for databases
	- #2339# It should not be possible to send a submission to copyedi...
	- #2340# Section Editor (Review) is being emailed in Proofing stage
	- #2341# OJS should be reporting an error message if a galley imag...
	- #2343# Schema upgrade does not disable null checking if necessary
	- #2344# Paging error in Editor's Select Reviewer
	- #2345# Installer help text for database settings is incorrect
	- #2346# Expedite feature not working in 2.1.1
	- #2351# Article "print version" does not include article stylesheet
	- #2355# Restrict <> characters from appearing in email addresses
	- #2356# Escape email headers to avoid possible insertion attacks
	- #2358# Extra conferenceId parameter passed to $sectionDao->getSe...
	- #2359# Remove ambiguous characters from password generation
	- #2360# Remove unused and wasteful Issue::getAuthors code
	- #2365# Inter-journal security checks prevent non-administrator m...
	- #2367# Spelling error in locale
	- #2371# Mass email sending issues
	- #2373# History page fixated on reviewer as source of everything
	- #2376# Editor decisiont to decline needs to remain active until ...
	- #2379# ISSN labels incorrect
	- #2383# Event and email log entries use bad static optimization
	- #2385# Using Import Peer Review in Notifdy Author email erases a...
	- #2397# Reviewers still receive reminders for archived articles
	- #2401# Prepared Emails list using wrong separators
	- #2403# Additional header data not displayed in all templates.
	- #2404# Article validation too restrictive for abstracts
	- #2405# CMS plugin alters TinyMCE buttons outside of its settings...
	- #2407# Display name in emails should be quoted if it has special...
	- #2409# Thesis plugin should extend content instead of overwritin...
	- #2411# Need to use filename length limits for original_file_name...
	- #2414# Editor decisions not recorded in event log
	- #2415# Google Scholar plugin generating incorrect URLs
	- #2422# HTTPFileWrapper cannot handle redirects
	- #2423# Reviewer says no and is cancelled; still counted as havin...
	- #2425# "Merge Users" doesn't allow merging into the current account
	- #2428# Comment emails should not be copied to reviewing editors
	- #2429# Under "For Authors" bad link to Author Guidelines
	- #2431# Envelope Sender does not apply to automated emails
	- #2432# Merge Users should also merge roles.
	- #2433# French translation refers to OJS as PRESTO
	- #2434# Layout editors unable to access CSS when proofing unpubli...
	- #2437# Declined reviews are considered incomplete
	- #2438# Submission lists & counts don't work well with editor-fre...
	- #2451# Missing colspan in sidebar login for submit button
	- #2456# Email send missing setFrom(...)
	- #2458# Proofreader email addressing sometimes incorrect
	- #2462# Caption should be optional in native import/export DTD
	- #2463# CLI native export does not respect PWD.
	- #2464# Cached data interfering with some management functions
	- #2466# Layout Manager plugin doesn't move gracefully
	- #2474# Google Scholar plugin should supply URLs to article, not ...
	- #2476# Registration form missing "country" field
	- #2478# Typo in journal setup, step 2
	- #2482# Clicking on Review Version of submission leads back to Su...
	- #2493# following sending email to user, dumps you into journal home
	- #2504# Plugins should not use the database when the "upgrade" sc...
	- #2505# problems accessing 2nd and 3rd level headings
	- #2508# Counter plugin assumes journal context
	- #2514# 'signature' only half-handled in Manager's UserManagement...
	- #2549# 'Print version' produces fatal error if no galley is uplo...
	- #2551# Help Table of Contents broken
	- #2579# The "Select Template" feature may have disappeared
	- #2594# CrossRef export plugin uses "some DOI batch ID" as batch ID
	- #2630# Country list typo
	- #2642# Assigning Layout Editor table is mis-recording numbers
	- #2645# Merge Users help indicates that Roles are not transferred
	- #2646# Email intended to go to Section Editor goes to Author
	- #2647# Author's complete email in Proofreading should not go to ...
	- #2668# Ensure that it's not possible to lodge an article in Copy...
	- #2673# Submission email log unnecessarily applies remove_unsafe_...
	- #2676# Imported papers from OJS1 cannot be declined properly
	- #2686# Domain-based subscription check always appears to be sati...
	- #2695# Email link in user profile is incorrect
	- #2699# Editors and Section Editors experience different "Decline...
	- #2704# CMS images do not work
	- #2706# Dissappearing paper with reviews for #64 in Open Medicine
	- #2709# COUNTER stats should only be available to admins
	- #2710# Wrong role_id in SectionEditorAction
	- #2711# CAPTCHA test missing wrapping <tr>
	- #2714# ArticleHandler::validate requires articles to be scheduled
	- #2768# "Decline" decision causes counts and lists to misbehave
	- #2773# Cancel button on Google Scholar settings page isn't correct
	- #2775# Google Scholar XML file reports wrong publication dates
	- #2800# Google Scholar XML file has wrong links for pdf articles
	- #2806# Inlineable non-HTML galley code forgets to return
	- #2811# TinyMCE disappears on CMS Settings Form Validate
	- #2812# CmsRss Plugin throwing Invalid argument supplied for fore...
	- #2813# Archiving after publishing
	- #2814# Exempt certain pages from TinyMCE loading
	- #2816# middle name not displayed correctly
	- #2823# Breadcrumb URL should be review_id
	- #2881# Reviewer labels don't match
	- #2858# Editors given Section Editor URLs in assignment notificat...
	- #2864# Paging loses context in editor select list
	- #2879# PhpAdsNew generated code getting overzealously cached
	- #2890# cliTool.inc.php patch
	- #2891# Remove dead notifyAuthor code
	- #2894# Remove extra encoding from "From" field in email send form
	- #2896# Email tag should be mandatory in native.dtd
	- #2897# Issue, when included on homepage, omits subtitle.
	- #2913# "URL" field not working on JM's "Edit User" form
	- #2914# Dates in emails are not formatted using configuration vars
	- #2917# Create Journal fails using PostgreSQL
	- #2918# COPYEDIT_COMPLETE goes to first author, not submitting user
	- #2907# PostgreSQL ordering and untyped columns
	- #2926# IssueForm does not use proper function to format date for DB
	- #2910# URL generation when path_info is disabled should escape []
	- #2901# Define Terms double-click does not work with disable_path...
	- #2042# Editing User Profile
	- #2084# Error moving from step 2 to step 3
	- #2939# TinyMCE plugin should enable specific fields only
	- #2931# OJS1 Import Fails from Undefined Method
	- #2945# Locale key typo: "gallery" should be "galley"
	- #2940# Submission status change date stamped too often
	- #2924# Apply setup page updates before displaying "Saved" message
	- #2933# XHTML compliance
	- #2934# Extra blank line appears in ToC
	- #2935# Emails containing "," in display name not properly escaped
	- #2977# HTML galley image filenames not escaped in replacement pregs
	- #2943# Editor assignment SQL uses incorrect join
	- #2966# Fix acceptance rate statistic
	- #2944# PluginRegistry::getAllPlugins assumes array
	- #2982# Sec Ed "Active" / "Completed" counts on assignment page n...
	- #2978# "Import Peer Reviews" misplaced close brace in SectionEdi...
	- #2986# Investigate ADODB index problems
	- #2987# Supp file typeOther metadata doesn't appear to be displayed.
	- #2991# Remove whitespace from templates prior to header.tpl bein...
	- #2994# Issue::subscriptionRequired should check issue object for...
	- #2990# XSS bug in templates/common/header.tpl
	- #2993# Suppress domain name lookup errors
	- #2995# strtotime complains when called with a null value
	- #2999# Change plugin loading error suppression behavior
	- #3001# Improve subscription authentication performance
	- #3000# Correct file cache behavior for installs with broken perm...
	- #2998# Various indentation and whitespace fixes
	- #3011# Creating and saving a new user does not return back to "E...
	- #3009# "Subscribers Only" text should link to About->Subscriptions
	- #3002# Issue description never displayed.
	- #3017# Typo in some calls to mktime
	- #3003# Rename/refactor review page locale keys
