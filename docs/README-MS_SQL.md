	=================================
	=== Microsoft SQL Server (MS SQL)
	=================================



------------
Introduction
------------

MS SQL could in theory be supported with all versions of PHP. Since supporting all the oldest versions of the tools
needed for the integration of the database can be time consuming, we started the project with the most recent versions.

For instance, our current development platform currently (2018-04) runs on the ones described below.

	- Windows 10 version 1607
	- Internet Information Services (IIS) 10.0.14393.0
	- Microsoft SQL Server Enterprise (64-bit) 13.0.2218.0
	- PHP 7.1.16
	- Microsoft Drivers for PHP for SQL Server 5.2 (supporting PHP 7.*)
	- Microsoft ODBC Driver for SQL Server 17.1.0.1

Required:

	- PHP 7.*
	- Microsoft Drivers for PHP for SQL Server 4.3+
	- Microsoft ODBC Driver for SQL Server 13+

As mentioned before, we don't have the time to test and correct all the possible combinations for this support. We
strongly suggest to use as much as possible the above configuration.



--------------
PDO SQL Server
--------------

1. Open the link below.

   https://github.com/Microsoft/msphpsql/releases

2. According to the installed PHP version, download the DLL package for Microsoft SQL Server.
3. Rename the PDO file to "php_pdo_sqlsrv.dll".
4. Add this file to the "ext" folder in the PHP installation.



------------------------------------
Microsoft ODBC Driver for SQL Server
------------------------------------

1. Open the link below.

   https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server

2. Depending on which PHP driver is used, download the according ODBC driver.
3. Install the driver.



------------------
Configuration File
------------------

Add the following lines in "config.inc.php".

ms_sql = On
driver = pdo_mssql
host = sqlsrv:server=SERVER\\INSTANCE

Note: within the setup home page, if an instance is required, only put one backslash in the host section.



------
Update
------

The update contains multiple changes in the OJS code. The major ones are described below.

	- The columns "current" have been renamed "actual" to avoid conflict with reserved keywords.
	- Added a configuration (ms_sql) to adapt SQL queries without impacting existing code.
	- Modified many files to support PHP 7 by preventing the application freezing because of an unexpected error.
	- Removed a duplicated schema (views) in the installation file (install.xml).
	- Modified field types from "X" to "XL" as some of them was not saving big values.
	- Added a missing parameter (publishingMode) in the journal settings configuration file.
	- Illuminate database library has been updated to the latest version from "4.1.*" to "5.6.*".
	- ADOdb has been updated to "5.20.12".
