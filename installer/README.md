# Krishna Engineers — Windows Installer

## Overview

This folder contains the PowerShell scripts used to install and update the Krishna Engineers application on a Windows office server.

**Default installation directory:**

`C:\KrishnaEngineersApp`

The installer deploys the Laravel application, installs dependencies, runs database migrations, builds frontend assets, optimizes the application, seeds the initial administrator account, and registers the Laravel scheduler in Windows Task Scheduler.

The update script deploys new application code while preserving the installed `.env` file and existing database.

## Requirements

Prepare the Windows server PC before running the installer.

- **Windows:** Windows 10/11 or Windows Server.
- **PHP:** PHP 8.3 or newer, with the extensions required by the Laravel application.
- **Composer:** Installed and accessible from PowerShell.
- **Node.js:** Version 22 or newer, with npm available.
- **MySQL Server:** Installed and running.
- **MySQL database:** The application database must already exist, and the configured MySQL user must have permission to access it and run migrations.
- **Network:** A stable office LAN connection.
- **Project ZIP:** The complete project ZIP, including `composer.lock`, `package-lock.json`, and the `installer` folder.

The scripts check the availability and supported versions of the main runtime tools. They do not install PHP, Composer, Node.js, npm, or MySQL automatically.

## 1. First-Time Installation

### Step 1 — Download the project

1. Download the complete project ZIP from the private GitHub repository.
2. Extract the ZIP to a temporary working folder on the Windows server PC.
3. Confirm that the extracted project contains files such as:
    - `artisan`
    - `composer.json`
    - `composer.lock`
    - `package.json`
    - `package-lock.json`
    - `.env.example`
    - `installer/Install-KrishnaEngineers.ps1`

Do not extract the project directly over an existing installation.

### Step 2 — Create the MySQL database

Create a new, empty MySQL database for the application using your approved MySQL administration tool.

Configure a MySQL user with the necessary permissions for that database.

**Important:** The installer runs Laravel migrations but does not create the MySQL database. Do not use an existing production database unless this installation is intentionally meant to use it.

### Step 3 — Configure the `.env` file

In the extracted project root, create `.env` from `.env.example` if it does not already exist.

Configure the production application settings, including:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` with the intended application URL
- `DB_CONNECTION=mysql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- E-Time Office API settings, if attendance synchronization is required

Use the correct database name, username, and password for the database created in Step 2.

Keep `.env` private. Never commit it to GitHub or include production credentials in a distributed project ZIP.

The installer copies this configured `.env` file into the installation directory.

### Step 4 — Run the installer

1. Open **PowerShell as Administrator**.
2. Navigate to the extracted project's `installer` directory. For example:

   ```powershell
   cd "C:\Path\To\ExtractedProject\installer"
   ```

3. Allow PowerShell scripts to run for the current PowerShell session:

   ```powershell
   Set-ExecutionPolicy -Scope Process Bypass
   ```

4. Run the installer:

   ```powershell
   .\Install-KrishnaEngineers.ps1
   ```

The default installation path is:

`C:\KrishnaEngineersApp`

To specify a different installation directory, pass the `InstallPath` parameter:

```powershell
.\Install-KrishnaEngineers.ps1 -InstallPath "D:\KrishnaEngineersApp"
```

### Step 5 — Wait for installation to complete

The installer performs the following operations:

1. Validates the project files and required runtime tools.
2. Copies the application to the installation directory.
3. Configures the production environment flags.
4. Generates an application key if one is missing.
5. Installs PHP dependencies using Composer.
6. Installs JavaScript dependencies using `npm ci`.
7. Runs database migrations using `php artisan migrate`.
8. Runs `AdminSeeder` for the initial administrator account.
9. Creates the storage link.
10. Builds frontend assets.
11. Clears and rebuilds Laravel caches.
12. Registers the `KrishnaEngineers-Scheduler` Windows scheduled task.

The installer uses `migrate`, **not** `migrate:fresh`. It does not intentionally drop or recreate the database.

Do not close PowerShell while installation is running. If an error occurs, read the error message before retrying.

### Step 6 — Verify the installation

After successful installation, confirm that:

- The application files exist in `C:\KrishnaEngineersApp`.
- The `.env` file contains the correct production settings.
- The database migrations completed successfully.
- The initial administrator can log in.
- The frontend loads correctly.
- The Windows scheduled task `KrishnaEngineers-Scheduler` exists and is enabled.

Change the initial administrator password immediately after the first login.

### Step 7 — Configure production web access

The installer does **not** configure the production web server or LAN HTTPS.

Before employees use the application, configure the appropriate web server, PHP integration, application document root, trusted office network access, and HTTPS certificate. The web server document root must point to the Laravel application's `public` directory, not the project root.

Test access from an employee device before going live.

## 2. Updating an Existing Installation

Use the update script for future application releases. Do not run the first-time installer over a completed installation.

### Step 1 — Back up the application

Before updating:

1. Back up the production MySQL database.
2. Back up `C:\KrishnaEngineersApp\.env` securely.
3. Confirm that the current application is working.
4. Schedule the update during a suitable maintenance window.

Do not store database backups or production credentials in the public project ZIP.

### Step 2 — Download the latest project ZIP

1. Download the updated project ZIP from the private GitHub repository.
2. Extract it into a **new temporary folder**.
3. Confirm that the ZIP includes the latest application code, `composer.lock`, `package-lock.json`, and `installer/Update-KrishnaEngineers.ps1`.

