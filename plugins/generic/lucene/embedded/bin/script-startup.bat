:: This file initializes common variables.
:: It is not meant to be executed directly
:: but will be sourced by other scripts.

:: Identify our plug-in base directory.
PUSHD .
CD %~dp0..\..
IF %ERRORLEVEL% NEQ 0 EXIT /B %ERRORLEVEL%
SET PLUGIN_DIR=%CD%

:: OJS directories
CD ..\..\..
IF %ERRORLEVEL% NEQ 0 EXIT /B %ERRORLEVEL%
SET OJS_DIR=%CD%
FOR /F "tokens=*" %%A IN (
  'FINDSTR /R /C:"\<files_dir\>" config.inc.php'
) DO (
  FOR /F "tokens=2 delims==" %%B IN ("%%A") DO (
    CALL SET "OJS_FILES=%%B"
  )
)
CD %OJS_FILES%
IF %ERRORLEVEL% NEQ 0 (
  ECHO "We did not find the location of the OJS files directory or the files directory is not writable."
  EXIT /B %ERRORLEVEL%
)
SET OJS_FILES=%CD%
POPD

SET LUCENE_FILES=%OJS_FILES%\lucene
IF NOT EXIST "%LUCENE_FILES%" (
  MD "%LUCENE_FILES%"
)

:: Set the path to the solr PID file.
SET SOLR_PIDFILE=%OJS_FILES%\lucene\solr.pid

:: Get the process identifier.
SET PROC_SECRET=
IF EXIST %SOLR_PIDFILE% (
  SET /P PROC_SECRET= < %SOLR_PIDFILE%
)

:: Check whether solr is running.
SET SOLR_RUNNING=false
IF DEFINED PROC_SECRET (
  FOR /F "tokens=*" %%A IN (
    'WMIC PROCESS GET CommandLine ^| FINDSTR /R /C:"^java.*%PROC_SECRET%"'
  ) DO (
    CALL SET "SOLR_RUNNING=true"
  )
)

:: The Jetty home directory
SET JETTY_HOME=%PLUGIN_DIR%\lib\jetty
