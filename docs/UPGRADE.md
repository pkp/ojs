# Upgrading an OJS Installation

Note: Before upgrading your installation, perform a complete backup of your
data files and database. If the upgrade process fails, you will need to recover
from backup before continuing.

If you are using PHP Safe Mode, please ensure that the max_execution_time
directive in your php.ini configuration file is set to a high limit. If this
or any other time limit (e.g. Apache's "Timeout" directive) is reached and
the upgrade process is interrupted, manual intervention will be required.


## Upgrading from OJS 2.0.x, 2.1.x, 2.2.x, or 2.3.x

See [docs/UPGRADE-UNSUPPORTED](UPGRADE-UNSUPPORTED.md).


## Upgrading from OJS 2.4.x

OJS 3.x is a major rewrite of Open Journal Systems, introducing numerous new
concepts and different approaches. The upgrade process from 2.x to 3.x does its
best to adapt old content to the new structures, but we strongly recommend
performing a test upgrade and exploring the new system before committing your
content to the upgrade. Downgrades from 3.x to 2.x will not be supported.

Note that upgrading from OJS 2.4.x to OJS 3.0 will rearrange your submission
file storage, so be sure to back it up before running an upgrade.

With that noted, follow the OJS 3.0 process described below.


## Upgrading from OJS 3.x

Upgrading to the latest version of OJS involves two steps:

- [Obtaining the latest OJS code](#obtaining-the-latest-ojs-code)
- [Upgrading the OJS database](#upgrading-the-ojs-database)

It is highly recommended that you also review the release notes ([docs/RELEASE](RELEASE))
and other documentation in the docs directory before performing an upgrade.


### Obtaining the latest OJS code

The OJS source code is available in two forms: a complete stand-alone 
package, and from read-only github access.

#### 1. Full Package

If you have not made local code modifications to the system, upgrade by 
downloading the complete package for the latest release of OJS:

- Download and decompress the package from the OJS web site into an empty
	directory (NOT over top of your current OJS installation)
- Move or copy the following files and directories into it from your current
	OJS installation:
		- config.inc.php
		- public/
		- Your uploaded files directory ("files_dir" in config.inc.php), if it
			resides within your OJS directory
- Synchronize new changes from config.TEMPLATE.inc.php to config.inc.php
- Replace the current OJS directory with the new OJS directory, moving the
	old one to a safe location as a backup
- Be sure to review the Configuration Changes section of the release notes
	in docs/release-notes/README-(version) for all versions between your
	original version and the new version. You may need to manually add
	new items to your config.inc.php file.


#### 2. git

Updating from github is the recommended approach if you have made local
modifications to the system.

If your instance of OJS was checked out from github (see [docs/README-GIT.md](README-GIT.md)),
you can update the OJS code using a git client.

To update the OJS code from a git check-out, run the following command from
your OJS directory:

```
$ git rebase --onto <new-release-tag> <previous-release-tag>
```

This assumes that you have made local changes and committed them on top of
the old release tag. The command will take your custom changes and apply
them on top of the new release. This may cause merge conflicts which have to
be resolved in the usual way, e.g. using a merge tool like kdiff3.

"TAG" should be replaced with the git tag corresponding to the new release.
OJS release version tags are of the form "ojs-MAJOR_MINOR_REVSION-BUILD".
For example, the tag for the initial release of OJS 3.0.0 is "ojs-3_0_0-0".

Consult the [README](README.md) of the latest OJS package or the OJS web site for the
tag corresponding to the latest available OJS release.

Note that attempting to update to an unreleased version (e.g., using the HEAD
tag to obtain the bleeding-edge OJS code) is not recommended for anyone other
than OJS or third-party developers; using experimental code on a production
deployment is strongly discouraged and will not be supported in any way by
the OJS team.


### Upgrading the OJS database

After obtaining the latest OJS code, an additional script must be run to
upgrade the OJS database.

NOTE: Patches to the included ADODB library may be required for PostgreSQL
upgrades; see https://forum.pkp.sfu.ca/t/upgrade-failure-postgresql/19215

This script can be executed from the command-line or via the OJS web interface.

#### 1. Command-line

If you have the CLI version of PHP installed (e.g., `/usr/bin/php`), you can
upgrade the database as follows:

- Edit config.inc.php and change "installed = On" to "installed = Off"
- Run the following command from the OJS directory (not including the $):
	`$ php tools/upgrade.php upgrade`
- Re-edit config.inc.php and change "installed = Off" back to
	 "installed = On"

#### 2. Web

If you do not have the PHP CLI installed, you can also upgrade by running a
web-based script. To do so:

- Edit config.inc.php and change "installed = On" to "installed = Off"
- Open a web browser to your OJS site; you should be redirected to the
	installation and upgrade page
- Select the "Upgrade" link and follow the on-screen instructions
- Re-edit config.inc.php and change "installed = Off" back to
	 "installed = On"

### Update Javascript libraries and build.js

The official .tar.gz releases, and the stable branches in git (e.g.
`ojs-stable-3_1_1`), contain precompiled javascript. If you are installing
OJS using either of those and have not modified your Javascript, you do not
need to compile Javascript.

If you are using the git `master` branch, or have made changes to your
Javascript code, you will need to recompile it following these instructions.

To update the Javascript libraries and rebuild the build.js you have to run
```
npm install
npm run build
```
