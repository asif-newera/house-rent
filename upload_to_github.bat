@echo off
title House Rent - GitHub Uploader
color 0A

echo ===================================================
echo      House Rent - Automatic GitHub Uploader
echo ===================================================
echo.

REM 1. Check if Git is installed
where git >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] Git is NOT installed!
    echo.
    echo Please install Git first:
    echo 1. Go to https://git-scm.com/download/win
    echo 2. Download and install Git
    echo 3. Run this script again
    echo.
    pause
    exit /b 1
)

echo [OK] Git is installed.
echo.

REM 2. Configure Git User
echo [1/6] Configuring Git...
git config --global user.name "asif-newera"
git config --global user.email "asif-newera@users.noreply.github.com"

REM 3. Initialize Repository
echo [2/6] Initializing repository...
if not exist .git (
    git init
) else (
    echo     (Git repository already exists)
)

REM 4. Add Files and Commit
echo [3/6] Adding files...
git add .
echo [4/6] Committing changes...
git commit -m "Initial commit of House Rent application with Admin Panel, SMTP Email, and Booking features"

REM 5. Create Remote Repository (using curl)
echo [5/6] Creating 'house-rent' repo on GitHub...
curl -H "Authorization: token YOUR_GITHUB_TOKEN" https://api.github.com/user/repos -d "{\"name\":\"house-rent\",\"private\":false}"
echo.

REM 6. Link and Push
echo [6/6] Pushing code to GitHub...
git branch -M main
git remote remove origin >nul 2>nul
git remote add origin https://asif-newera:YOUR_GITHUB_TOKEN@github.com/asif-newera/house-rent.git
git push -u origin main

echo.
echo ===================================================
echo               UPLOAD COMPLETE!
echo ===================================================
echo.
echo You can view your project here:
echo https://github.com/asif-newera/house-rent
echo.
pause