Do not extract the update ZIP directly over the installed application.

### Step 3 — Run the update script

1. Open **PowerShell as Administrator**.
2. Navigate to the extracted update's `installer` directory:

   ```powershell
   cd "C:\Path\To\ExtractedUpdate\installer"
   ```

3. Allow scripts for the current session:

   ```powershell
   Set-ExecutionPolicy -Scope Process Bypass
   ```

4. Run the update script:

   ```powershell
   .\Update-KrishnaEngineers.ps1
   ```

The default target is `C:\KrishnaEngineersApp`.

For a custom installation path:

```powershell
.\Update-KrishnaEngineers.ps1 -InstallPath "D:\KrishnaEngineersApp"
```

### Step 4 — Wait for the update to finish

The update script:

1. Validates the installed application and update ZIP.
2. Copies updated application files while excluding the installed `.env`, `vendor`, `node_modules`, and `.git`.
3. Runs `composer install` using the updated lock file.
4. Runs `npm ci` using the updated lock file.
5. Runs `php artisan migrate --force`.
6. Builds frontend assets.
7. Clears and rebuilds Laravel caches.

Keep PowerShell open until the script completes.

**The update script does not intentionally replace the installed `.env`, delete the database, or run `AdminSeeder`.** Existing database records and administrator credentials are therefore not reset by the update script's normal workflow.

Database migrations can still modify the database schema. Review and test migrations before releasing an update.

### Step 5 — Verify the updated application

After the script reports success:

- Confirm that the application opens and login works.
- Verify the updated features.
- Check that existing records are present.
- Check attendance synchronization and other scheduled operations.
- Review Laravel logs if any errors occur.

If the update fails, read the error output and resolve the underlying issue before rerunning the script. Keep the database and `.env` backups available for recovery.

## 3. Database and Seeder Rules

- Create the MySQL database before the first installation.
- Use `php artisan migrate` for normal installation and updates.
- Do not run `php artisan migrate:fresh` against an existing database.
- Run `AdminSeeder` only during the initial installation workflow.
- Do not run master-data or employee seeders unless a separate, explicitly approved procedure requires them.
- Do not run `db:seed` indiscriminately during updates.
- Back up the database before applying migrations.

## 4. Attendance Synchronization

The installer registers the Laravel scheduler through the Windows Task Scheduler task named:

`KrishnaEngineers-Scheduler`

The task runs Laravel's scheduler every minute. The schedule defined by the application determines when individual scheduled commands execute.

The application must have the required E-Time Office configuration in `.env` for attendance synchronization to work. Verify that the scheduled `etimeoffice:sync-punches` command runs at its configured interval and inspect application logs if synchronization fails.

## 5. Security

- Never commit `.env`, API credentials, or production secrets to GitHub.
- Change the initial administrator password immediately.
- Do not expose MySQL port `3306` to employee devices.
- Do not configure router port forwarding for the application or database.
- Restrict web access to the trusted office network.
- Protect database backups and application configuration files.
- Distribute only the public HTTPS certificate authority certificate to employee devices when required. Never distribute the private key.
- Use HTTPS for employee access and verify that the certificate is trusted by office devices.

## 6. Production Readiness Checklist

Before deploying to employees, verify the following:

- [ ] PHP, Composer, Node.js, npm, and required PHP extensions are installed.
- [ ] MySQL is running and the application database exists.
- [ ] Production `.env` settings have been verified.
- [ ] Initial installation or update completed without errors.
- [ ] Database backup and recovery procedures are tested.
- [ ] Initial administrator password has been changed.
- [ ] Web server and LAN HTTPS are configured separately.
- [ ] Login and permissions have been tested.
- [ ] Attendance synchronization has been verified.
- [ ] Windows Task Scheduler is running the Laravel scheduler.
- [ ] A future update has been tested on a non-production copy before being applied to production.

## 7. Troubleshooting

### PHP, Composer, Node.js, or npm is not found

Install the missing prerequisite, ensure it is available in the system `PATH`, and reopen PowerShell. Confirm the commands work:

```powershell
php -v
composer --version
node -v
npm -v
```

### Database connection fails

Verify that MySQL is running, the database already exists, and the connection values in the installed `.env` are correct. Confirm that the configured user has the necessary database permissions.

### PowerShell blocks the script

Open PowerShell as Administrator and run:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
```

Then rerun the appropriate script.

### The application does not load after installation or update

Check the web server configuration, PHP integration, HTTPS setup, `.env` values, Laravel logs, and frontend build output. The installer scripts do not configure the web server or HTTPS.

### The installation script failed partway through

The installer uses an installation marker to identify an incomplete installation and clean up that directory when retried. Do not manually delete a completed installation without backing up its `.env` and other required data.

A failed installation may already have applied migrations or run the initial seeder. The MySQL database is not deleted by the cleanup process. Review the error and database state before retrying.

## 8. Release Guidelines

For each new application release:

1. Complete and test the code changes.
2. Update and test database migrations.
3. Ensure `composer.lock` and `package-lock.json` match the dependency manifests.
4. Test the update script against a copy of the installed application.
5. Include the complete application source and the latest installer scripts in the release ZIP.
6. Exclude `.env`, `.git`, `vendor`, and `node_modules` from the distributed ZIP.
7. Keep the lock files in the ZIP so dependency versions remain reproducible.
8. Back up the production database before applying the update.

Use `Install-KrishnaEngineers.ps1` for a first-time installation and `Update-KrishnaEngineers.ps1` for subsequent application releases.
