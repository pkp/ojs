; <?php exit; // DO NOT DELETE?>
; DO NOT DELETE THE ABOVE LINE!!!
; Doing so will expose this configuration file through your web site!
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; config.TEMPLATE.inc.php
;
; Copyright (c) 2014-2021 Simon Fraser University
; Copyright (c) 2003-2021 John Willinsky
; Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
;
; OJS Configuration settings.
; Rename config.TEMPLATE.inc.php to config.inc.php to use.
;
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
base_url = "https://pkp.sfu.ca/ojs"

; Enable strict mode. This will more aggressively cause errors/warnings when
; deprecated behaviour exists in the codebase.
strict = Off

; Session cookie name
session_cookie_name = OJSSID

; Session cookie path; if not specified, defaults to the detected base path
; session_cookie_path = /

; Number of days to save login cookie for if user selects to remember
; (set to 0 to force expiration at end of current session)
session_lifetime = 30

; SameSite configuration for the cookie, see possible values and explanations
; at https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite
; To set the "Secure" attribute for the cookie see the setting force_ssl at the [security] group
session_samesite = Lax

; Enable support for running scheduled tasks
; Set this to On if you have set up the scheduled tasks script to
; execute periodically
scheduled_tasks = Off

; Site time zone
; Please refer to https://www.php.net/timezones for a full list of supported
; time zones.
; I.e.: "Europe/Amsterdam"
; time_zone="Europe/Amsterdam"
time_zone = "UTC"

; Short and long date formats
date_format_short = "Y-m-d"
date_format_long = "F j, Y"
datetime_format_short = "Y-m-d h:i A"
datetime_format_long = "F j, Y - h:i A"
time_format = "h:i A"

; Use fopen(...) for URL-based reads. Modern versions of dspace
; will not accept requests using fopen, as it does not provide a
; User Agent, so this option is disabled by default. If this feature
; is disabled by PHP's configuration, this setting will be ignored.
allow_url_fopen = Off

; Base URL override settings: Entries like the following examples can
; be used to override the base URLs used by OJS. If you want to use a
; proxy to rewrite URLs to OJS, configure your proxy's URL with this format.
; Syntax: base_url[journal_path] = http://www.example.com
;
; Example1: URLs that aren't part of a particular journal.
;    Example1: base_url[index] = http://www.example.com
; Example2: URLs that map to a subdirectory.
;    Example2: base_url[myJournal] = http://www.example.com/myJournal
; Example3: URLs that map to a subdomain.
;    Example3: base_url[myOtherJournal] = http://myOtherJournal.example.com

; Generate RESTful URLs using mod_rewrite.  This requires the
; rewrite directive to be enabled in your .htaccess or httpd.conf.
; See FAQ for more details.
restful_urls = Off

; Restrict the list of allowed hosts to prevent HOST header injection.
; See docs/README.md for more details. The list should be JSON-formatted.
; An empty string indicates that all hosts should be trusted (not recommended!)
; Example:
; allowed_hosts = '["myjournal.tld", "anotherjournal.tld", "mylibrary.tld"]'
allowed_hosts = ''

; Allow the X_FORWARDED_FOR header to override the REMOTE_ADDR as the source IP
; Set this to "On" if you are behind a reverse proxy and you control the X_FORWARDED_FOR
; Warning: This defaults to "On" if unset for backwards compatibility.
trust_x_forwarded_for = Off

; Set the maximum number of citation checking processes that may run in parallel.
; Too high a value can increase server load and lead to too many parallel outgoing
; requests to citation checking web services. Too low a value can lead to significantly
; slower citation checking performance. A reasonable value is probably between 3
; and 10. The more your connection bandwidth allows the better.
citation_checking_max_processes = 3

; Display a message on the site admin and journal manager user home pages if there is an upgrade available
show_upgrade_warning = On

; Set the following parameter to off if you want to work with the uncompiled (non-minified) JavaScript
; source for debugging or if you are working off a development branch without compiled JavaScript.
enable_minified = On

; Provide a unique site ID and OAI base URL to PKP for statistics and security
; alert purposes only.
enable_beacon = On

; Set this to "On" if you would like to only have a single, site-wide Privacy
; Statement, rather than a separate Privacy Statement for each journal. Setting
; this to "Off" will allow you to enter a site-wide Privacy Statement as well
; as separate Privacy Statements for each journal.
sitewide_privacy_statement = Off

