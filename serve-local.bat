@echo off
set "PHPRC=%USERPROFILE%\scoop\persist\php\cli"
set "PHP_INI_SCAN_DIR="
set "PATH=%USERPROFILE%\scoop\shims;%PATH%"
"%USERPROFILE%\scoop\apps\php\current\php.exe" -c "%USERPROFILE%\scoop\persist\php\cli\php.ini" artisan serve --host=127.0.0.1 --port=8000
