@echo off

echo "Modifying the configuration file (php.ini)."

:: pnp ini 디렉토리 변수설정
set phpPath=%~dp0php73
set outPath=%phpPath%\php.ini
set samplePath=%phpPath%\sample.ini

:: Search for code to fix
:: for "extension_dir"
for /f "tokens=*" %%i in ('findstr /B "extension_dir" %samplePath%') do (
	set result=%%i
)
:: Origin
set findStr=%result%
:: New
set editStr=extension_dir = "%phpPath:\=/%/ext"

:: php.ini 존재시 내용 지우기
if exist %outPath% type NUL > %outPath%

for /f "tokens=1* delims=]" %%a in ('type %samplePath% ^| find /n /v ""') do (
	if "%findStr%" equ "%%b" (
		echo.%editStr% >> %outPath%
	) else (
		echo.%%b >> %outPath%
	)
)

echo "Modifying the configuration file (httpd.conf)."

:: for "Define PROJPATH"

:: 아파치 conf 디렉토리 변수설정
set apacheConfPath=%~dp0\httpd24\Apache24\conf\
set outPath=%apacheConfPath%httpd.conf
set samplePath=%apacheConfPath%sample.conf

:: PROJPATH 찾기
for /f "tokens=*" %%i in ('findstr /C:"Define PROJPATH" %samplePath%') do (
	set result=%%i
)

set findStr=%result%
set replaceStr=Define PROJPATH "%cd:\=/%"
set replaceStr=%replaceStr:/dev_env"=%"

:: httpd.conf 존재시 내용 지우기
if exist %outPath% type NUL > %outPath%

:: 라인단위로 읽어가며 수정대상 치환
for /f "tokens=1* delims=]" %%a in ('type %samplePath% ^| find /n /v ""') do (
	if "%findStr%" equ "%%b" (
		echo.%replaceStr% >> %outPath%
	) else (
		echo.%%b >> %outPath%
	)
)

:END