@REM ----------------------------------------------------------------------------
@REM Licensed to the Apache Software Foundation (ASF) under one
@REM or more contributor license agreements.  See the NOTICE file
@REM distributed with this work for additional information
@REM regarding copyright ownership.  The ASF licenses this file
@REM to you under the Apache License, Version 2.0 (the
@REM "License"); you may not use this file except in compliance
@REM with the License.  You may obtain a copy of the License at
@REM
@REM    http://www.apache.org/licenses/LICENSE-2.0
@REM
@REM Unless required by applicable law or agreed to in writing,
@REM software distributed under the License is distributed on an
@REM "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
@REM KIND, either express or implied.  See the License for the
@REM specific language governing permissions and limitations
@REM under the License.
@REM ----------------------------------------------------------------------------

@REM ----------------------------------------------------------------------------
@REM Maven Start Up Batch script (wrapper edition)
@REM
@REM Usage from PowerShell:
@REM   .\mvnw.cmd clean compile
@REM
@REM On first run this downloads the Maven distribution pinned in
@REM .mvn\wrapper\maven-wrapper.properties into %USERPROFILE%\.m2\wrapper,
@REM then delegates to it -- no separately-installed Maven required.
@REM ----------------------------------------------------------------------------

@echo off
@setlocal

set ERROR_CODE=0

@REM Resolve JAVA_HOME
if not "%JAVA_HOME%"=="" goto valJavaHome
echo Error: JAVA_HOME not set. >&2
echo Set it, e.g.: $env:JAVA_HOME = "C:\Program Files\Java\jdk-21" >&2
goto error

:valJavaHome
if exist "%JAVA_HOME%\bin\java.exe" goto init
echo Error: JAVA_HOME is set to "%JAVA_HOME%" but "%JAVA_HOME%\bin\java.exe" does not exist. >&2
goto error

:init
set MAVEN_PROJECTBASEDIR=%~dp0
if "%MAVEN_PROJECTBASEDIR:~-1%"=="\" set MAVEN_PROJECTBASEDIR=%MAVEN_PROJECTBASEDIR:~0,-1%

set WRAPPER_JAR=%MAVEN_PROJECTBASEDIR%\.mvn\wrapper\maven-wrapper.jar
set WRAPPER_PROPERTIES=%MAVEN_PROJECTBASEDIR%\.mvn\wrapper\maven-wrapper.properties
set WRAPPER_LAUNCHER=org.apache.maven.wrapper.MavenWrapperMain

@REM Download the (small) wrapper jar itself if it isn't present yet.
if exist "%WRAPPER_JAR%" goto runWrapper

echo Downloading Maven Wrapper...
set WRAPPER_URL=https://repo.maven.apache.org/maven2/org/apache/maven/wrapper/maven-wrapper/3.3.2/maven-wrapper-3.3.2.jar

powershell -NoProfile -ExecutionPolicy Bypass -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; (New-Object Net.WebClient).DownloadFile('%WRAPPER_URL%', '%WRAPPER_JAR%')"

if not exist "%WRAPPER_JAR%" (
    echo Error: failed to download maven-wrapper.jar. Check your internet connection. >&2
    goto error
)

:runWrapper
"%JAVA_HOME%\bin\java.exe" ^
    -Dmaven.multiModuleProjectDirectory="%MAVEN_PROJECTBASEDIR%" ^
    -classpath "%WRAPPER_JAR%" ^
    %WRAPPER_LAUNCHER% %*

if ERRORLEVEL 1 goto error
goto end

:error
set ERROR_CODE=1

:end
@endlocal & set ERROR_CODE=%ERROR_CODE%
exit /B %ERROR_CODE%
