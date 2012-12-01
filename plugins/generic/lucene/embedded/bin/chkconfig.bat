:: This script is ensures that the embedded configuration works on Windows
:: servers. Please execute it once after creating the 'sorl' and 'jetty' folders
:: or symlinks in 'plugins\generic\lucene\lib' as described in the README file
:: that comes together with this plugin.
::
:: Usage: chkconfig.bat

@ECHO off
SETLOCAL ENABLEEXTENSIONS
SETLOCAL ENABLEDELAYEDEXPANSION

:: Source common variables.
CALL %~dp0script-startup.bat

:: Delete existing links.
FOR %%A IN (contrib dist webapps) DO (
  SET "SYMLINK=%PLUGIN_DIR%\embedded\%%A"
  IF EXIST "!SYMLINK!" (
    PUSHD "!SYMLINK!" 2>NUL && (POPD & SET SYMTYPE=folder) || SET SYMTYPE=file
  ) else (
    SET SYMTYPE=none
  )
  IF !SYMTYPE! == file (
    DEL /A "!SYMLINK!" 2>NUL
  )
  IF !SYMTYPE! == folder (
    RMDIR "!SYMLINK!" 2>NUL
  )
)

:: (Re-)create links.
MKLINK /D "%PLUGIN_DIR%\embedded\contrib" ..\lib\solr\contrib >NUL 2>&1
MKLINK /D "%PLUGIN_DIR%\embedded\dist" ..\lib\solr\dist >NUL 2>&1
MKLINK /D "%PLUGIN_DIR%\embedded\webapps" ..\lib\solr\example\webapps >NUL 2>&1

SET ERROR=false

:: Check availability of jetty.
IF NOT EXIST "%PLUGIN_DIR%\lib\jetty\start.jar" (
  ECHO Jetty was not correctly installed. Please make sure that the jetty
  ECHO installation is available in
  ECHO '%PLUGIN_DIR%\lib\jetty'.
  ECHO This directory should contain the file 'start.jar'.
  SET ERROR=true
)

:: Check availability of solr.
IF NOT EXIST "%PLUGIN_DIR%\embedded\webapps\solr.war" (
  ECHO Solr was not correctly installed. Please make sure that the solr
  ECHO installation is available in
  ECHO '%PLUGIN_DIR%\lib\solr'.
  ECHO The directory 'example\webapps' therein should contain the file
  ECHO 'solr.war'.
  SET ERROR=true
)

:: If we got an error then let the user know what to do.
IF "%ERROR%" == "true" (
  ECHO.
  ECHO Please correct the errors and then re-run this script.
) ELSE (
  ECHO Everything ok. You should be able to start Solr now.
)