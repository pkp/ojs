OJS 2.1.1 Release Notes
CVS tag: ojs-2_1_1-0
Release date: July 17, 2006
=================================


Configuration Changes
---------------------

See config.TEMPLATE.inc.php for a description and examples of all supported
configuration parameters.

New config.inc.php parameters:
	- general:allow_url_fopen - Allows disabling of url-based fopen calls,
	          even if the feature is available in PHP. If this is disabled,
		  the FileWrapper.inc.php class (classes/file) will be used.

New Features
------------
	- #977# #2218# Statistics and reports updates
	- #1632# RSS/RSS2/Atom support added
	- #1633# Announcements feature added
	- #2115# Added duplicate account list for OJS 1.x migrations
	- #2082# Added "Merge Users" feature
	- #2125# Counter report 1a
	- #2126# Abstracted alpha lists
	- #2134# Editorial process modifications
	- #2151# Added simple article resolver
	- #2172# Added URL to user/author profiles
	- #2178# Thesis abstract plugin
	- #2184# TinyMCE WYSIWYG editor support added
	- #2185# Layout template support added
	- #2187# Layout Editors need to know submission's issue
	- #2197# Add electronic vs. print ISSN distinction
	- #2200# Make registry/inlineTypes.txt apply to article galleys
	- #2206# Reviewing Interests added to Reviewer Enrollment
	- #2335# CrossRef export support added
	- #2335# Pubmed export support added
	- #1458# Help files updated
	- #2090# #2234# #2310# Editor decision refinements
	- #2100# #2101# Native import/export tune-ups (XSL etc)
	- #2119# Add pages info into OAI feeds
	- #2143# #2222# Editorial assignment modifications
	- #2144# #2182# Editorial page refinements
	- #2154# #2236# "Ensuring a Blind Review" instructions/help refactoring
	- #2159# Added locale checker
	- #2162# #2250# #2254# Reading Tools layout refinements
	- #2166# "About this publishing system" should include version
	- #2213# #1243# #1739# Subscription module updates
	- #2220# Plugins page layout changes
	- #2225# Galley edit layout changes
	- #2230# "Details" link should not be displayed when unnecessary
	- #2233# History refinements
	- #2240# Setup layout changes for 3.3
	- #2286# Added sup and sub to allowed HTML
	- #2297# Added switch de-bouncer to install and journal create forms
	
Bug Fixes
---------
	- #1814# Removed abstract heading from emails when abstracts disabled
	- #2045# #2214# #2237# Default submission checklist modified
	- #2061# #2066# #2064# #2157# #2158# #2174# #2202# #2209# #2210# #2211#
	  #2221# #2232# #2301# Wording changes
	- #2062# #2063# #2271# Asterisks / required fields modifications
	- #2067# #2127# #2150# #2161# #2173# #2315# Paging issues
	- #2068# #2069# Hook name corrections & updates
	- #2072# Typo in Section Editor's interface
	- #2076# "Select All" email bug in People Management
	- #2077# "Table of Contents" link from issue title page on journal home
	- #2078# Issue title not displayed on cover or ToC
	- #2080# Template pre-compile tune-ups
	- #2083# Dirname peculiarity with webroot installations on Windows
	- #2085# #2164# Notify Users email update
	- #2086# Author acknowledgement BCCs
	- #2087# Submission counts not always correct
	- #2092# Code cleanup -- references and commenting
	- #2095# Cancel button behaving unexpectedly
	- #2096# Erudit command-line export
	- #2097# Installer error message uses incorrect variable name
	- #2102# Reminder email cannot be sent to reviewer
	- #2103# Prevent editors from modifying addresses on one-click emails
	- #2104# "About" link corrections
	- #2105# Moved custom items
	- #2106# Email update registrations
	- #2107# OAI with path_info disabled
	- #2109# ADODB cache should be flushed with data caches
	- #2113# fopen should use binary mode
	- #2116# #2148# Proofreading dates not showing up as expected
	- #2118# Automated reminders and one-click access
	- #2120# Multi-language import/export
	- #2123# Open access policy display
	- #2124# Author index and UTF-8
	- #2128# Issue CSS not deleted when issue deleted
	- #2129# Citation tool export updates
	- #2130# #2195# #2245# #2302# Custom section ordering
	- #2132# Handling of double-slashes in URLs
	- #2135# Author/editor email addressing changes
	- #2136# Submission page email icons should result in log additions
	- #2142# Editorial assignment notification email problem
	- #2145# Published article archive/restore problem
	- #2146# Articles without titles
	- #2147# Editor becomes Section Editor
	- #2152# Main RT admin page does not check credentials
	- #2153# Corrected bad redirect
	- #2156# Corrected breadcrumb
	- #2163# Email sender not correctly set
	- #2165# Mail template not storing journal param correctly
	- #2169# Typo in systemConfigUpdated.tpl
	- #2179# OAI output should not include HTML
	- #2180# Erudit export should not include HTML
	- #2183# GenericPlugin::installData tune-up
	- #2186# Proofreader email error
	- #2188# Authors index not limited to journal
	- #2189# Layout Editor needs to be able to upload Layout Version
	- #2190# Define Terms does not work with path_info disabled
	- #2191# Plugin locale files not automatically flushed when necessary
	- #2192# Generic plugins must check installed status before using DB
	- #2196# Section form incorrectly lists other languages' abbreviations
	- #2198# templates/issue/header.tpl accesses $issue without checking
	- #2201# XML file validation problems
	- #2212# Cannot send layout version complete message
	- #2215# Issue preview title
	- #2216# Copyedit Step 3 upload
	- #2217# Article should be moved to archive
	- #2228# Reviewer email error
	- #2235# Field lengths mismatch between UI and DB
	- #2238# Email link in email history has key instead of text
	- #2241# Plugin schema installation
	- #2246# Journal Manager's "Make Directory" in files browser
	- #2247# Advisory comment for invalid CSS
	- #2248# Image problem with HTML galleys
	- #2250# "type" column for temporary files unused and not null
	- #2262# Section deletion violates constraint
	- #2276# Suprious index in XML schema
	- #2279# Site primary locale not used for journal creation
	- #2281# Request::getRemoteDomain does not return domain
	- #2282# Subscription domain and ip range should be set to null ...
	- #2284# Imported issues without publication date
	- #2285# URL not working in reminder email to reviewers
	- #2300# Reviewer cannot record recommendation
	- #2314# "Select template" feature not working for editorial team
	- #2316# Remove additional browser IPs if multiple addresses reported
	- #2328# Reference abuse in native export
	- #2329# BCCs not received when screen names included
	- #2333# User import links do not work
