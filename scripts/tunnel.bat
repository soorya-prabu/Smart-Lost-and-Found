@echo off
title Smart Lost & Found - Mobile Access & Tunneling
cls
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0tunnel.ps1"
pause
