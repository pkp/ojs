:: Execute this script on Windows to start the solr server. Please read the
:: README file that comes with this plugin first to understand how to install
:: and configure Solr. You'll find usage examples there, too.
::
:: Usage: stop.bat

@ECHO OFF
SETLOCAL ENABLEEXTENSIONS

:: Source common variables.
CALL %~dp0script-startup.bat

:: Check whether Solr is running.
IF NOT EXIST "%SOLR_PIDFILE%" (
  ECHO Solr PID-file not found. Is Solr stopped? Has the PID-file been deleted?
  EXIT /B 1
)
IF %SOLR_RUNNING% == false (
  ECHO Solr not running^.
  EXIT /B 1
)

:: Stop the solr process.
IF DEFINED PROC_SECRET (
  java -DSTOP.PORT=8079 -DSTOP.KEY=%PROC_SECRET% -jar "%JETTY_HOME%\start.jar" --stop
  ECHO Stopped solr^.
)
