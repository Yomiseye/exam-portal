[CmdletBinding()]
param(
    [string] $Slug = "hotfix",
    [string] $Date = (Get-Date -Format "yyyyMMdd"),
    [string[]] $AppPaths = @("app", "bootstrap", "database", "resources", "routes"),
    [switch] $SkipBuild
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $ProjectRoot

$SafeSlug = ($Slug.Trim().ToLowerInvariant() -replace "[^a-z0-9]+", "-").Trim("-")
if ([string]::IsNullOrWhiteSpace($SafeSlug)) {
    throw "Release slug cannot be empty after sanitizing. Use letters, numbers, or dashes."
}

if ($Date -notmatch "^\d{8}$") {
    throw "Date must use YYYYMMDD format, for example 20260624."
}

$ReleaseName = "cpanel-$SafeSlug-$Date"
$DeployDir = Join-Path $ProjectRoot "deploy"
$ReleaseDir = Join-Path $DeployDir $ReleaseName
$AppBundleName = "testportal-app-$SafeSlug-$Date"
$PublicBundleName = "testportal-public-build"
$AppBundleDir = Join-Path $ReleaseDir $AppBundleName
$PublicBundleDir = Join-Path $ReleaseDir $PublicBundleName
$AppZip = Join-Path $ReleaseDir "$AppBundleName.zip"
$PublicZip = Join-Path $ReleaseDir "$PublicBundleName.zip"
$UploadNotes = Join-Path $ReleaseDir "UPLOAD_NOTES.md"

if (Test-Path $ReleaseDir) {
    throw "Release folder already exists: $ReleaseDir"
}

New-Item -ItemType Directory -Path $AppBundleDir, $PublicBundleDir | Out-Null

if (-not $SkipBuild) {
    Write-Host "Building frontend assets with npm run build..."
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        throw "npm run build failed."
    }
}

$PublicBuild = Join-Path $ProjectRoot "public\build"
if (-not (Test-Path $PublicBuild)) {
    throw "public\build was not found. Run npm run build, or remove -SkipBuild."
}

Write-Host "Copying Laravel app files..."
foreach ($Path in $AppPaths) {
    $Source = Join-Path $ProjectRoot $Path
    if (-not (Test-Path $Source)) {
        throw "Configured app path does not exist: $Path"
    }

    Copy-Item -Path $Source -Destination $AppBundleDir -Recurse -Force
}

Write-Host "Copying public build..."
Copy-Item -Path $PublicBuild -Destination $PublicBundleDir -Recurse -Force

Write-Host "Creating zip bundles..."
Compress-Archive -Path (Join-Path $AppBundleDir "*") -DestinationPath $AppZip -Force
Compress-Archive -Path (Join-Path $PublicBundleDir "*") -DestinationPath $PublicZip -Force

$GeneratedAt = Get-Date -Format "yyyy-MM-dd HH:mm:ss zzz"
$AppPathList = ($AppPaths | ForEach-Object { "- $_" }) -join [Environment]::NewLine
$RelativeReleaseDir = "deploy/$ReleaseName"
$RelativeAppZip = "$RelativeReleaseDir/$AppBundleName.zip"
$RelativePublicZip = "$RelativeReleaseDir/$PublicBundleName.zip"
$Command = ".\scripts\package-cpanel-release.ps1 -Slug $SafeSlug"
if ($SkipBuild) {
    $Command += " -SkipBuild"
}

$Notes = @"
# cPanel Upload Notes

Release: $ReleaseName
Generated: $GeneratedAt

## Bundles

- App bundle: $RelativeAppZip
- Public build bundle: $RelativePublicZip

## App Bundle Contents

$AppPathList

## Upload Steps

1. Back up the current cPanel application files and database before upload.
2. Upload and extract $AppBundleName.zip over the Laravel application root, for example /home/cpanel-user/exam-portal.
3. Upload and extract $PublicBundleName.zip inside the web public directory, for example /home/cpanel-user/exam-portal/public or public_html if that is how the host is configured. It contains the build folder.
4. From the Laravel application root on cPanel, run:

~~~bash
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
~~~

5. Verify admin login, student login, exam start, answer saving, exam submission, and result display.

## Rebuild Command

~~~powershell
$Command
~~~

Use -Date YYYYMMDD to reproduce a specific dated folder, or pass -AppPaths with extra paths when a release changes config or root files.
"@

Set-Content -Path $UploadNotes -Value $Notes -Encoding UTF8

Write-Host ""
Write-Host "Created cPanel release:"
Write-Host "  $ReleaseDir"
Write-Host "  $AppZip"
Write-Host "  $PublicZip"
Write-Host "  $UploadNotes"
