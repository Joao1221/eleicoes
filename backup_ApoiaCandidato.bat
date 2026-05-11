@echo off
setlocal EnableExtensions DisableDelayedExpansion

REM === Backup automatico do banco apoiacandidato (Locaweb) ===

REM --- Caminhos e configs ---
set "PATH=C:\xampp\mysql\bin;%PATH%"
set "DIR=C:\backups-sue"
set "LOG_DIR=%DIR%\logs"
set "DB_HOST=apoiacandidato.mysql.dbaas.com.br"
set "DB_USER=apoiacandidato"
set "DB_PASS=Jo@o7462503814"
set "DB_NAME=apoiacandidato"
set "RETENCAO_DIAS=7"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"

REM --- Timestamp robusto ---
for /f %%I in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "DATA=%%I"
if not defined DATA (
  echo [ERRO] Nao foi possivel gerar o timestamp do backup.
  exit /b 1
)

REM --- Pasta destino ---
if not exist "%DIR%" mkdir "%DIR%"
if errorlevel 1 (
  echo [ERRO] Nao foi possivel criar a pasta de destino: "%DIR%"
  exit /b 1
)
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"
if errorlevel 1 (
  echo [ERRO] Nao foi possivel criar a pasta de logs: "%LOG_DIR%"
  exit /b 1
)

set "SQL_FILE=%DIR%\%DB_NAME%_%DATA%.sql"
set "ZIP_FILE=%DIR%\%DB_NAME%_%DATA%.zip"
set "LOG_FILE=%LOG_DIR%\%DB_NAME%_%DATA%.log"
set "HASH_FILE=%LOG_DIR%\hashes.txt"

if not exist "%MYSQLDUMP%" (
  echo [ERRO] mysqldump.exe nao encontrado em "%MYSQLDUMP%".
  exit /b 1
)

echo [INFO] Iniciando backup em %DATA%
echo [INFO] Destino: %ZIP_FILE%
echo [INFO] Log: %LOG_FILE%

REM --- Dump completo do banco ---
"%MYSQLDUMP%" ^
  -h "%DB_HOST%" ^
  -u"%DB_USER%" ^
  -p"%DB_PASS%" ^
  --no-tablespaces ^
  --single-transaction ^
  --quick ^
  --routines ^
  --triggers ^
  --events ^
  --default-character-set=utf8mb4 ^
  "%DB_NAME%" > "%SQL_FILE%" 2>> "%LOG_FILE%"

if errorlevel 1 (
  echo [ERRO] mysqldump falhou. Veja o log em "%LOG_FILE%".
  if exist "%SQL_FILE%" del "%SQL_FILE%" >nul 2>&1
  exit /b 1
)

if not exist "%SQL_FILE%" (
  echo [ERRO] Arquivo SQL nao foi criado.
  exit /b 1
)

for %%F in ("%SQL_FILE%") do set "SQL_SIZE=%%~zF"
if "%SQL_SIZE%"=="0" (
  echo [ERRO] Arquivo SQL vazio. Veja o log em "%LOG_FILE%".
  del "%SQL_FILE%" >nul 2>&1
  exit /b 1
)

REM --- Compacta em ZIP e remove o SQL temporario apenas apos validar o ZIP ---
powershell -NoProfile -Command ^
  "$ErrorActionPreference = 'Stop';" ^
  "Compress-Archive -LiteralPath '%SQL_FILE%' -DestinationPath '%ZIP_FILE%' -Force;" >> "%LOG_FILE%" 2>&1

if errorlevel 1 (
  echo [ERRO] Falha ao compactar o arquivo. Veja o log em "%LOG_FILE%".
  exit /b 1
)

if not exist "%ZIP_FILE%" (
  echo [ERRO] O arquivo ZIP nao foi criado.
  exit /b 1
)

for %%F in ("%ZIP_FILE%") do set "ZIP_SIZE=%%~zF"
if "%ZIP_SIZE%"=="0" (
  echo [ERRO] O arquivo ZIP foi criado vazio.
  exit /b 1
)

del "%SQL_FILE%" >nul 2>&1

REM --- Rotacao confiavel: remove ZIPs, LOGs e SQLs antigos do banco ---
powershell -NoProfile -Command ^
  "$limite = (Get-Date).AddDays(-%RETENCAO_DIAS%);" ^
  "Get-ChildItem -LiteralPath '%DIR%' -File |" ^
  "Where-Object { $_.LastWriteTime -lt $limite -and ($_.Name -like '%DB_NAME%_*.zip' -or $_.Name -like '%DB_NAME%_*.sql') } |" ^
  "Remove-Item -Force -ErrorAction SilentlyContinue;" ^
  "Get-ChildItem -LiteralPath '%LOG_DIR%' -File |" ^
  "Where-Object { $_.LastWriteTime -lt $limite -and $_.Name -like '%DB_NAME%_*.log' } |" ^
  "Remove-Item -Force -ErrorAction SilentlyContinue" >> "%LOG_FILE%" 2>&1

REM --- Hash SHA256 para auditoria ---
>> "%HASH_FILE%" echo [%DATA%] %ZIP_FILE%
certutil -hashfile "%ZIP_FILE%" SHA256 >> "%HASH_FILE%" 2>&1

echo [OK] Backup concluido: %ZIP_FILE%
endlocal

