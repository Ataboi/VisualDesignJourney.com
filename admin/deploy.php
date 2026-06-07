<?php
function deployRootPath(): string {
    return dirname(__DIR__);
}

function deployGitAvailable(): bool {
    return is_dir(deployRootPath() . '/.git');
}

function deployGitUnavailableMessage(): string {
    return 'This site root is not a Git checkout. Use ZIP deploy or the outbound deploy webhook instead.';
}

function deployAppendLog(string $entry): void {
    file_put_contents(deployRootPath() . '/config/deploy.log', $entry, FILE_APPEND);
}

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
        header('Content-Type: application/json');

        if (!deployGitAvailable()) {
            $output = deployGitUnavailableMessage();
            deployAppendLog(date('Y-m-d H:i:s') . ' GitHub webhook pull skipped' . "\n" . $output . "\n---\n");
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => $output]);
        } else {
            $branch = 'main';
            $output = shell_exec('cd ' . escapeshellarg(deployRootPath()) . ' && git pull origin ' . escapeshellarg($branch) . ' 2>&1');
            deployAppendLog(date('Y-m-d H:i:s') . ' GitHub webhook pull' . "\n" . $output . "\n---\n");
            http_response_code(200);
            echo json_encode(['ok' => true, 'output' => $output]);
        }
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    }
    exit;
}

if (($_GET['action'] ?? '') === 'status_callback') {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/deploy_sync.php';

    header('Content-Type: application/json');

    try {
        $callbackDb = getDB();
        deploySyncBootstrap($callbackDb);
        $callbackSettings = deploySyncSettings($callbackDb);
        $callbackSecret = trim((string)($callbackSettings['webhook_secret'] ?? ''));

        if ($callbackSecret === '') {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Status callback is not enabled.']);
            exit;
        }

        $timestamp = $_SERVER['HTTP_X_VDJ_TIMESTAMP'] ?? '';
        $event = $_SERVER['HTTP_X_VDJ_EVENT'] ?? '';
        $signature = $_SERVER['HTTP_X_VDJ_SIGNATURE'] ?? '';

        if (!deploySyncVerifySignature($rawBody, $callbackSecret, $timestamp, $event, $signature)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid status callback signature.']);
            exit;
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload.']);
            exit;
        }

        $result = deploySyncApplyIncomingStatus($callbackDb, $callbackSettings, $payload, $rawBody);
        echo json_encode($result);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not save status callback.']);
    }
    exit;
}

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../includes/deploy_sync.php';

deploySyncBootstrap($db);
$deploySettings = deploySyncSettings($db);

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

