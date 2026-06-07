<?php
// ── GitHub Webhook (must run before any output or session) ─────────────────
$rawBody       = file_get_contents('php://input');
$hubSignature  = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($hubSignature !== '') {
    // Load config/db.php for SITE_URL, then read token
    require_once __DIR__ . '/../config/db.php';
    $tokenFile = dirname(__DIR__) . '/config/.deploy_token';
    $token     = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : '';

    $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $token);

    if (hash_equals($expected, $hubSignature)) {
        $branch = 'main';
        $output = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && git pull origin ' . escapeshellarg($branch) . ' 2>&1');
        file_put_contents(
            dirname(__DIR__) . '/config/deploy.log',
            date('Y-m-d H:i:s') . ' GitHub webhook pull' . "\n" . $output . "\n---\n",
            FILE_APPEND
        );
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['ok' => true, 'output' => $output]);
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    }
    exit;
}

require_once __DIR__ . '/_guard.php';

// ── Deploy token bootstrap ─────────────────────────────────────────────────
$tokenFile = dirname(__DIR__) . '/config/.deploy_token';
if (!file_exists($tokenFile)) {
    file_put_contents($tokenFile, bin2hex(random_bytes(24)));
}
$deployToken = trim(file_get_contents($tokenFile));
$csrf = csrfToken();

function deploySafeName(string $name): string {
    return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
}

function deployFetchUrl(string $url): string {
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: VDJ-Deploy-Check/1.0\r\nCache-Control: no-cache\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    return is_string($body) ? $body : '';
}

function deployLiveVerification(): array {
    $homeUrl = publicUrl('/?vdj_check=' . rawurlencode(VDJ_ASSET_VERSION . '-' . time()));
    $html = deployFetchUrl($homeUrl);
    $cssUrl = publicUrl('/assets/css/style.min.css?v=' . rawurlencode(VDJ_ASSET_VERSION) . '&vdj_check=' . time());
    $css = deployFetchUrl($cssUrl);

    return [
        'home_url' => $homeUrl,
        'css_url' => $cssUrl,
        'html_fetched' => $html !== '',
        'css_fetched' => $css !== '',
        'build_marker' => $html !== '' && str_contains($html, 'VDJ_BUILD: ' . VDJ_BUILD_ID),
        'asset_version' => $html !== '' && str_contains($html, 'style.min.css?v=' . VDJ_ASSET_VERSION),
        'nav_more' => $html !== '' && str_contains($html, 'nav__more'),
        'nav_optional' => $html !== '' && str_contains($html, 'nav__optional'),
        'css_grid' => $css !== '' && str_contains($css, '.nav__inner{display:grid'),
        'css_marker' => $css !== '' && str_contains($css, VDJ_BUILD_ID),
    ];
}

function deployVerificationOk(array $check): bool {
    foreach (['html_fetched', 'css_fetched', 'build_marker', 'asset_version', 'nav_more', 'nav_optional', 'css_grid', 'css_marker'] as $key) {
        if (empty($check[$key])) return false;
    }
    return true;
}