; The number of days a new user has to validate their account
; A new user account will be expired and removed if this many days have passed since the user registered
; their account, and they have not validated their account or logged in. If the user_validation_period is set to
; 0, unvalidated accounts will never be removed. Use this setting to automatically remove bot registrations.
user_validation_period = 28

; Turn sandbox mode to On in order to prevent the software from interacting with outside systems.
; Use this for development or testing purposes.
sandbox = Off


;;;;;;;;;;;;;;;;;;;;;
; Database Settings ;
;;;;;;;;;;;;;;;;;;;;;

[database]

driver = mysqli
host = localhost
username = ojs
password = ojs
name = ojs

; Set the non-standard port and/or socket, if used
; port = 3306
; unix_socket = /var/run/mysqld/mysqld.sock

; Database collation
; collation = utf8_general_ci

; Enable database debug output (very verbose!)
debug = Off

;;;;;;;;;;;;;;;;;;
; Cache Settings ;
;;;;;;;;;;;;;;;;;;

[cache]

; Choose the type of object data caching to use. Options are:
; - memcache: Use the memcache server configured below
; - xcache: Use the xcache variable store
; - apc: Use the APC variable store
; - none: Use no caching.
object_cache = none

; Enable memcache support
memcache_hostname = localhost
memcache_port = 11211

; For site visitors who are not logged in, many pages are often entirely
; static (e.g. About, the home page, etc). If the option below is enabled,
; these pages will be cached in local flat files for the number of hours
; specified in the web_cache_hours option. This will cut down on server
; overhead for many requests, but should be used with caution because:
; 1) Things like journal metadata changes will not be reflected in cached
;    data until the cache expires or is cleared, and
; 2) This caching WILL NOT RESPECT DOMAIN-BASED SUBSCRIPTIONS.
; However, for situations like hosting high-volume open access journals, it's
; an easy way of decreasing server load.
;
; When using web_cache, configure a tool to periodically clear out cache files
; such as CRON. For example, configure it to run the following command:
; find .../ojs/cache -maxdepth 1 -name wc-\*.html -mtime +1 -exec rm "{}" ";"
web_cache = Off
web_cache_hours = 1


;;;;;;;;;;;;;;;;;;;;;;;;;
; Localization Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;

[i18n]

; Default locale
locale = en

; Database connection character set
connection_charset = utf8


;;;;;;;;;;;;;;;;;
; File Settings ;
;;;;;;;;;;;;;;;;;

[files]

; Complete path to directory to store uploaded files
; (This directory should not be directly web-accessible)
; Windows users should use forward slashes
files_dir = files

; Path to the directory to store public uploaded files
; (This directory should be web-accessible and the specified path
; should be relative to the base OJS directory)
; Windows users should use forward slashes
public_files_dir = public

; The maximum allowed size in kilobytes of each user's public files
; directory. This is where user's can upload images through the
; tinymce editor to their bio. Editors can upload images for
; some of the settings.
; Set this to 0 to disallow such uploads.
public_user_dir_size = 5000

; Permissions mask for created files and directories
umask = 0022

; The minimum percentage similarity between filenames that should be considered
; a possible revision
filename_revision_match = 70


;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; Fileinfo (MIME) Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;

[finfo]
; mime_database_path = /etc/magic.mime


;;;;;;;;;;;;;;;;;;;;;
; Security Settings ;
;;;;;;;;;;;;;;;;;;;;;

[security]

; Force SSL connections site-wide and also sets the "Secure" flag for session cookies
; See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#secure
force_ssl = Off

; Force SSL connections for login only
force_login_ssl = Off

; This check will invalidate a session if the user's IP address changes.
; Enabling this option provides some amount of additional security, but may
; cause problems for users behind a proxy farm (e.g., AOL).
session_check_ip = On

; The encryption (hashing) algorithm to use for encrypting user passwords
; Valid values are: md5, sha1
; NOTE: This hashing method is deprecated, but necessary to permit gradual
; migration of old password hashes.
encryption = sha1

; The unique salt to use for generating password reset hashes
salt = "YouMustSetASecretKeyHere!!"

; The unique secret used for encoding and decoding API keys
api_key_secret = ""

; The number of seconds before a password reset hash expires (defaults to 7200 / 2 hours)
reset_seconds = 7200

