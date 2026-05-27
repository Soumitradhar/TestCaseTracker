<?php
// ============================================================
//  api/pdf_reports.php
//
//  GET    ?project=neo-nature           → list PDFs
//  GET    ?id=5&action=preview          → inline preview
//  GET    ?id=5&action=download         → force download
//  POST   multipart: project,title,desc,uploaded_by,file → upload
//  DELETE ?id=5                         → delete
// ============================================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
ensurePdfReportsTable($db);

// Upload directory — only PDFs allowed
$uploadDir = __DIR__ . '/../pdf_uploads/';

function ensurePdfReportsTable(PDO $db): void {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS `pdf_reports` (
          `id`            INT           NOT NULL AUTO_INCREMENT,
          `project_id`    INT           NOT NULL,
          `title`         VARCHAR(200)  NOT NULL,
          `description`   TEXT,
          `file_name`     VARCHAR(255)  NOT NULL,
          `original_name` VARCHAR(255)  NOT NULL,
          `file_size`     INT           NOT NULL DEFAULT 0,
          `uploaded_by`   VARCHAR(100)  DEFAULT NULL,
          `uploaded_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function getProjectId(PDO $db, string $slug): int {
    $stmt = $db->prepare('SELECT id FROM projects WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) respondError('Project not found', 404);
    return (int)$row['id'];
}

function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
    return $bytes . ' B';
}

switch ($method) {

    // ── LIST / PREVIEW / DOWNLOAD ─────────────────────────
    case 'GET':
        $id     = (int)($_GET['id']     ?? 0);
        $action = $_GET['action'] ?? '';

        // Serve file
        if ($id && in_array($action, ['preview','download'])) {
            $stmt = $db->prepare('SELECT * FROM pdf_reports WHERE id = ?');
            $stmt->execute([$id]);
            $row  = $stmt->fetch();
            if (!$row) { http_response_code(404); echo 'File not found'; exit(); }

            $path = $uploadDir . $row['file_name'];
            if (!file_exists($path)) { http_response_code(404); echo 'File missing on server'; exit(); }

            header('Content-Type: application/pdf');
            if ($action === 'download') {
                header('Content-Disposition: attachment; filename="' . addslashes($row['original_name']) . '"');
            } else {
                header('Content-Disposition: inline; filename="'     . addslashes($row['original_name']) . '"');
            }
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: max-age=3600');
            header('Accept-Ranges: bytes');
            readfile($path);
            exit();
        }

        // List PDFs for a project
        $slug = $_GET['project'] ?? '';
        if (!$slug) respondError('project param required');
        $pid  = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'SELECT id, title, description, original_name, file_name, file_size, uploaded_by, uploaded_at
             FROM pdf_reports WHERE project_id = ? ORDER BY uploaded_at DESC'
        );
        $stmt->execute([$pid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['file_size_fmt'] = formatSize((int)$r['file_size']);
        }
        respond($rows);
        break;

    // ── UPLOAD ────────────────────────────────────────────
    case 'POST':
        $slug        = trim($_POST['project']     ?? '');
        $title       = trim($_POST['title']       ?? '');
        $desc        = trim($_POST['desc']        ?? '');
        $uploadedBy  = trim($_POST['uploaded_by'] ?? '');

        if (!$slug || !$title) respondError('project and title are required');

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errCodes = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            ];
            $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            respondError($errCodes[$code] ?? 'Upload error code: ' . $code);
        }

        $file     = $_FILES['file'];
        
        // Use finfo instead of deprecated mime_content_type()
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileSize = (int)$file['size'];

        // PDF only
        if ($mimeType !== 'application/pdf') {
            respondError('Only PDF files are allowed. Detected: ' . $mimeType);
        }

        // Max 50 MB
        if ($fileSize > 50 * 1024 * 1024) {
            respondError('File too large. Maximum size is 50 MB.');
        }

        $originalName = basename($file['name']);
        $fileName     = uniqid('pdf_', true) . '.pdf';
        $destPath     = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            respondError('Could not save file. Check pdf_uploads/ folder permissions.');
        }

        $pid  = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'INSERT INTO pdf_reports (project_id, title, description, file_name, original_name, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$pid, $title, $desc, $fileName, $originalName, $fileSize, $uploadedBy ?: null]);
        $newId = (int)$db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM pdf_reports WHERE id = ?');
        $stmt->execute([$newId]);
        $row = $stmt->fetch();
        $row['file_size_fmt'] = formatSize((int)$row['file_size']);
        respond($row, 201);
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respondError('id required');

        $stmt = $db->prepare('SELECT file_name FROM pdf_reports WHERE id = ?');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if (!$row) respondError('Report not found', 404);

        $path = $uploadDir . $row['file_name'];
        if (file_exists($path)) unlink($path);

        $db->prepare('DELETE FROM pdf_reports WHERE id = ?')->execute([$id]);
        respond(['deleted' => true, 'id' => $id]);
        break;

    default:
        respondError('Method not allowed', 405);
}
