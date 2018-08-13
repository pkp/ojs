	===================================
	=== Open Journal Systems
	=== The Public Knowledge Project
	=== Version: 3.2.0
	=== GIT tag: ojs-3_2_0-0
	=== Release date: 
	===================================


## About

Open Journal Systems (OJS) has been developed by the Public Knowledge Project.
For general information about OJS and other open research systems, visit the
PKP web site at <http://pkp.sfu.ca/>.


## License

OJS is licensed under the GNU General Public License v2. See the file
[docs/COPYING](COPYING) for the complete terms of this license.

Third parties are welcome to modify and redistribute OJS in entirety or parts
according to the terms of this license. PKP also welcomes patches for
improvements or bug fixes to the software.


## System Requirements

Recommended server requirements:

* PHP >= 5.6
* MySQL >= 4.1 or PostgreSQL >= 9.1.5
* Apache >= 1.3.2x or >= 2.0.4x or Microsoft IIS 6
* Operating system: Any OS that supports the above software, including
	Linux, BSD, Solaris, Mac OS X, Windows

As PKP does not have the resources to test every possible combination of
software versions and platforms, no guarantee of correct operation or support
is implied. We welcome feedback from users who have deployed OJS on systems
other than those listed above.


## Recommended Configuration

A secure deployment can be best achieved by using the following policies:

* Dedicate a database to OJS; use unique credentials to access it.
	Configure this database to perform automated backups on a regular
	basis. Perform a manual backup when upgrading or performing
	maintenance.

* Configure OJS (`config.inc.php`) to use SHA1 hashing rather than MD5.

* Configure OJS (`config.inc.php`) to use force_ssl_login so that
	authenticated users communicate with the server via HTTPS.

* Install OJS so that the files directory is NOT a subdirectory of
	the OJS installation and cannot be accessed directly via the web
	server. Restrict file permissions as much as possible. Automated
	backups of this directory should be roughly synchronized with
	database backups.


## Installation

Please review this document and the [RELEASE](RELEASE) document prior to installing OJS.
If you encounter problems, please also see the [FAQ](FAQ) document in this directory.

To install OJS:

1. Extract the OJS archive to the desired location in your web
	 documents directory.

2. Make the following files and directories (and their contents)
	 writeable (i.e., by changing the owner or permissions with chown or
	 chmod):
	 
	 * `config.inc.php` (optional -- if not writable you will be prompted
		 to manually overwrite this file during installation)
	 * `public`
	 * `cache`
	 * `cache/t_cache`
	 * `cache/t_config`
	 * `cache/t_compile`
	 * `cache/_db`

3. Create a directory to store uploaded files (submission files, etc.)
	 and make this directory writeable. It is recommended that this
	 directory be placed in a non-web-accessible location (or otherwise
	 protected from direct access, such as via .htaccess rules).
	 
4. Open a web browser to http://yourdomain.com/path/to/ojs/ and
	 follow the on-screen installation instructions.
	 
	 Alternatively, the command-line installer can be used instead by
	 running the command `php tools/install.php` from your OJS directory.
	 (Note: with the CLI installer you may need to chown/chmod the public
	 and uploaded files directories after installation, if the Apache
	 user is different from the user running the tool.)

5. Recommended additional steps post-installation:

	 * Review `config.inc.php` for additional configuration settings
	 * Review the FAQ document for frequently asked technical and
		 server configuration questions.


## Upgrading

See [docs/UPGRADE.md](UPGRADE.md) for information on upgrading from previous OJS releases.


## Localization

To add support for other languages, the following sets of XML files must be
localized and placed in an appropriately named directory (using ISO locale 
codes, e.g. `fr_FR`, is recommended):

* `locale/en_US`
* `lib/pkp/locale/en_US`
* `docs/manual/en`
* `registry/locale/en_US`
* `plugins/[plugin category]/[plugin name]/locale`, where applicable

The only critical files that need translation for the system to function
properly are found in `locale/en_US`, `lib/pkp/locale/en_US`, and
`registry/locale/en_US`.

New locales must also be added to the file `registry/locales.xml`, after which
they can be installed in the system through the site administration web
interface.
	
Translations can be contributed back to PKP for distribution with future
releases of OJS.


## Scheduled Tasks

OJS supports a mechanism to execute a variety of tasks at scheduled times
(such as automatic sending of reminder notification emails).

To enable support for using scheduled tasks, edit your `config.inc.php` and
set the `scheduled_tasks` setting to `On`, and set up your operating system to
periodically execute (as the same user your webserver is running under) the
PHP script found at `tools/runScheduledTasks.php` in your OJS directory:

On *nix operating systems, this can be done by adding a simple cron task:
```
# crontab -e www
0 * * * *	php /path/to/ojs/tools/runScheduledTasks.php
```
In this example the script would be executed every hour.

On Windows XP systems, this can be done by using the Windows Task Scheduler:
1) From the Control Panel, double-click on Scheduled Tasks.
2) Right-click within the Scheduled Tasks window and choose:
	New > Scheduled Task
3) Under the Task tab, in the Run field, enter:
	php c:\path\to\ojs\tools\runScheduledTasks.php
4) You will also be asked to specify the folder to start this task in
	 (which will usually be the folder that PHP was installed into) and
	 the user under which the task will be executed as.
5) Under Schedule tab and the Settings tab, you can more specifically
	 configure the task. For example, you can choose start and end dates
	 for this scheduled task and also how often to execute this task.
	   
If using the scheduled tasks script, it is recommended that the script be
set up to execute at least once per day.

Note that using the script also requires you to have the PHP command-line
interpreter installed on your server.


## Third-party Libraries

* See [lib/pkp/lib/libraries.txt](../lib/pkp/lib/libraries.txt) for a list of third-party libraries
	used by OJS.

* OJS supports the legacy GeoLiteCite database to approximate geolocation
	information for usage statistics. If you would like to use this optional
	functionality, you can download the database from MaxMind at:
	http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
	You will need to decompress the file and place "GeoLiteCity.dat" into
	the `plugins/generic/usageStats` directory. A separate license agreement
	is required for this use of this database. For details, see:
	https://dev.maxmind.com/geoip/legacy/geolite/

## Contact/Support

The forum is the recommended method of contacting the team with technical
issues.

* Forum: http://forum.pkp.sfu.ca/
* Bugs: https://github.com/pkp/pkp-lib#issues
* Email: pkp.contact@gmail.com