; Allowed HTML tags for fields that permit restricted HTML.
; Use e.g. "img[src,alt],p" to allow "src" and "alt" attributes to the "img"
; tag, and also to permit the "p" paragraph tag. Unspecified attributes will be
; stripped.
allowed_html = "a[href|target|title],em,strong,cite,code,ul,ol,li[class],dl,dt,dd,b,i,u,img[src|alt],sup,sub,br,p"

; Allowed HTML tags for submission titles only
; Unspecified attributes will be stripped.
allowed_title_html = "b,i,u,sup,sub"

;N.b.: The implicit_auth parameter has been removed in favor of plugin implementations such as shibboleth

;;;;;;;;;;;;;;;;;;
; Email Settings ;
;;;;;;;;;;;;;;;;;;

[email]

; Default method to send emails
; Available options: sendmail, smtp, log, phpmailer
default = sendmail

; Path to the sendmail, -bs argument is for using SMTP protocol
sendmail_path = "/usr/sbin/sendmail -bs"

; Use SMTP for sending mail instead of mail()
; smtp = On

; SMTP server settings
; smtp_server = mail.example.com
; smtp_port = 25

; Enable SMTP authentication
; Supported smtp_auth: ssl, tls (see PHPMailer SMTPSecure)
; smtp_auth = ssl
; smtp_username = username
; smtp_password = password

; Enable suppressing SSL/TLS peer verification by SMTP transports
; Note: this is not recommended for security reasons
; smtp_suppress_cert_check = On

; Allow envelope sender to be specified
; (may not be possible with some server configurations)
; allow_envelope_sender = Off

; Default envelope sender to use if none is specified elsewhere
; default_envelope_sender = my_address@my_host.com

; Force the default envelope sender (if present)
; This is useful if setting up a site-wide no-reply address
; The reply-to field will be set with the reply-to or from address.
; force_default_envelope_sender = Off

; Force a DMARC compliant from header (RFC5322.From)
; If any of your users have email addresses in domains not under your control
; you may need to set this to be compliant with DMARC policies published by
; those 3rd party domains.
; Setting this will move the users address into the reply-to field and the
; from field wil be rewritten with the default_envelope_sender.
; To use this you must set force_default_enveloper_sender = On and
; default_envelope_sender must be set to a valid address in a domain you own.
; force_dmarc_compliant_from = Off

; The display name to use with a DMARC compliant from header
; By default the DMARC compliant from will have an empty name but this can
; be changed by adding a text here.
; You can use '%n' to insert the users name from the original from header
; and '%s' to insert the localized sitename.
; dmarc_compliant_from_displayname = '%n via %s'

; If enabled, email addresses must be validated before login is possible.
require_validation = Off

; Maximum number of days before an unvalidated account expires and is deleted
validation_timeout = 14


;;;;;;;;;;;;;;;;;;;
; Search Settings ;
;;;;;;;;;;;;;;;;;;;

[search]

; Minimum indexed word length
min_word_length = 3

; The maximum number of search results fetched per keyword. These results
; are fetched and merged to provide results for searches with several keywords.
results_per_keyword = 500

; Paths to helper programs for indexing non-text files.
; Programs are assumed to output the converted text to stdout, and "%s" is
; replaced by the file argument.
; Note that using full paths to the binaries is recommended.
; Uncomment applicable lines to enable (at most one per file type).
; Additional "index[MIME_TYPE]" lines can be added for any mime type to be
; indexed.

; PDF
; index[application/pdf] = "/usr/bin/pstotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"
; index[application/pdf] = "/usr/bin/pdftotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"

; PostScript
; index[application/postscript] = "/usr/bin/pstotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"
; index[application/postscript] = "/usr/bin/ps2ascii %s | /usr/bin/tr '[:cntrl:]' ' '"

; Microsoft Word
; index[application/msword] = "/usr/bin/antiword %s"
; index[application/msword] = "/usr/bin/catdoc %s"


;;;;;;;;;;;;;;;;
; OAI Settings ;
;;;;;;;;;;;;;;;;

[oai]

; Enable OAI front-end to the site
oai = On

; OAI Repository identifier. This setting forms part of OAI-PMH record IDs.
; Changing this setting may affect existing clients and is not recommended.
repository_id = ojs.pkp.sfu.ca

