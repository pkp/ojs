; <?php exit(); // DO NOT DELETE ?>
; DO NOT DELETE THE ABOVE LINE!!!
; Doing so will expose this configuration file through your web site!
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; config.TEMPLATE.inc.php
;
; Copyright (c) 2003-2005 The Public Knowledge Project
; Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
;
; OJS Configuration settings.
; Rename config.TEMPLATE.inc.php to config.inc.php to use.
;
; $Id$
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;


;;;;;;;;;;;;;;;;;;;;
; General Settings ;
;;;;;;;;;;;;;;;;;;;;

[general]

; Set this to On once the system has been installed
; (This is generally done automatically by the installer)
installed = Off

; The canonical URL to the OJS installation (excluding the trailing slash)
base_url = "http://pkp.sfu.ca/ojs"

; Path to the registry directory (containing various settings files)
; Although the files in this directory generally do not contain any
; sensitive information, the directory can be moved to a location that
; is not web-accessible if desired
registry_dir = registry

; Session cookie name
session_cookie_name = OJSSID

; Number of days to save login cookie for if user selects to remember
; (set to 0 to force expiration at end of current session)
session_lifetime = 30

; Enable support for running scheduled tasks
; Set this to On if you have set up the scheduled tasks script to
; execute periodically
scheduled_tasks = Off

; Short and long date formats
date_format_trunc = "%m-%d"
date_format_short = "%Y-%m-%d"
date_format_long = "%B %e, %Y"
datetime_format_short = "%Y-%m-%d %I:%M %p"
datetime_format_long = "%B %e, %Y - %I:%M %p"


;;;;;;;;;;;;;;;;;;;;;
; Database Settings ;
;;;;;;;;;;;;;;;;;;;;;

[database]

driver = mysql
host = localhost
username = ojs
password = ojs
name = ojs

; Enable persistent connections (recommended)
persistent = On

; Enable database debug output (very verbose!)
debug = Off

;;;;;;;;;;;;;;;;;;
; Cache Settings ;
;;;;;;;;;;;;;;;;;;

[cache]

; The type of data caching to use. Options are:
; - memcache: Use the memcache server configured below
; - file: Use file-based caching; configured below
; - none: Use no caching. This may be extremely slow.

cache = memcache

; Enable memcache support
memcache_hostname = localhost
memcache_port = 11211

; Path to use for file-based caching
file_path = cache

;;;;;;;;;;;;;;;;;;;;;;;;;
; Localization Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;

[i18n]

; Default locale
locale = en_US

; Client output/input character set
client_charset = utf-8

; Database connection character set
; Must be set to "Off" if not supported by the database server
; If enabled, must be the same character set as "client_charset"
; (although the actual name may differ slightly depending on the server)
connection_charset = Off

; Database storage character set
; Must be set to "Off" if not supported by the database server
database_charset = Off


;;;;;;;;;;;;;;;;;
; File Settings ;
;;;;;;;;;;;;;;;;;

[files]

; Complete path to directory to store uploaded files
; (This directory should not be directly web-accessible)
files_dir = files

; Path to the directory to store public uploaded files
; (This directory should be web-accessible and the specified path
; should be relative to the base OJS directory)
public_files_dir = public

; Permissions mask for created files and directories
umask = 0022


;;;;;;;;;;;;;;;;;;;;;
; Security Settings ;
;;;;;;;;;;;;;;;;;;;;;

[security]

; Force SSL connections site-wide
force_ssl = Off

; Force SSL connections for login only
force_login_ssl = Off

; This check will invalidate a session if the user's IP address changes.
; Enabling this option provides some amount of additional security, but may
; cause problems for users behind a proxy farm (e.g., AOL).
session_check_ip = On

; The encryption (hashing) algorithm to use for encrypting user passwords
; Valid values are: md5, sha1
; Note that sha1 requires PHP >= 4.3.0
encryption = md5


;;;;;;;;;;;;;;;;;;
; Email Settings ;
;;;;;;;;;;;;;;;;;;

[email]

; Use SMTP for sending mail instead of mail()
; smtp = On

; SMTP server settings
; smtp_server = mail.example.com
; smtp_port = 25

; Enable SMTP authentication
; Supported mechanisms: PLAIN, LOGIN, CRAM-MD5, and DIGEST-MD5
; smtp_auth = PLAIN
; smtp_username = username
; smtp_password = password

; Allow envelope sender to be specified
; (may not be possible with some server configurations)
; allow_envelope_sender = Off

; Enable attachments in the various "Send Email" pages.
; (Disabling here will not disable attachments on features that
; require them, e.g. attachment-based reviews)
enable_attachments = On


;;;;;;;;;;;;;;;;;;;
; Search Settings ;
;;;;;;;;;;;;;;;;;;;

[search]

; Minimum indexed word length
min_word_length = 3

; The maximum number of search results fetched per keyword. These results
; are fetched and merged to provide results for searches with several keywords.
results_per_keyword = 500

; The number of hours for which keyword search results are cached.
result_cache_hours = 1

; Paths to helper programs for indexing non-text files.
; Programs are assumed to output the converted text to stdout, and "%s" is
; replaced by the file argument.
; Note that using full paths to the binaries is recommended.
; Uncomment applicable lines to enable (at most one per file type).
; Additional "index[MIME_TYPE]" lines can be added for any mime type to be
; indexed.

; PDF
; index[application/pdf] = "/usr/bin/pstotext %s"
; index[application/pdf] = "/usr/bin/pdftotext %s -"

; PostScript
; index[application/postscript] = "/usr/bin/pstotext %s"
; index[application/postscript] = "/usr/bin/ps2ascii %s"

; Microsoft Word
; index[application/msword] = "/usr/bin/antiword %s"
; index[application/msword] = "/usr/bin/catdoc %s"


;;;;;;;;;;;;;;;;
; OAI Settings ;
;;;;;;;;;;;;;;;;

[oai]

; Enable OAI front-end to the site
oai = On

; OAI Repository identifier
repository_id = ojs.pkp.sfu.ca


;;;;;;;;;;;;;;;;;;;;;;
; Interface Settings ;
;;;;;;;;;;;;;;;;;;;;;;

[interface]

; Number of items to display per page; overridable on a per-journal basis
items_per_page = 25

; Number of page links to display; overridable on a per-journal basis
page_links = 10


;;;;;;;;;;;;;;;;;;
; Debug Settings ;
;;;;;;;;;;;;;;;;;;

[debug]

; Display execution stats in the footer
show_stats =  Off

; Display a stack trace when a fatal error occurs.
; Note that this may expose private information and should be disabled
; for any production system.
show_stacktrace = Off