function deployChangedPathsFromPost(): array {
    $raw = (string)($_POST['changed_paths'] ?? '');
    if ($raw === '') return [];

    $paths = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $paths = array_map(fn($path) => trim($path), $paths);
    $paths = array_filter($paths, fn($path) => $path !== '' && !str_contains($path, '..'));

    return array_values(array_slice($paths, 0, 50));
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

    // Save outbound deploy webhook settings.
    if ($action === 'save_webhook') {
        verifyCsrf();
        $result = deploySyncSaveWebhook($db, $deploySettings, $_POST);
        $flash = $result['message'];
        $flashType = !empty($result['ok']) ? 'success' : 'error';
        $deploySettings = deploySyncSettings($db);
    }

    // Send a harmless test payload to the saved webhook.
    if ($action === 'test_webhook') {
        verifyCsrf();
        $result = deploySyncSendAction(
            $db,
            deploySyncSettings($db),
            'test',
            $_POST['deploy_environment'] ?? ($deploySettings['environment'] ?? 'production'),
            $adminUser['username'] ?? 'admin'
        );
        $flash = $result['message'];
        $flashType = !empty($result['ok']) ? 'success' : 'error';
        $deploySettings = deploySyncSettings($db);
    }

    // Trigger deploy/sync/rebuild/publish through the saved outbound webhook.
    if ($action === 'trigger_webhook') {
        verifyCsrf();
        $deployAction = deploySyncAllowedAction($_POST['deploy_action'] ?? 'deploy');
        if ($deployAction === 'test') $deployAction = 'deploy';
        $result = deploySyncSendAction(
            $db,
            deploySyncSettings($db),
            $deployAction,
            $_POST['deploy_environment'] ?? ($deploySettings['environment'] ?? 'production'),
            $adminUser['username'] ?? 'admin',
            deployChangedPathsFromPost()
        );
        $flash = $result['message'];
        $flashType = !empty($result['ok']) ? 'success' : 'error';
        $deploySettings = deploySyncSettings($db);
    }

    // Pull current deploy status from a saved or discovered status URL.
    if ($action === 'pull_status') {
        verifyCsrf();
        $result = deploySyncPullStatus($db, deploySyncSettings($db), $adminUser['username'] ?? 'admin');
        $flash = $result['message'];
        $flashType = !empty($result['ok']) ? 'success' : 'error';
        $deploySettings = deploySyncSettings($db);
    }

    // Retry the most recent failed webhook-style action.
    if ($action === 'retry_webhook') {
        verifyCsrf();
        $failed = deploySyncLatestFailed($db);
        if (!$failed) {
            $flash = 'No failed deploy webhook action is available to retry.';
            $flashType = 'error';
        } else {
            $retryAction = deploySyncAllowedAction((string)($failed['action'] ?? 'deploy'));
            if (!in_array($retryAction, ['deploy', 'sync', 'rebuild', 'publish'], true)) {
                $retryAction = 'deploy';
            }
            $result = deploySyncSendAction(
                $db,
                deploySyncSettings($db),
                $retryAction,
                $failed['environment'] ?? ($deploySettings['environment'] ?? 'production'),
                $adminUser['username'] ?? 'admin'
            );
            $flash = $result['message'];
            $flashType = !empty($result['ok']) ? 'success' : 'error';
            $deploySettings = deploySyncSettings($db);
        }
    }

    // Git pull
    if ($action === 'git_pull') {
        verifyCsrf();
        if (!deployGitAvailable()) {
            $gitOutput = deployGitUnavailableMessage();
            deployAppendLog(
                date('Y-m-d H:i:s') . ' Git pull skipped by ' . $adminUser['username'] . "\n" . $gitOutput . "\n---\n"
            );
            $flash = $gitOutput;
            $flashType = 'error';
        } else {
            $branch    = preg_replace('/[^a-zA-Z0-9_\-\/.]/', '', $_POST['branch'] ?? 'main') ?: 'main';
            $root      = escapeshellarg(deployRootPath());
            $gitOutput = shell_exec('cd ' . $root . ' && git pull origin ' . escapeshellarg($branch) . ' 2>&1');
            $gitLog5   = shell_exec('cd ' . $root . ' && git log --oneline -5 2>&1');
            deployAppendLog(
                date('Y-m-d H:i:s') . ' Git pull by ' . $adminUser['username'] . "\n" . $gitOutput . "\n---\n",
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
$gitAvailable   = deployGitAvailable();
$root           = escapeshellarg(deployRootPath());
$currentCommit  = '';
$lastCommitMsg  = deployGitUnavailableMessage();
if ($gitAvailable) {
    $currentCommit = trim(shell_exec('cd ' . $root . ' && git rev-parse --short HEAD 2>&1') ?? '');
    $lastCommitMsg = trim(shell_exec('cd ' . $root . ' && git log --oneline -1 2>&1') ?? '');
}

// ── Deploy log (last 30 lines) ─────────────────────────────────────────────
$logFile    = dirname(__DIR__) . '/config/deploy.log';
$deployLog  = '';
if (file_exists($logFile)) {
    $lines     = file($logFile);
    $last30    = array_slice($lines, -30);
    $deployLog = implode('', $last30);
}

// ── Outbound webhook sync state ───────────────────────────────────────────
$deploySettings     = deploySyncSettings($db);
$deployHistory      = deploySyncHistory($db, 10);
$latestFailedDeploy = deploySyncLatestFailed($db);
$savedWebhookUrl    = trim((string)($deploySettings['webhook_url'] ?? ''));
$savedStatusUrl     = deploySyncStatusUrlFromSettings($deploySettings);
$webhookConnected   = $savedWebhookUrl !== '';
$statusConnected    = trim($savedStatusUrl) !== '';
$syncState          = trim((string)($deploySettings['sync_state'] ?? 'idle')) ?: 'idle';
$syncStateClass     = deploySyncStateClass($syncState);
$lockUntil          = !empty($deploySettings['in_progress_until']) ? strtotime((string)$deploySettings['in_progress_until']) : false;
$deployBusy         = in_array($syncState, ['pending', 'running'], true) && (!$lockUntil || $lockUntil > time());

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
.flash-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

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

.webhook-panel-grid { display: grid; grid-template-columns: minmax(0, .85fr) minmax(320px, 1.15fr); gap: 18px; align-items: start; }
.deploy-status-card { border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; padding: 16px; }
.deploy-status-card h3 { font-size: 14px; font-weight: 700; margin-bottom: 10px; }
.sync-badge { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.sync-badge.idle { background: #f1f5f9; color: #475569; }
.sync-badge.running { background: #e0f2fe; color: #075985; }
.sync-badge.success { background: #dcfce7; color: #166534; }
.sync-badge.failed { background: #fee2e2; color: #991b1b; }
.deploy-meta-list { display: grid; gap: 8px; margin-top: 14px; }
.deploy-meta-row { display: flex; justify-content: space-between; gap: 14px; font-size: 13px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
.deploy-meta-row span:first-child { color: var(--text-muted); }
.deploy-meta-row span:last-child { font-weight: 600; text-align: right; word-break: break-word; }
.webhook-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
.webhook-history { width: 100%; border-collapse: collapse; font-size: 13px; }
.webhook-history th, .webhook-history td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: top; text-align: left; }
.webhook-history th { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; background: #f8fafc; }
.history-status { display: inline-flex; border-radius: 999px; padding: 3px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.history-status.success { background: #dcfce7; color: #166534; }
.history-status.failed { background: #fee2e2; color: #991b1b; }
.history-status.pending { background: #e0f2fe; color: #075985; }
.history-details summary { cursor: pointer; color: var(--accent); font-weight: 600; }
.history-details pre { margin-top: 8px; max-height: 180px; overflow: auto; white-space: pre-wrap; word-break: break-word; background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 10px; font-size: 11px; line-height: 1.55; }
.form-input[disabled], .btn[disabled] { opacity: .55; cursor: not-allowed; }

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
@media (max-width: 900px) { .deploy-grid, .webhook-panel-grid { grid-template-columns: 1fr; } }
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
            <p>Push updates to the live site via Git, ZIP upload, or outbound webhook.</p>
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
                <i class="fa-solid fa-lock"></i> Hidden
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
    <?php elseif (!$gitAvailable): ?>
    <div class="git-status" style="margin-bottom:20px;border-color:#f59e0b;background:#fffbeb;">
        <i class="fa-solid fa-circle-info" style="color:#f59e0b;font-size:18px;"></i>
        <span class="git-status__commit">Git unavailable</span>
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

    <!-- Outbound deploy webhook -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-satellite-dish"></i> Outbound Deploy Webhook</h2>
        </div>
        <div class="admin-card__body">
            <div class="webhook-panel-grid">
                <div class="deploy-status-card">
                    <h3>Current Sync State</h3>
                    <span class="sync-badge <?= e($syncStateClass) ?>">
                        <i class="fa-solid <?= $syncStateClass === 'success' ? 'fa-circle-check' : ($syncStateClass === 'failed' ? 'fa-triangle-exclamation' : ($syncStateClass === 'running' ? 'fa-spinner' : 'fa-circle')) ?>"></i>
                        <?= e($syncState) ?>
                    </span>
                    <div class="deploy-meta-list">
                        <div class="deploy-meta-row">
                            <span>Webhook</span>
                            <span><?= $webhookConnected ? e(deploySyncMaskUrl($savedWebhookUrl)) : 'Not connected' ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>Status pull</span>
                            <span><?= $statusConnected ? e(deploySyncMaskUrl($savedStatusUrl)) : 'No status URL yet' ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>Environment</span>
                            <span><?= e((string)($deploySettings['environment'] ?? 'production')) ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>Last status</span>
                            <span><?= e((string)($deploySettings['last_status'] ?? 'None yet')) ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>HTTP code</span>
                            <span><?= !empty($deploySettings['last_http_code']) ? (int)$deploySettings['last_http_code'] : 'None' ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>Last deploy</span>
                            <span><?= e((string)($deploySettings['last_deploy_at'] ?? 'Never')) ?></span>
                        </div>
                        <div class="deploy-meta-row">
                            <span>Last checked</span>
                            <span><?= e((string)($deploySettings['last_checked_at'] ?? 'Never')) ?></span>
                        </div>
                    </div>
                    <?php if ($deployBusy): ?>
                        <p class="form-hint" style="margin-top:12px;color:#075985;">
                            A deploy is marked pending/running. Pull status before triggering another deploy.
                        </p>
                    <?php endif; ?>
                </div>

                <div>
                    <form method="post" action="/admin/deploy.php" style="display:grid;gap:14px;margin-bottom:18px;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="save_webhook">

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="outbound-webhook-url">Webhook URL</label>
                            <input type="url" id="outbound-webhook-url" name="webhook_url" class="form-input"
                                   placeholder="<?= $webhookConnected ? 'Leave blank to keep ' . e(deploySyncMaskUrl($savedWebhookUrl)) : 'https://example.com/deploy-webhook' ?>">
                            <div class="form-hint">
                                Paste a deploy/build-hook URL from your host, CI, or deploy service. This is different from the GitHub repository webhook URL below.
                                Saved URLs are kept server-side, and leaving this blank preserves the current webhook URL.
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="outbound-status-url">Status URL</label>
                            <input type="url" id="outbound-status-url" name="status_url" class="form-input"
                                   placeholder="<?= $statusConnected ? 'Leave blank to keep ' . e(deploySyncMaskUrl($savedStatusUrl)) : 'Optional status endpoint' ?>">
                            <div class="form-hint">Optional. If omitted, Pull Status can use a status_url returned by the webhook response.</div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="webhook-environment">Environment</label>
                                <select id="webhook-environment" name="environment" class="form-input">
                                    <option value="production" <?= ($deploySettings['environment'] ?? 'production') === 'production' ? 'selected' : '' ?>>Production</option>
                                    <option value="staging" <?= ($deploySettings['environment'] ?? '') === 'staging' ? 'selected' : '' ?>>Staging</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="webhook-secret">Shared secret</label>
                                <input type="password" id="webhook-secret" name="webhook_secret" class="form-input" autocomplete="new-password" placeholder="<?= trim((string)($deploySettings['webhook_secret'] ?? '')) !== '' ? 'Secret saved' : 'Optional HMAC secret' ?>">
                            </div>
                        </div>

                        <?php if (trim((string)($deploySettings['webhook_secret'] ?? '')) !== ''): ?>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);">
                                <input type="checkbox" name="clear_secret" value="1">
                                Clear saved shared secret
                            </label>
                        <?php endif; ?>

                        <div class="webhook-actions" style="margin-top:0;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Save Webhook
                            </button>
                        </div>
                    </form>

                    <div class="webhook-actions">
                        <form method="post" action="/admin/deploy.php">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="test_webhook">
                            <input type="hidden" name="deploy_environment" value="<?= e((string)($deploySettings['environment'] ?? 'production')) ?>">
                            <button type="submit" class="btn btn-secondary" <?= !$webhookConnected ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-vial"></i> Test Webhook
                            </button>
                        </form>

                        <form method="post" action="/admin/deploy.php">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="pull_status">
                            <button type="submit" class="btn btn-secondary" <?= !$statusConnected ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-rotate"></i> Pull Status
                            </button>
                        </form>

                        <form method="post" action="/admin/deploy.php">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="retry_webhook">
                            <button type="submit" class="btn btn-secondary" <?= !$latestFailedDeploy || !$webhookConnected || $deployBusy ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-arrow-rotate-right"></i> Retry Failed
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <form method="post" action="/admin/deploy.php" style="margin-top:18px;border-top:1px solid var(--border);padding-top:18px;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="trigger_webhook">
                <div style="display:grid;grid-template-columns:170px 170px minmax(0,1fr);gap:12px;align-items:end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="deploy-action">Action</label>
                        <select id="deploy-action" name="deploy_action" class="form-input">
                            <option value="deploy">Deploy</option>
                            <option value="sync">Sync</option>
                            <option value="rebuild">Rebuild</option>
                            <option value="publish">Publish</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="deploy-environment">Environment</label>
                        <select id="deploy-environment" name="deploy_environment" class="form-input">
                            <option value="production" <?= ($deploySettings['environment'] ?? 'production') === 'production' ? 'selected' : '' ?>>Production</option>
                            <option value="staging" <?= ($deploySettings['environment'] ?? '') === 'staging' ? 'selected' : '' ?>>Staging</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= !$webhookConnected || $deployBusy ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-rocket"></i> Trigger Deploy Webhook
                    </button>
                </div>
                <div class="form-group" style="margin-top:12px;margin-bottom:0;">
                    <label class="form-label" for="changed-paths">Changed paths or content IDs</label>
                    <textarea id="changed-paths" name="changed_paths" class="form-input" rows="3" placeholder="Optional, one path or content ID per line"></textarea>
                    <div class="form-hint">This is included in the webhook payload when you know what changed.</div>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Deployment History</h2>
        </div>
        <div class="admin-card__body" style="padding:0;">
            <?php if (!empty($deployHistory)): ?>
                <table class="webhook-history">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Status</th>
                            <th>HTTP</th>
                            <th>Admin</th>
                            <th>Started</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deployHistory as $history): ?>
                            <?php
                            $historyStatus = (string)($history['status'] ?? 'pending');
                            $historyStatusClass = in_array($historyStatus, ['success', 'failed'], true) ? $historyStatus : 'pending';
                            $detailText = trim((string)($history['error_message'] ?: $history['response_body'] ?: $history['payload_json'] ?: ''));
                            ?>
                            <tr>
                                <td><?= e((string)($history['action'] ?? 'deploy')) ?></td>
                                <td><span class="history-status <?= e($historyStatusClass) ?>"><?= e($historyStatus) ?></span></td>
                                <td><?= !empty($history['http_code']) ? (int)$history['http_code'] : '—' ?></td>
                                <td><?= e((string)($history['triggered_by'] ?? '—')) ?></td>
                                <td><?= e((string)($history['started_at'] ?? '—')) ?></td>
                                <td>
                                    <?php if ($detailText !== ''): ?>
                                        <details class="history-details">
                                            <summary>View</summary>
                                            <pre><?= e(deploySyncTruncate($detailText, 1800) ?? '') ?></pre>
                                        </details>
                                    <?php else: ?>
                                        <span class="text-muted">No details</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding:24px;color:var(--text-muted);font-size:14px;">
                    No deploy webhook activity has been recorded yet.
                </div>
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
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="git_pull">

                    <div class="form-group">
                        <label class="form-label" for="branch">Branch</label>
                        <input type="text" id="branch" name="branch" class="form-input" value="main" placeholder="main">
                        <div class="form-hint">
                            <?= $gitAvailable ? 'Branch to pull from origin.' : 'Git pull is disabled because this deployment is not a Git checkout.' ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"<?php if (!$gitAvailable): ?> disabled aria-disabled="true"<?php endif; ?>>
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
                <?php if ($gitAvailable): ?>
                    Connect your GitHub repository so every push automatically runs <code>git pull</code> on this server.
                <?php else: ?>
                    GitHub can send push events here, but automatic <code>git pull</code> only works when this server folder is a Git checkout.
                <?php endif; ?>
            </p>
            <?php if (!$gitAvailable): ?>
                <div class="flash flash-warning" style="margin-bottom:16px;">
                    GitHub may show a delivery as successful when the URL responds, but this ZIP-based install cannot pull code from Git.
                    Use ZIP upload here, or save a deploy/build-hook URL in the Outbound Deploy Webhook panel above.
                </div>
            <?php endif; ?>
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
                    <button type="button" class="btn btn-secondary btn-sm" style="display:inline-flex;" onclick="copyToken()"><i class="fa-solid fa-lock"></i> Hidden</button>
                </div>
            </div>
            <ol class="steps">
                <li>Go to your GitHub repository &rarr; <strong>Settings</strong> &rarr; <strong>Webhooks</strong> &rarr; <strong>Add webhook</strong>.</li>
                <li>Paste the Payload URL above into the <code>Payload URL</code> field.</li>
                <li>Set <code>Content type</code> to <code>application/json</code>.</li>
                <li>Paste your deploy token into the <code>Secret</code> field.</li>
                <li>Under <em>Which events…</em>, select <strong>Just the push event</strong>.</li>
                <li>Make sure <strong>Active</strong> is checked, then click <strong>Add webhook</strong>.</li>
                <li>GitHub will send a ping. On Git checkouts, the server responds with 200 and triggers a <code>git pull</code> on every subsequent push.</li>
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

function copyToken() {
    alert('For security, deploy tokens are not exposed in the browser. Regenerate the token only when you are ready to update the external webhook secret.');
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