function writeSqlDump(PDO $db, string $outFile): void {
    $fh = fopen($outFile, 'wb');
    if (!$fh) {
        throw new RuntimeException('Could not create SQL dump file.');
    }

    fwrite($fh, "-- Visual Design Journey database export\n");
    fwrite($fh, "-- Generated: " . gmdate('c') . " UTC\n\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $tableRow) {
        $table = $tableRow[0];
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';

        $createStmt = $db->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'] ?? array_values($createStmt)[1] ?? '';

        fwrite($fh, "\n-- Table structure for {$quotedTable}\n");
        fwrite($fh, "DROP TABLE IF EXISTS {$quotedTable};\n");
        fwrite($fh, $createSql . ";\n\n");
        fwrite($fh, "-- Data for {$quotedTable}\n");

        $rows = $db->query('SELECT * FROM ' . $quotedTable, PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $columns = array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
            $values = array_map(function ($value) use ($db) {
                if ($value === null) return 'NULL';
                return $db->quote((string)$value);
            }, array_values($row));
            fwrite($fh, 'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
}

function addDirectoryToZip(ZipArchive $zip, string $root, string $baseDir, bool $includeUploads = false): void {
    $skipExact = [
        'config/db.local.php',
        'config/.deploy_token',
        'config/.installed',
        'config/deploy.log',
        'config/.rollback.zip',
    ];
    $skipPrefixes = [
        'uploads/.rate-limit/',
        'cache/',
        '.git/',
        '__MACOSX/',
    ];

    $rit = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($rit as $item) {
        if ($item->isDir()) continue;
        $rel = ltrim(str_replace($root, '', $item->getPathname()), DIRECTORY_SEPARATOR . '/');
        $rel = str_replace('\\', '/', $rel);

        if (in_array($rel, $skipExact, true)) continue;
        if (!$includeUploads && str_starts_with($rel, 'uploads/')) continue;
        if (strtolower(pathinfo($rel, PATHINFO_EXTENSION)) === 'zip') continue;
        $skip = false;
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($rel, $prefix)) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        $zip->addFile($item->getPathname(), $rel);
    }
}

if (($_GET['action'] ?? '') === 'download_backup') {
    $token = $_GET['csrf_token'] ?? '';
    if ($token === '' || !hash_equals($csrf, $token)) {
        header('Location: /admin/deploy.php');
        exit;
    }

    @set_time_limit(120);
    ignore_user_abort(true);

    $siteRoot = dirname(__DIR__);
    $tmpDir = sys_get_temp_dir() . '/vdj_backup_' . uniqid();
    if (!mkdir($tmpDir, 0755, true)) {
        http_response_code(500);
        die('Could not create backup temp directory.');
    }

    $date = date('Y-m-d_His');
    $sqlFile = $tmpDir . '/visualdesignjourney-db-' . $date . '.sql';
    $scope = $_GET['scope'] ?? 'light';
    if (!in_array($scope, ['light', 'sql', 'full'], true)) $scope = 'light';
    $zipFile = $tmpDir . '/visualdesignjourney-' . $scope . '-backup-' . $date . '.zip';

    try {
        writeSqlDump(getDB(), $sqlFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup ZIP.');
        }
        if ($scope !== 'sql') {
            addDirectoryToZip($zip, $siteRoot, $siteRoot, $scope === 'full');
        }
        $zip->addFile($sqlFile, 'database/visualdesignjourney-db-' . $date . '.sql');
        $zip->close();

        file_put_contents(
            $siteRoot . '/config/deploy.log',
            date('Y-m-d H:i:s') . ' ' . ucfirst($scope) . ' backup downloaded by ' . $adminUser['username'] . "\n---\n",
            FILE_APPEND
        );

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($zipFile);
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Backup failed: ' . e($e->getMessage());
    } finally {
        if (file_exists($sqlFile)) unlink($sqlFile);
        if (file_exists($zipFile)) unlink($zipFile);
        if (is_dir($tmpDir)) rmdir($tmpDir);
    }
    exit;
}

if (($_GET['action'] ?? '') === 'live_check') {
    $token = $_GET['csrf_token'] ?? '';
    if ($token === '' || !hash_equals($csrf, $token)) {
        header('Location: /admin/deploy.php');
        exit;
    }
    $liveCheck = deployLiveVerification();
}

// ── Actions ────────────────────────────────────────────────────────────────
$flash      = '';
$flashType  = 'success';
$gitOutput  = '';
$gitLog5    = '';
$zipResults = [];
$liveCheck  = $liveCheck ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Regenerate token (no CSRF needed — it IS the CSRF-equivalent secret)
    if ($action === 'regen_token') {
        verifyCsrf();
        $newToken = bin2hex(random_bytes(24));
        file_put_contents($tokenFile, $newToken);
        $deployToken = $newToken;
        $flash = 'Deploy token regenerated.';
    }

    // Git pull
    if ($action === 'git_pull') {
        $sentToken = $_POST['deploy_secret'] ?? '';
        if (!hash_equals($deployToken, $sentToken)) {
            $flash     = 'Invalid deploy secret.';
            $flashType = 'error';
        } else {
            $branch    = preg_replace('/[^a-zA-Z0-9_\-\/.]/', '', $_POST['branch'] ?? 'main') ?: 'main';
            $root      = escapeshellarg(dirname(__DIR__));
            $gitOutput = shell_exec('cd ' . $root . ' && git pull origin ' . escapeshellarg($branch) . ' 2>&1');
            $gitLog5   = shell_exec('cd ' . $root . ' && git log --oneline -5 2>&1');
            file_put_contents(
                dirname(__DIR__) . '/config/deploy.log',
                date('Y-m-d H:i:s') . ' Git pull by ' . $adminUser['username'] . "\n" . $gitOutput . "\n---\n",
                FILE_APPEND
            );
            $flash = 'Git pull executed.';
        }
    }

    // ZIP deploy
    if ($action === 'zip_deploy') {
        verifyCsrf();
        $file = $_FILES['zipfile'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $flash     = 'No file uploaded or upload error.';
            $flashType = 'error';
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
            $flash     = 'Only .zip files are accepted.';
            $flashType = 'error';
        } elseif ($file['size'] > 50 * 1024 * 1024) {
            $flash     = 'File exceeds 50 MB limit.';
            $flashType = 'error';
        } else {
            $protected = [
                'config/db.php',         // never overwrite — may hold legacy credentials
                'config/db.local.php',   // never overwrite — production credentials
                'config/.installed',
                'config/.deploy_token',
            ];
            $protectedDirPrefix = 'uploads/';

            $zip    = new ZipArchive();
            $result = $zip->open($file['tmp_name']);
            if ($result !== true) {
                $flash     = 'Could not open ZIP archive (error ' . $result . ').';
                $flashType = 'error';
            } else {
                $root      = dirname(__DIR__);
                $tmpDir    = sys_get_temp_dir() . '/vdj_deploy_' . uniqid();
                mkdir($tmpDir, 0755, true);
                $zip->extractTo($tmpDir);
                $zip->close();

                $deployBase = $tmpDir;
                $topItems = array_values(array_filter(scandir($tmpDir) ?: [], fn($entry) => $entry !== '.' && $entry !== '..'));
                if (count($topItems) === 1 && is_dir($tmpDir . '/' . $topItems[0])) {
                    $candidate = $tmpDir . '/' . $topItems[0];
                    if (is_file($candidate . '/includes/header.php') || is_file($candidate . '/index.php')) {
                        $deployBase = $candidate;
                    }
                }

                $rit = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($deployBase, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($rit as $item) {
                    if ($item->isDir()) continue;

                    $relPath = ltrim(str_replace($deployBase, '', $item->getPathname()), DIRECTORY_SEPARATOR . '/');
                    $relPath = str_replace('\\', '/', $relPath);

                    // Skip protected paths
                    $skip = false;
                    foreach ($protected as $p) {
                        if ($relPath === $p) { $skip = true; break; }
                    }
                    if (!$skip && strpos($relPath, $protectedDirPrefix) === 0) {
                        $skip = true;
                    }
                    if ($skip) continue;

                    $destPath = $root . '/' . $relPath;
                    $destDir  = dirname($destPath);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($item->getPathname(), $destPath);
                    $zipResults[] = $relPath;
                }

                // Clean up temp dir
                $clean = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($clean as $cleanItem) {
                    $cleanItem->isDir() ? rmdir($cleanItem->getPathname()) : unlink($cleanItem->getPathname());
                }
                rmdir($tmpDir);

                $count = count($zipResults);
                file_put_contents(
                    $root . '/config/deploy.log',
                    date('Y-m-d H:i:s') . ' ZIP deploy by ' . $adminUser['username'] . ' — ' . $count . " file(s) updated\n---\n",
                    FILE_APPEND
                );
                $flash = "ZIP deployed successfully. {$count} file(s) updated.";
                $liveCheck = deployLiveVerification();
                if (!deployVerificationOk($liveCheck)) {
                    $flash .= ' Live verification still sees stale output. Check deploy target or CDN/browser cache.';
                    $flashType = 'error';
                }

                // Save rollback snapshot of the uploaded ZIP
                $rollbackFile = dirname(__DIR__) . '/config/.rollback.zip';
                copy($file['tmp_name'], $rollbackFile);
                file_put_contents(
                    $root . '/config/deploy.log',
                    date('Y-m-d H:i:s') . ' Rollback snapshot saved (' . round($file['size'] / 1024) . " KB)\n---\n",
                    FILE_APPEND
                );
            }
        }
    }

    // ── Create rollback snapshot ───────────────────────────────────────────
    if ($action === 'create_rollback') {
        verifyCsrf();
        $rollbackFile = dirname(__DIR__) . '/config/.rollback.zip';
        $siteRoot     = dirname(__DIR__);
        $protected    = ['config/db.php', 'config/db.local.php', 'config/.installed', 'config/.deploy_token'];
        $skipDirs     = ['uploads', 'config'];

        $zip = new ZipArchive();
        if ($zip->open($rollbackFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $rit = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($rit as $item) {
                if ($item->isDir()) continue;
                $rel = ltrim(str_replace($siteRoot, '', $item->getPathname()), DIRECTORY_SEPARATOR . '/');
                $rel = str_replace('\\', '/', $rel);
                $topDir = explode('/', $rel)[0];
                if (in_array($topDir, $skipDirs)) continue;
                if (in_array($rel, $protected)) continue;
                $zip->addFile($item->getPathname(), $rel);
            }
            $zip->close();
            $sz    = round(filesize($rollbackFile) / 1024 / 1024, 1);
            $flash = "Rollback snapshot created ({$sz} MB) — " . date('Y-m-d H:i:s');
            file_put_contents(
                $siteRoot . '/config/deploy.log',
                date('Y-m-d H:i:s') . ' Rollback snapshot created manually by ' . $adminUser['username'] . "\n---\n",
                FILE_APPEND
            );
        } else {
            $flash     = 'Could not create rollback snapshot.';
            $flashType = 'error';
        }
    }

    // ── Restore rollback ───────────────────────────────────────────────────
    if ($action === 'restore_rollback') {
        verifyCsrf();
        $rollbackFile = dirname(__DIR__) . '/config/.rollback.zip';
        if (!file_exists($rollbackFile)) {
            $flash     = 'No rollback snapshot found.';
            $flashType = 'error';
        } else {
            $protected = ['config/db.php', 'config/db.local.php', 'config/.installed', 'config/.deploy_token'];
            $zip    = new ZipArchive();
            $result = $zip->open($rollbackFile);
            if ($result !== true) {
                $flash     = 'Could not open rollback ZIP (error ' . $result . ').';
                $flashType = 'error';
            } else {
                $siteRoot = dirname(__DIR__);
                $tmpDir   = sys_get_temp_dir() . '/vdj_rollback_' . uniqid();
                mkdir($tmpDir, 0755, true);
                $zip->extractTo($tmpDir);
                $zip->close();
                $rit = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                $count = 0;
                foreach ($rit as $item) {
                    if ($item->isDir()) continue;
                    $rel = ltrim(str_replace($tmpDir, '', $item->getPathname()), DIRECTORY_SEPARATOR . '/');
                    $rel = str_replace('\\', '/', $rel);
                    if (in_array($rel, $protected)) continue;
                    $destPath = $siteRoot . '/' . $rel;
                    if (!is_dir(dirname($destPath))) mkdir(dirname($destPath), 0755, true);
                    copy($item->getPathname(), $destPath);
                    $count++;
                }
                // Cleanup tmp
                $clean = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($clean as $ci) { $ci->isDir() ? rmdir($ci->getPathname()) : unlink($ci->getPathname()); }
                rmdir($tmpDir);
                $flash = "Rollback restored — {$count} file(s) reverted.";
                file_put_contents(
                    $siteRoot . '/config/deploy.log',
                    date('Y-m-d H:i:s') . ' ROLLBACK restored by ' . $adminUser['username'] . " — {$count} files\n---\n",
                    FILE_APPEND
                );
            }
        }
    }
}

// ── Rollback snapshot info ─────────────────────────────────────────────────
$rollbackFile   = dirname(__DIR__) . '/config/.rollback.zip';
$rollbackExists = file_exists($rollbackFile);
$rollbackSize   = $rollbackExists ? round(filesize($rollbackFile) / 1024 / 1024, 1) . ' MB' : null;
$rollbackDate   = $rollbackExists ? date('Y-m-d H:i', filemtime($rollbackFile)) : null;

// ── Current git info ───────────────────────────────────────────────────────
$root           = escapeshellarg(dirname(__DIR__));
$currentCommit  = trim(shell_exec('cd ' . $root . ' && git rev-parse --short HEAD 2>&1') ?? '');
$lastCommitMsg  = trim(shell_exec('cd ' . $root . ' && git log --oneline -1 2>&1') ?? '');

// ── Deploy log (last 30 lines) ─────────────────────────────────────────────
$logFile    = dirname(__DIR__) . '/config/deploy.log';
$deployLog  = '';
if (file_exists($logFile)) {
    $lines     = file($logFile);
    $last30    = array_slice($lines, -30);
    $deployLog = implode('', $last30);
}

// ── Masked token ───────────────────────────────────────────────────────────
$maskedToken = substr($deployToken, 0, 6) . '••••••••••••••';

$currentPage = 'deploy';
$webhookUrl  = publicUrl('/admin/deploy.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deploy — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --accent: #7F77DD;
    --accent-light: rgba(127,119,221,.12);
    --sidebar-bg: #0f172a;
    --sidebar-width: 240px;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --bg: #f8fafc;
    --white: #ffffff;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --success: #22c55e;
    --radius: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

/* Sidebar */
.admin-sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform .25s ease; }
.admin-sidebar__logo { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.08); }
.admin-sidebar__logo .brand { font-family: 'Manrope', sans-serif; font-weight: 700; font-size: 15px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
.admin-sidebar__logo .brand i { color: var(--accent); font-size: 18px; }
.admin-sidebar__logo .admin-chip { display: inline-block; background: var(--accent); color: #fff; font-size: 10px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; padding: 2px 7px; border-radius: 20px; margin-top: 6px; }
.admin-sidebar__nav { flex: 1; padding: 16px 0; overflow-y: auto; }
.admin-sidebar__nav a { display: flex; align-items: center; gap: 12px; padding: 10px 20px; color: rgba(255,255,255,.65); text-decoration: none; font-size: 14px; font-weight: 500; border-left: 3px solid transparent; transition: all .15s ease; }
.admin-sidebar__nav a i { width: 16px; text-align: center; font-size: 14px; }
.admin-sidebar__nav a:hover { color: #fff; background: rgba(255,255,255,.06); }
.admin-sidebar__nav a.active { color: #fff; background: rgba(127,119,221,.18); border-left-color: var(--accent); }
.nav-section-label { padding: 16px 20px 6px; font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.3); }
.admin-sidebar__footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
.admin-sidebar__footer a { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,.5); text-decoration: none; font-size: 13px; transition: color .15s; }
.admin-sidebar__footer a:hover { color: #fff; }

/* Main */
.admin-main { margin-left: var(--sidebar-width); flex: 1; overflow-y: auto; padding: 32px; }
.admin-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; gap: 16px; }
.admin-header h1 { font-family: 'Manrope', sans-serif; font-size: 24px; font-weight: 700; }
.admin-header p { font-size: 14px; color: var(--text-muted); margin-top: 3px; }
.hamburger { display: none; background: var(--white); border: 1px solid var(--border); border-radius: 8px; padding: 8px 11px; cursor: pointer; font-size: 16px; color: var(--text); }

/* Flash */
.flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.flash-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Token card */
.token-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.token-card__label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.token-display { font-family: 'Courier New', monospace; font-size: 15px; font-weight: 600; color: var(--text); background: #f8fafc; border: 1px solid var(--border); padding: 8px 14px; border-radius: 8px; letter-spacing: 1px; user-select: none; }
.token-actions { display: flex; gap: 8px; margin-left: auto; }

/* Cards grid */
.deploy-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.admin-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.admin-card__header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
.admin-card__header h2 { font-size: 16px; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 8px; }
.admin-card__header h2 i { color: var(--accent); }
.admin-card__body { padding: 20px; }

/* Form elements */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 6px; }
.form-input { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; background: var(--white); color: var(--text); }
.form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); }
.form-hint { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; font-family: inherit; cursor: pointer; border: none; text-decoration: none; transition: all .15s; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: #6b62cc; }
.btn-secondary { background: var(--white); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: #f1f5f9; }
.btn-danger { background: var(--white); color: var(--danger); border: 1px solid var(--danger-light); }
.btn-danger:hover { background: var(--danger-light); }
.btn-sm { padding: 6px 12px; font-size: 13px; }

/* Output pre */
.output-pre { background: #0f172a; color: #e2e8f0; font-family: 'Courier New', monospace; font-size: 12px; padding: 14px 16px; border-radius: 8px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; margin-top: 16px; max-height: 300px; overflow-y: auto; line-height: 1.6; }

/* Git status row */
.git-status { background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.git-status__commit { font-family: 'Courier New', monospace; background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.git-status__msg { font-size: 13px; color: var(--text-muted); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.live-check-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; margin-top: 14px; }
.live-check-item { display: flex; align-items: center; gap: 8px; padding: 9px 11px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-size: 13px; }
.live-check-item.ok { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
.live-check-item.fail { color: #991b1b; background: #fee2e2; border-color: #fecaca; }
.live-check-url { margin-top: 10px; font-family: 'Courier New', monospace; font-size: 11px; color: var(--text-muted); word-break: break-all; }

/* ZIP results */
.zip-results { margin-top: 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 12px 16px; max-height: 200px; overflow-y: auto; }
.zip-results li { font-size: 12px; font-family: 'Courier New', monospace; color: var(--text-muted); padding: 2px 0; list-style: none; }
.zip-results li::before { content: '+ '; color: var(--success); font-weight: 700; }

/* Log section */
.log-section { margin-bottom: 24px; }
.log-section h2 { font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.log-section h2 i { color: var(--accent); }
.log-pre { background: #0f172a; color: #94a3b8; font-family: 'Courier New', monospace; font-size: 12px; padding: 16px; border-radius: var(--radius); overflow-x: auto; white-space: pre-wrap; word-break: break-all; max-height: 320px; overflow-y: auto; line-height: 1.7; }
.log-empty { color: var(--text-muted); font-size: 13px; }

/* Collapsible webhook section */
.webhook-section { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 24px; overflow: hidden; }
.webhook-toggle { width: 100%; text-align: left; padding: 16px 20px; font-size: 15px; font-weight: 600; font-family: inherit; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; color: var(--text); }
.webhook-toggle i { color: var(--accent); }
.webhook-toggle .chevron { margin-left: auto; transition: transform .2s; font-size: 12px; color: var(--text-muted); }
.webhook-toggle.open .chevron { transform: rotate(180deg); }
.webhook-body { display: none; padding: 0 20px 20px; }
.webhook-body.open { display: block; }
.webhook-url-row { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.webhook-url { font-family: 'Courier New', monospace; font-size: 13px; background: #f8fafc; border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; color: var(--text); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.steps { list-style: none; counter-reset: steps; }
.steps li { counter-increment: steps; display: flex; gap: 12px; padding: 8px 0; font-size: 14px; color: var(--text); border-bottom: 1px solid #f1f5f9; }
.steps li:last-child { border-bottom: none; }
.steps li::before { content: counter(steps); display: flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; border-radius: 50%; background: var(--accent-light); color: var(--accent); font-size: 11px; font-weight: 700; margin-top: 1px; }
.steps li code { font-family: 'Courier New', monospace; font-size: 12px; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; }

/* Sidebar overlay */
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 99; }
@media (max-width: 900px) { .deploy-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-sidebar.open { transform: translateX(0); }
    .sidebar-overlay.open { display: block; }
    .admin-main { margin-left: 0; padding: 20px; }
    .hamburger { display: flex; align-items: center; }
    .token-card { flex-direction: column; align-items: flex-start; }
    .token-actions { margin-left: 0; }
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<aside class="admin-sidebar" id="sidebar">
    <div class="admin-sidebar__logo">
        <a href="/admin/" class="brand"><i class="fa-solid fa-compass-drafting"></i> Visual Design</a>
        <div class="admin-chip">Admin Panel</div>
    </div>
    <nav class="admin-sidebar__nav">
        <div class="nav-section-label">Main</div>
        <a href="/admin/"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/admin/users.php"><i class="fa-solid fa-users"></i> Users</a>
        <a href="/admin/boards.php"><i class="fa-solid fa-border-all"></i> Boards</a>
        <div class="nav-section-label">System</div>
        <a href="/admin/deploy.php" class="active"><i class="fa-solid fa-rocket"></i> Deploy</a>
        <a href="/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
    </nav>
    <div class="admin-sidebar__footer">
        <a href="/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign out (<?= e($adminUser['username']) ?>)</a>
    </div>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Deploy</h1>
            <p>Push updates to the live site via Git or ZIP upload.</p>
        </div>
        <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash-<?= $flashType === 'error' ? 'error' : 'success' ?>"><?= e($flash) ?></div>
    <?php endif; ?>

    <!-- Token card -->
    <div class="token-card">
        <div>
            <div class="token-card__label"><i class="fa-solid fa-key"></i> Deploy Token</div>
            <div class="token-display" id="maskedTokenDisplay"><?= e($maskedToken) ?></div>
        </div>
        <div class="token-actions">
            <button type="button" class="btn btn-secondary btn-sm" onclick="copyToken()">
                <i class="fa-solid fa-copy"></i> Copy
            </button>
            <form method="post" action="/admin/deploy.php" onsubmit="return confirm('Regenerate the deploy token? Existing webhook configurations will break.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="regen_token">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-arrows-rotate"></i> Regenerate
                </button>
            </form>
        </div>
    </div>

    <!-- Current git status -->
    <?php if ($currentCommit): ?>
    <div class="git-status" style="margin-bottom:20px;">
        <i class="fa-brands fa-git-alt" style="color:#f05033;font-size:18px;"></i>
        <span class="git-status__commit"><?= e($currentCommit) ?></span>
        <span class="git-status__msg"><?= e($lastCommitMsg) ?></span>
    </div>
    <?php endif; ?>

    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-stethoscope"></i> Live Deploy Verification</h2>
        </div>
        <div class="admin-card__body">
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">
                Expected build: <code><?= e(VDJ_BUILD_ID) ?></code> · CSS version: <code><?= e(VDJ_ASSET_VERSION) ?></code>
            </p>
            <a href="/admin/deploy.php?action=live_check&amp;csrf_token=<?= e($csrf) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-rotate"></i> Check live output
            </a>
            <?php if (!empty($liveCheck)): ?>
                <?php
                $checkLabels = [
                    'html_fetched' => 'HTML fetched',
                    'css_fetched' => 'CSS fetched',
                    'build_marker' => 'HTML build marker',
                    'asset_version' => 'HTML asset version',
                    'nav_more' => 'HTML nav__more',
                    'nav_optional' => 'HTML nav__optional',
                    'css_grid' => 'CSS grid navbar',
                    'css_marker' => 'CSS build marker',
                ];
                ?>
                <div class="live-check-grid">
                    <?php foreach ($checkLabels as $key => $label): ?>
                        <?php $ok = !empty($liveCheck[$key]); ?>
                        <div class="live-check-item <?= $ok ? 'ok' : 'fail' ?>">
                            <i class="fa-solid <?= $ok ? 'fa-check' : 'fa-xmark' ?>"></i>
                            <?= e($label) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="live-check-url">HTML: <?= e($liveCheck['home_url'] ?? '') ?></div>
                <div class="live-check-url">CSS: <?= e($liveCheck['css_url'] ?? '') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Method cards -->
    <div class="deploy-grid">

        <!-- Method A: Git Pull -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2><i class="fa-brands fa-git-alt"></i> Method A — Git Pull</h2>
            </div>
            <div class="admin-card__body">
                <form method="post" action="/admin/deploy.php">
                    <input type="hidden" name="action" value="git_pull">
                    <input type="hidden" name="deploy_secret" value="<?= e($deployToken) ?>">

                    <div class="form-group">
                        <label class="form-label" for="branch">Branch</label>
                        <input type="text" id="branch" name="branch" class="form-input" value="main" placeholder="main">
                        <div class="form-hint">Branch to pull from origin.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Pull Now
                    </button>
                </form>

                <?php if ($gitOutput !== ''): ?>
                    <pre class="output-pre"><?= e($gitOutput) ?></pre>
                    <?php if ($gitLog5): ?>
                        <div style="margin-top:12px;">
                            <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Last 5 commits</div>
                            <pre class="output-pre" style="margin-top:0;"><?= e($gitLog5) ?></pre>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Method B: ZIP Upload -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2><i class="fa-solid fa-file-zipper"></i> Method B — ZIP Upload</h2>
            </div>
            <div class="admin-card__body">
                <form method="post" action="/admin/deploy.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="zip_deploy">

                    <div class="form-group">
                        <label class="form-label" for="zipfile">ZIP File</label>
                        <input type="file" id="zipfile" name="zipfile" class="form-input" accept=".zip">
                        <div class="form-hint">Max 50 MB. Config, credentials and uploads/ are protected and will not be overwritten.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-upload"></i> Upload &amp; Deploy
                    </button>
                </form>

                <?php if (!empty($zipResults)): ?>
                    <div style="margin-top:14px;font-size:13px;font-weight:500;color:var(--text);">
                        <?= count($zipResults) ?> file(s) updated:
                    </div>
                    <ul class="zip-results">
                        <?php foreach ($zipResults as $f): ?>
                            <li><?= e($f) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Backup downloads -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-download"></i> Backup Downloads</h2>
        </div>
        <div class="admin-card__body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <p style="font-size:14px;color:var(--text);margin:0 0 4px;font-weight:600;">Download code + database without the huge uploads folder</p>
                <p style="font-size:13px;color:var(--text-muted);margin:0;">Light backup is the safe default for shared hosting. It excludes uploads, cache, existing ZIP files, production credentials and deploy secrets.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="/admin/deploy.php?action=download_backup&amp;scope=light&amp;csrf_token=<?= e($csrf) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-file-arrow-down"></i> Light Backup ZIP
                </a>
                <a href="/admin/deploy.php?action=download_backup&amp;scope=sql&amp;csrf_token=<?= e($csrf) ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-database"></i> SQL Only ZIP
                </a>
                <a href="/admin/deploy.php?action=download_backup&amp;scope=full&amp;csrf_token=<?= e($csrf) ?>" class="btn btn-danger" onclick="return confirm('Full backup includes uploads and can fail on shared hosting when media is very large. Continue only if you really need it.');">
                    <i class="fa-solid fa-triangle-exclamation"></i> Full With Uploads
                </a>
            </div>
        </div>
    </div>

    <!-- GitHub Webhook instructions -->
    <div class="webhook-section">
        <button type="button" class="webhook-toggle" id="webhookToggle">
            <i class="fa-brands fa-github"></i>
            GitHub Webhook Setup
            <i class="fa-solid fa-chevron-down chevron"></i>
        </button>
        <div class="webhook-body" id="webhookBody">
            <p style="font-size:14px;color:var(--text-muted);margin-bottom:16px;">
                Connect your GitHub repository so every push automatically deploys to this server.
            </p>
            <div style="margin-bottom:12px;">
                <div class="form-label">Payload URL</div>
                <div class="webhook-url-row">
                    <span class="webhook-url" id="webhookUrl"><?= e($webhookUrl) ?></span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyWebhookUrl()">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <div class="form-label">Secret</div>
                <div style="font-family:'Courier New',monospace;font-size:13px;background:#f8fafc;border:1px solid var(--border);padding:8px 12px;border-radius:8px;color:var(--text);">
                    <?= e($maskedToken) ?> &nbsp;
                    <button type="button" class="btn btn-secondary btn-sm" style="display:inline-flex;" onclick="copyToken()"><i class="fa-solid fa-copy"></i> Copy token</button>
                </div>
            </div>
            <ol class="steps">
                <li>Go to your GitHub repository &rarr; <strong>Settings</strong> &rarr; <strong>Webhooks</strong> &rarr; <strong>Add webhook</strong>.</li>
                <li>Paste the Payload URL above into the <code>Payload URL</code> field.</li>
                <li>Set <code>Content type</code> to <code>application/json</code>.</li>
                <li>Paste your deploy token into the <code>Secret</code> field.</li>
                <li>Under <em>Which events…</em>, select <strong>Just the push event</strong>.</li>
                <li>Make sure <strong>Active</strong> is checked, then click <strong>Add webhook</strong>.</li>
                <li>GitHub will send a ping — the server will respond with 200 and trigger a <code>git pull</code> on every subsequent push.</li>
            </ol>
        </div>
    </div>

    <!-- Rollback Workflow -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-rotate-left"></i> Rollback Snapshot</h2>
            <?php if ($rollbackExists): ?>
                <span style="font-size:12px;color:var(--muted);">
                    Saved <?= e($rollbackDate) ?> &mdash; <?= e($rollbackSize) ?>
                </span>
            <?php endif; ?>
        </div>
        <div style="padding:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <?php if (!$rollbackExists): ?>
                <p style="font-size:13px;color:var(--muted);margin:0;">No snapshot saved yet. Create one before your next deploy.</p>
            <?php else: ?>
                <div style="flex:1;min-width:220px;">
                    <p style="font-size:13px;color:var(--muted);margin:0 0 4px;">
                        <i class="fa-solid fa-circle-check" style="color:#22c55e;"></i>
                        Snapshot ready &mdash; <strong><?= e($rollbackSize) ?></strong> saved on <strong><?= e($rollbackDate) ?></strong>
                    </p>
                    <p style="font-size:12px;color:var(--muted);margin:0;">Restoring will overwrite current files (except db.php, credentials, uploads).</p>
                </div>
                <form method="post" onsubmit="return confirm('Restore rollback? Current files will be overwritten.');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="restore_rollback">
                    <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Restore this snapshot
                    </button>
                </form>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="create_rollback">
                <button type="submit" style="background:#111;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">
                    <i class="fa-solid fa-camera"></i> <?= $rollbackExists ? 'Update snapshot' : 'Create snapshot now' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Deploy Log -->
    <div class="log-section">
        <h2><i class="fa-solid fa-terminal"></i> Deploy Log</h2>
        <?php if ($deployLog !== ''): ?>
            <pre class="log-pre"><?= e($deployLog) ?></pre>
        <?php else: ?>
            <p class="log-empty">No deploy activity yet.</p>
        <?php endif; ?>
    </div>

</main>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
document.getElementById('hamburger').addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Copy full token (stored as data attribute)
const fullToken = <?= json_encode($deployToken) ?>;

function copyToken() {
    navigator.clipboard.writeText(fullToken).then(() => {
        alert('Token copied to clipboard.');
    }).catch(() => {
        prompt('Copy this token:', fullToken);
    });
}

function copyWebhookUrl() {
    const url = document.getElementById('webhookUrl').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        alert('Webhook URL copied.');
    }).catch(() => {
        prompt('Copy this URL:', url);
    });
}

// Collapsible webhook section
const webhookToggle = document.getElementById('webhookToggle');
const webhookBody   = document.getElementById('webhookBody');
webhookToggle.addEventListener('click', () => {
    webhookToggle.classList.toggle('open');
    webhookBody.classList.toggle('open');
});
</script>
</body>
</html>
