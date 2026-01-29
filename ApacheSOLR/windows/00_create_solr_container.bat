@echo off
setlocal ENABLEDELAYEDEXPANSION

echo.
echo ==========================================
echo   Create peviitor-solr Docker container
echo ==========================================
echo.

REM 1. Verificam Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker nu este instalat sau nu este in PATH.
    echo Instaleaza Docker Desktop si apoi ruleaza din nou acest script.
    pause
    exit /b 1
)

REM 2. Daca exista deja un container (running sau oprit) cu numele peviitor-solr, il oprim si il stergem
echo Verificam daca exista deja un container peviitor-solr...

docker ps -a --filter "name=peviitor-solr" --format "table {{.Names}}" >"%TEMP%\peviitor-solr.names"

set "EXISTING_NAME="
for /f "skip=1 usebackq delims=" %%i in ("%TEMP%\peviitor-solr.names") do set "EXISTING_NAME=%%i"
del "%TEMP%\peviitor-solr.names" >nul 2>&1

if /I "%EXISTING_NAME%"=="peviitor-solr" (
    echo Exista deja un container peviitor-solr. Il oprim si stergem...
    docker stop peviitor-solr >nul 2>&1
    docker rm peviitor-solr >nul 2>&1
) else (
    echo Nu exista niciun container peviitor-solr. Cream unul nou.
)

REM 3. Cream folderul C:\peviitor\solr daca nu exista
if not exist "C:\peviitor\solr" (
    mkdir "C:\peviitor\solr"
)

REM 4. Pull solr:latest
echo Pulling solr:latest ...
docker pull solr:latest

REM 5. Cream containerul nou
echo Creating container peviitor-solr ...
docker run -d --name peviitor-solr -p 8983:8983 -v C:/peviitor/solr:/var/solr solr:latest

if errorlevel 1 (
    echo [ERROR] Nu am reusit sa cream containerul peviitor-solr.
    pause
    exit /b 1
)

echo.
echo Containerul peviitor-solr a fost creat si pornit.
echo Solr ar trebui sa fie disponibil la: http://localhost:8983/solr
echo.
pause
exit /b 0
