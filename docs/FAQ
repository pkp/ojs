Frequently Asked Questions
--------------------------

This document contains frequently asked questions about OJS, primarily dealing
with common technical and support questions. For information about using or
interacting with the OJS journal management system, consult the system's
built-in help. The README document contains introductory information and
installation instructions.


=================
General Questions
=================

   1) Who can I contact for support?

A: A support forum for OJS is available at <http://forum.pkp.sfu.ca/>.
   Bugs or feature requests can be reported at
   <https://github.com/pkp/pkp-lib#issues>.
   Although the forum is the preferred method of contact, email inquiries
   regarding OJS can be sent to <pkp.contact@gmail.com>.
   
   Although we will try our best to help you and fix any bugs found in the
   system, please note that OJS comes with no warranty or guarantee of support.

   -----------------------------------------------------------------------------
   
   2) Can I modify the OJS code?
   
A: As OJS is an open source program, you are free to make any code changes that
   you like. We welcome any code submissions or patches to be posted on the
   OJS Development Forum at <http://forum.pkp.sfu.ca/> if you think
   your changes may be beneficial to other OJS users.

   Please note that if you plan on redistributing OJS (either in original or
   a modified form), you must do so according to the terms of the GNU General
   Public License v2. See docs/COPYING for the complete terms of this license.



===================================
Server Configuration and Management
===================================

   1) Will OJS work with PHP as a CGI instead of as an Apache module?

A: OJS is known to work reasonably well with PHP as a CGI using Apache's
   mod_actions. Since there are a number of different ways to configure Apache
   to use the CGI version of PHP (e.g., through FastCGI, etc.), your mileage may
   vary with other deployments (feedback is welcomed).

   On PHP 4 it may be necessary to add "cgi.fix_pathinfo = 1" to the server's
   php.ini to avoid fatal "No input file specified" errors. This parameter is
   enabled by default on PHP 5.
   
   OJS may not function correctly if the base OJS directory is located outside
   of the virtual host's DocumentRoot directory (e.g., when using mod_alias or
   mod_userdir). This is due to a bug/feature in the method PHP uses to
   determine the SCRIPT_NAME environment variable by stripping the DOCUMENT_ROOT
   from SCRIPT_FILENAME.

   -----------------------------------------------------------------------------

   2) Will OJS work correctly in PHP's "safe mode"?

A: OJS is known to work reasonably well with PHP's safe mode restrictions
   (including open_basedir) enabled. Testing has not been extensive, so there
   may be unknown issues, in particular with very restrictive configurations
   that disable PHP functions relied on by OJS (feedback is welcomed).

   -----------------------------------------------------------------------------

   3) Will OJS work in a Microsoft IIS environment, or on other non-Apache web
      servers?

A: OJS 2.0.2 and newer have been tested on Microsoft Windows Server 2003 with
   IIS 6.0; earlier versions of OJS will need to be upgraded before they will
   function. PHP 5.x is required because of an issue with the
   $_SERVER[PATH_INFO] variable. We welcome feedback from users who have
   successfully installed and used OJS in other server environments.

   -----------------------------------------------------------------------------

   4) How can I create a backup of an OJS site?

A: It is strongly recommended that an OJS system be periodically backed up to
   guard against a failure in OJS or the server software or hardware that could
   result in data loss.
   
   To properly backup an OJS system, the following should be backed up:
     - The OJS database, using the tools provided by your DBMS (e.g.,
       mysqldump for MySQL, pg_dump for PostgreSQL)
     - The base OJS directory
     - The non-public files directory (the directory specified by the
       "files_dir" configuration option), which is typically outside of the base
       OJS directory.
       
   This backup procedure can be easily integrated into any automated backup
   mechanism.

   -----------------------------------------------------------------------------

   5) How can I move an existing OJS installation to a different server?

A: To move an OJS system from one server to another, you will need to:

   - Copy the database data, and import it into the new server (e.g., using the
     command-line tools provided by the DBMS)
   - Copy the base OJS directory and non-public files directory
   - Update config.inc.php with any changed settings for the new server
     (typically, the base URL, database authentication/access, email, and files
     settings will differ between servers)



=======================
Common Technical Issues
=======================

   1) When I try to access my OJS site this error message is displayed:
      "Warning: Smarty error: problem creating directory '/./templates' in
      ./lib/smarty/Smarty.class.php"

A: This can occur if a higher level directory has execute permission but not
   read permission. In these situations, the __FILE__ value in an included file
   appears to be relative to the location of the including file. This issue can
   be resolved by ensuring all parent directories are both readable and
   executable.

   See also this PHP bug report: <http://bugs.php.net/bug.php?id=16231>.

   -----------------------------------------------------------------------------

   2) Uploads of large files fail inexplicably.
 
A: This can be caused by certain Apache or PHP settings.

   Apache 2.x has a LimitRequestBody directive that, if set to a low number, can
   lead to this behaviour. In particular, the default PHP packages for recent
   versions of Red Hat Linux set LimitRequestBody to 524288 bytes in
   /etc/httpd/conf.d/php.conf.

   Low values for the PHP ini settings like post_max_size (default "8M"),
   upload_max_filesize (default "8M"), and memory_limit (default "8M") can also
   cause this problem.

   -----------------------------------------------------------------------------

   3) Emails sent out by the system are never received.

