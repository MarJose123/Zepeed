<?php

/*
 * File: updater.php
 * Last Modified: 8/12/25, 4:17 PM
 *
 * Copyright (c) 2025 Marjose Darang. - All Rights Reserved
 *
 */

function getLatestReleaseInfo()
{
    $url = 'https://api.github.com/repos/axllent/mailpit/releases/latest';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'mailpit-updater script v1'); // GitHub API requires a User-Agent

    $response = curl_exec($ch);
    curl_close($ch);

    if (! $response) {
        exit("Failed to fetch release info.\n");
    }

    return json_decode($response, true);
}

function downloadFile($url, $destination): void
{
    $file = fopen($destination, 'w');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $file);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'mailpit-updater script v1');
    curl_exec($ch);
    curl_close($ch);
    fclose($file);
}

function getOperatingSystem(): string
{
    $os_info = php_uname();
    if (stripos($os_info, 'Windows') !== false) {
        return 'Windows';
    } elseif (stripos($os_info, 'Darwin') !== false || stripos($os_info, 'Mac') !== false) {
        return 'MacOS';
    }
}

function isWindowsOs(): bool
{
    return strtoupper(substr(getOperatingSystem(), 0, 3)) === 'WIN';
}

function isMacOS(): bool
{
    return strtoupper(substr(getOperatingSystem(), 0, 3)) === 'MAC';
}

function isLinuxOs(): bool
{
    return strtoupper(substr(getOperatingSystem(), 0, 3)) === 'LIN';
}

function extractMailpitBinary($archivePath, $targetDir): void
{
    // Stop running mailpit process
    if (isWindowsOs()) {
        exec('taskkill /F /IM mailpit.exe 2>NUL');
    } else {
        exec('killall mailpit 2>/dev/null');
    }
    sleep(2);

    $mailpitFileName = isWindowsOs() ? 'mailpit.exe' : 'mailpit';

    // Ensure target directory exists
    if (! is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Determine file type
    if (str_ends_with($archivePath, '.zip')) {

        $zip = new ZipArchive;
        if ($zip->open($archivePath) === true) {

            if ($zip->locateName($mailpitFileName) === false) {
                $zip->close();
                exit("❌  {$mailpitFileName} not found in zip file.\n");
            }

            if (! $zip->extractTo($targetDir, $mailpitFileName)) {
                $zip->close();
                exit("❌  Failed to extract {$mailpitFileName} from zip.\n");
            }

            $zip->close();
        } else {
            exit("❌  Failed to open zip file.\n");
        }

    } elseif (str_ends_with($archivePath, '.tar.gz')) {

        try {
            $phar = new PharData($archivePath);
            $tarPath = str_replace('.tar.gz', '.tar', $archivePath);

            if (! file_exists($tarPath)) {
                $phar->decompress(); // creates .tar
            }

            $tar = new PharData($tarPath);

            if (! $tar->offsetExists($mailpitFileName)) {
                unlink($tarPath);
                exit("❌  {$mailpitFileName} not found in tar archive.\n");
            }

            $tar->extractTo($targetDir, $mailpitFileName, true);
            unlink($tarPath);

        } catch (Exception $e) {
            exit('❌  Failed to extract tar.gz file: '.$e->getMessage()."\n");
        }

    } else {
        exit("❌  Unsupported archive format.\n");
    }

    // Make executable on Linux/macOS
    if (! isWindowsOs()) {
        chmod($targetDir.'/'.$mailpitFileName, 0755);
    }

    echo "✅  Extracted Mailpit\n";
}

function getLocalVersion($exePath): ?string
{
    if (! file_exists($exePath)) {
        return null;
    }

    $output = [];
    exec("\"$exePath\" version", $output);
    if (! empty($output)) {
        // Extract version like "v1.27.2" using regex
        if (preg_match('/v\d+\.\d+\.\d+/', $output[0], $matches)) {
            return $matches[0];
        }
    }

    return null;
}

// Main script logic
$release = getLatestReleaseInfo();
$latestVersion = $release['tag_name'];
echo "🔍  Latest version on GitHub: $latestVersion\n";

$zipAsset = null;
foreach ($release['assets'] as $asset) {
    if (isWindowsOs() && str_contains($asset['name'], 'mailpit-windows-amd64.zip')) {
        $zipAsset = $asset['browser_download_url'];
        break;
    }
    if (isLinuxOs() && str_contains($asset['name'], 'mailpit-linux-amd64.tar.gz')) {
        $zipAsset = $asset['browser_download_url'];
        break;
    }
    if (isMacOS() && str_contains($asset['name'], 'mailpit-darwin-arm64.tar.gz')) {
        $zipAsset = $asset['browser_download_url'];
        break;
    }
}

if (! $zipAsset) {
    exit("❌  Could not find zip asset in latest release.\n");
}

$scriptDir = __DIR__;
if (isWindowsOs()) {
    $exePath = "$scriptDir/mailpit.exe";
    $zipPath = "$scriptDir/mailpit-windows-amd64.zip";
} elseif (isMacOS()) {
    $exePath = "$scriptDir/mailpit";
    $zipPath = "$scriptDir/mailpit-darwin-arm64.tar.gz";
} elseif (isLinuxOs()) {
    $exePath = "$scriptDir/mailpit";
    $zipPath = "$scriptDir/mailpit-linux-amd64.tar.gz";
}

$currentVersion = getLocalVersion($exePath);
if ($currentVersion) {
    echo "📦  Local Mailpit version: $currentVersion\n";
} else {
    echo "⚠️  Mailpit not found locally or version not detected.\n";
}

if (! $currentVersion || stripos($currentVersion, $latestVersion) === false) {
    echo "⬇️  Downloading and updating to $latestVersion...\n";
    downloadFile($zipAsset, $zipPath);
    extractMailpitBinary($zipPath, $scriptDir);
    unlink($zipPath); // Cleanup zip file
    echo "✅  Update complete.\n";
} else {
    echo "✔️  Already up-to-date.\n";
}