; Maximum number of records per request to serve via OAI
oai_max_records = 100

;;;;;;;;;;;;;;;;;;;;;;
; Interface Settings ;
;;;;;;;;;;;;;;;;;;;;;;

[interface]

; Number of items to display per page; can be overridden on a per-journal basis
items_per_page = 25

; Number of page links to display; can be overridden on a per-journal basis
page_links = 10


;;;;;;;;;;;;;;;;;;;;
; Captcha Settings ;
;;;;;;;;;;;;;;;;;;;;

[captcha]

; Whether or not to enable ReCaptcha
recaptcha = off

; Public key for reCaptcha (see http://www.google.com/recaptcha)
recaptcha_public_key = your_public_key

; Private key for reCaptcha (see http://www.google.com/recaptcha)
recaptcha_private_key = your_private_key

; Whether or not to use Captcha on user registration
captcha_on_register = on

; Whether or not to use Captcha on user login
captcha_on_login = on

; Validate the hostname in the ReCaptcha response
recaptcha_enforce_hostname = Off

;;;;;;;;;;;;;;;;;;;;;
; External Commands ;
;;;;;;;;;;;;;;;;;;;;;

[cli]

; These are paths to (optional) external binaries used in
; certain plug-ins or advanced program features.

; Using full paths to the binaries is recommended.

; tar (used in backup plugin, translation packaging)
tar = /bin/tar

; On systems that do not have libxsl/xslt libraries installed, or for those who
; require a specific XSLT processor, you may enter the complete path to the
; XSLT renderer tool, with any required arguments. Use %xsl to substitute the
; location of the XSL stylesheet file, and %xml for the location of the XML
; source file; eg:
; /usr/bin/java -jar ~/java/xalan.jar -HTML -IN %xml -XSL %xsl
xslt_command = ""

;;;;;;;;;;;;;;;;;;
; Proxy Settings ;
;;;;;;;;;;;;;;;;;;

[proxy]

; The HTTP proxy configuration to use
; http_proxy = "http://username:password@192.168.1.1:8080"
; https_proxy = "https://username:password@192.168.1.1:8080"


;;;;;;;;;;;;;;;;;;
; Debug Settings ;
;;;;;;;;;;;;;;;;;;

[debug]

; Display a stack trace when a fatal error occurs.
; Note that this may expose private information and should be disabled
; for any production system.
show_stacktrace = Off

; Display an error message when something goes wrong.
display_errors = Off

; Display deprecation warnings
deprecation_warnings = Off

; Log web service request information for debugging
log_web_service_info = Off

; declare a cainfo path if a certificate other than PHP's default should be used for curl calls.
; This setting overrides the 'curl.cainfo' parameter of the php.ini configuration file.
[curl]
; cainfo = ""


;;;;;;;;;;;;;;;;;;;;;;;
; Job Queues Settings ;
;;;;;;;;;;;;;;;;;;;;;;;

[queues]

; Default queue driver
default_connection = "database"

; Default queue to use when a job is added to the queue
default_queue = "queue"

; Whether or not to turn on the built-in job runner
;
; When enabled, jobs will be processed at the end of each web
; request to the application.
;
; Use of the built-in job runner is highly discouraged for high-volume
; sites. Instead, a worker daemon or cron job should be configured
; to process jobs off the application's main thread.
;
; See: https://docs.pkp.sfu.ca/admin-guide/en/deploy-jobs
;
job_runner = On

; The maximum number of jobs to run in a single request when using
; the built-in job runner.
job_runner_max_jobs = 30

; The maximum number of seconds the built-in job runner should spend
; running jobs in a single request.
;
; This should be less than the max_execution_time the server has
; configured for PHP.
;
; Lower this setting if jobs are failing due to timeouts.
job_runner_max_execution_time = 30

; The maximum consumerable memory that should be spent by the built-in
; job runner when running jobs.
;
; Set as a percentage, such as 80%:
;
; job_runner_max_memory = 80
;
; Or set as a fixed value in megabytes:
;
; job_runner_max_memory = 128M
;
; When setting a fixed value in megabytes, this should be less than the
; memory_limit the server has configured for PHP.
job_runner_max_memory = 80

; Remove failed jobs from the database after the following number of days.
; Remove this setting to leave failed jobs in the database.
delete_failed_jobs_after = 180
