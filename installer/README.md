# Krishna Engineers — Windows Installer

## Overview

This folder contains the Windows installer for the Krishna Engineers application.

The installer is intended to deploy the Laravel application to:

`C:\KrishnaEngineers`

## Installation

1. Download the complete project ZIP from the private GitHub repository.
2. Extract the ZIP on the Windows server PC.
3. Open PowerShell as Administrator.
4. Navigate to the project's `installer` folder.
5. Run:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\Install-KrishnaEngineers.ps1
```

6. Follow the prompts for database and E-Time Office credentials.

## Requirements

The installer must check for:

- PHP 8.4 and required PHP extensions
- Composer
- Node.js and npm
- MySQL Server
- A stable office LAN connection

## Database setup

- Create a fresh application database during the first installation.
- Run Laravel migrations.
- Run `AdminSeeder` only.
- Do not run master-data or employee seeders.
- Never drop or recreate an existing database during a repair or update.

## Security

- Never commit `.env` or production credentials to GitHub.
- Do not expose MySQL port 3306 to employee devices.
- Do not configure router port forwarding.
- Restrict web access to the trusted office network.
- Distribute only the public HTTPS certificate authority certificate to employee devices. Never distribute its private key.
- Generate a unique initial administrator password and change it after the first login.

## Attendance synchronization

The production deployment must configure the Windows Task Scheduler to run Laravel's scheduler reliably, including the `etimeoffice:sync-punches` command every 15 minutes.

## Production readiness

Before using the installer in production, test installation on a clean Windows PC, HTTPS access from employee devices, login, attendance synchronization, database backups, and safe reruns.
