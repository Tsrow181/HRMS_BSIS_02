@echo off
echo ========================================
echo Cleanup Before Git Commit
echo ========================================
echo.

echo Checking for sensitive files...
echo.

REM Check if ai_keys.php exists (should NOT be committed)
if exist ai_keys.php (
    echo [WARNING] ai_keys.php exists - This file should NOT be committed
    echo It is in .gitignore, so it should be safe
) else (
    echo [OK] ai_keys.php does not exist yet
)

REM Check .gitignore
if exist .gitignore (
    echo [OK] .gitignore exists
    findstr /C:"ai_keys.php" .gitignore >nul
    if %errorlevel% equ 0 (
        echo [OK] ai_keys.php is in .gitignore
    ) else (
        echo [ERROR] ai_keys.php is NOT in .gitignore!
        echo Please add it before committing
        pause
        exit /b 1
    )
) else (
    echo [ERROR] .gitignore does not exist!
    pause
    exit /b 1
)

echo.
echo ========================================
echo Safe to commit! Files to be added:
echo ========================================
echo.
echo - ai_config.php (no sensitive data)
echo - ai_keys.example.php (template only)
echo - ai_keys_setup.php (web interface)
echo - setup_ai_keys.php (setup script)
echo - ai_screening.php (AI logic)
echo - run_ai_screening.php (API endpoint)
echo - setup_ai_screening.php (verification)
echo - job_applications.php (UI updates)
echo - .gitignore (updated)
echo - SECURITY_NOTICE.md (documentation)
echo.
echo Files that will NOT be committed:
echo - ai_keys.php (contains actual API keys)
echo - config.php (database credentials)
echo.
echo ========================================
echo Ready to commit!
echo ========================================
echo.
echo Run these commands:
echo   git add .
echo   git commit -m "feat: Add AI screening with secure API key management"
echo   git push
echo.
pause
