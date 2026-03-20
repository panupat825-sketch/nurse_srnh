@echo off
setlocal

echo Starting Nurse SRNH installer server...
php -S 127.0.0.1:8080 -t .

endlocal