A: By default, OJS sends mail through PHP's built-in mail() facility.

   On Windows PHP needs to be configured to send email through a SMTP server
   (running either on the same machine or on another machine).
   
   On other platforms such as Linux and Mac OS X, PHP will sent mail using the
   local sendmail client, so a local MTA such as Sendmail or Postfix must be
   running and configured to allow outgoing mail.
   
   See <http://www.php.net/mail> for more details on configuring PHP's mail
   functionality.
   
   OJS can also be configured to use an SMTP server as specified in
   config.inc.php, either with or without authentication.

   -----------------------------------------------------------------------------

   4) I am using Apache 2.0.x, and OJS pages fail to load, producing an error
      like "File does not exist: .../index.php/index/...".

A: With some versions or configurations of Apache 2.0.x, it may be necessarily
   to explicitly enable the AcceptPathInfo directive in your Apache
   configuration file.
   
   See http://httpd.apache.org/docs-2.0/mod/core.html for more information about
   this directive.

   -----------------------------------------------------------------------------

   5) OJS installation fails with the MySQL error "Client does not support
      authentication protocol requested by server; consider upgrading MySQL
      client".

A: This problem is caused by a change in the MySQL authentication protocol in
   MySQL 4.1, and can occur if your system is using an older MySQL client
   library with a newer MySQL server.

   See http://dev.mysql.com/doc/mysql/en/Old_client.html for suggested
   approaches to resolve this issue.

   -----------------------------------------------------------------------------

   6) "Warning: ini_set(): A session is active. You cannot change the session
       module's ini settings at this time" messages appear when I load OJS.

A: Check if session.auto_start is enabled in your php.ini configuration. OJS
   requires this setting to be disabled, which is the default behaviour in
   current versions of PHP.

   -----------------------------------------------------------------------------

   7) The installation form loads successfully, but after clicking the button
       to install, a blank page appears and the database was not created.

A: This may indicate that your server does not have the selected PHP database
   module installed and enabled (this can be verified by looking at the output
   of phpinfo() to see if the required database support exists -- see
   http://php.net/phpinfo).
   
   The OJS installer lists database drivers for which the required PHP
   extension does not appear to be loaded in brackets (e.g., "[MySQL]").
   
   Most Linux distributions offer a separate package that can be installed for
   each supported PHP database module -- e.g., php4-mysql or php-mysql (for
   MySQL support), or php4-pgsql or php-pgsql (for PostgreSQL support).
   
   Note also that even with the module installed it may be necessary to modify
   your php.ini configuration to load the module, by adding "extension=mysql.so"
   or "extension=pgsql.so", for example.



==========================
Advanced OJS Configuration
==========================

   1) How can I control the address to which bounced emails will be sent?
      Why do messages sent with an envelope sender have an 'X-Warning' header?

A: To control the address to which a bounced emails will be sent, you need to
   set the envelope sender address. Enable the "allow_envelope_sender" option
   in the [email] section of the OJS 2.x configuration file; when this option
   is enabled, a "Bounce Address" field appears on Page 2 of the Journal Setup.

   Note that this option may require changes to the server's mail server
   configuration so that the user the web server runs as (e.g. "www-data") is
   trusted by the sendmail program, or an "X-Warning" header will be added to
   outgoing messages. Consult your mail server's documentation if outgoing mails
   include such a header.
   
   For example, Sendmail keeps a list of trusted users in the file
   "/etc/mail/trusted-users";  other mail systems use similar files.
   The command-line option used by OJS 2.x to set the envelope sender is "-f".

   -----------------------------------------------------------------------------

   2) How can I allow users to search non-text files, such as PDF or Microsoft
      Word documents?

A: OJS supports indexing of non-text files via external conversion applications.
   The "Search Settings" configuration section in config.inc.php can be modified
   to enable indexing of certain binary file formats by setting a
   "index[MIME_TYPE]" setting (with the desired file mime-type) to the path of
   the appropriate external text converter for that file format.

   Note that additional third-party software must be installed to use this
   feature (such as "pdftotext" for PDF files).

   -----------------------------------------------------------------------------

   3) I am changing the URL to my OJS installation. Does OJS need to be
      reconfigured to be accessible under the new URL?

A: The only reconfiguration that is required after a change in the site URL is
   to update the value of the "base_url" configuration setting in
   config.inc.php.
   
   -----------------------------------------------------------------------------

   4) How can I remove "index.php" from the URLs in OJS?

A: OJS uses a REST-style URL syntax for all of its links.  To force OJS to remove the
   "index.php" portion of all URLs, edit config.inc.php and set "restful_urls" to "On".
   
   In addition, your server will have to support URL rewriting in order to recognize
   the new URLs.  Apache servers use the mod_rewrite plugin, which must be enabled
   in your httpd.conf, and the following section added to the correct section of either
   your httpd.conf or an .htaccess file (preferred) in your OJS root directory (the same
   location as config.inc.php):
   
   <IfModule mod_rewrite.c>
   RewriteEngine on
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteRule ^(.*)$ index.php/$1 [QSA,L]
  </IfModule>
