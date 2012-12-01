:: Execute this script on Windows to check whether the solr server is running.
:: Please read the README file that comes with this plugin first to understand
:: how to install and configure Solr.
::
:: Usage: check.bat

@ECHO OFF
SETLOCAL ENABLEEXTENSIONS

:: Source common variables.
CALL %~dp0script-startup.bat

:: If we don't find a PID-file we assume that the server is stopped.
IF NOT EXIST "%SOLR_PIDFILE%" (
  ECHO Server is stopped ^(no PID file found^)^.
  EXIT /B 1
)

:: Check whether we got a process id at all.
IF NOT DEFINED PROC_SECRET (
  ECHO Server is stopped ^(no PID found in PID file^)^.
  EXIT /B 1
)

:: Check whether solr is running.
IF %SOLR_RUNNING% == true (
  ECHO Server is running^.
  EXIT /B 0
) ELSE (
  ECHO Server is stopped ^(last PID no longer active^)^.
  EXIT /B 1
)
