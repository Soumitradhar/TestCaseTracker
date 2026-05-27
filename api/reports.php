<?php
// ============================================================
//  api/reports.php  —  File Upload / Download / Delete API
//
//  GET    ?project=neo-nature           → list reports
//  GET    ?download=1&id=5              → download file
//  POST   multipart: project,title,desc,file → upload
//  DELETE ?id=5                          → delete
// ============================================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// Upload directory (inside testflow/uploads/)
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Allowed file types
$allowedTypes = [
    'application/pdf',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'text/plain',
    'text/csv',
];

function getProjectId(PDO $db, string $slug): int {
    $stmt = $db->prepare('SELECT id FROM projects WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) respondError('Project not found', 404);
    return (int)$row['id'];
}

function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

switch ($method) {

    // ── LIST ──────────────────────────────────────────────
    case 'GET':
        // Download a file
        if (!empty($_GET['download']) && !empty($_GET['id'])) {
            $id   = (int)$_GET['id'];
            $stmt = $db->prepare('SELECT * FROM reports WHERE id = ?');
            $stmt->execute([$id]);
            $row  = $stmt->fetch();
            if (!$row) respondError('File not found', 404);

            $path = $uploadDir . $row['file_name'];
            if (!file_exists($path)) respondError('File missing on server', 404);

            header('Content-Type: ' . $row['file_type']);
            header('Content-Disposition: attachment; filename="' . addslashes($row['original_name']) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-cache');
            readfile($path);
            exit();
        }

        // List reports for a project
        $slug = $_GET['project'] ?? '';
        if (!$slug) respondError('project param required');
        $pid  = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'SELECT id, title, description, original_name, file_name, file_type, file_size, uploaded_at
             FROM reports WHERE project_id = ? ORDER BY uploaded_at DESC'
        );
        $stmt->execute([$pid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['file_size_fmt'] = formatSize((int)$r['file_size']);
            $r['ext'] = strtolower(pathinfo($r['original_name'], PATHINFO_EXTENSION));
        }
        respond($rows);
        break;

    // ── UPLOAD ────────────────────────────────────────────
    case 'POST':
        $slug  = trim($_POST['project'] ?? '');
        $title = trim($_POST['title']   ?? '');
        $desc  = trim($_POST['desc']    ?? '');

        if (!$slug || !$title) respondError('project and title are required');
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            respondError('File upload failed — error code: ' . ($_FILES['file']['error'] ?? 'no file'));
        }

        $file         = $_FILES['file'];
        $originalName = basename($file['name']);
        
        // Use finfo instead of deprecated mime_content_type()
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileSize     = (int)$file['size'];

        // Validate type
        if (!in_array($mimeType, $allowedTypes)) {
            respondError('File type not allowed: ' . $mimeType . '. Allowed: PDF, Excel, Word, Images, CSV, TXT');
        }

        // Max 20 MB
        if ($fileSize > 20 * 1024 * 1024) {
            respondError('File too large. Maximum size is 20 MB.');
        }

        // Save with unique name
        $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName = uniqid('rpt_', true) . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            respondError('Could not save file on server. Check uploads/ folder permissions.');
        }

        $pid  = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'INSERT INTO reports (project_id, title, description, file_name, original_name, file_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$pid, $title, $desc, $fileName, $originalName, $mimeType, $fileSize]);
        $newId = (int)$db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM reports WHERE id = ?');
        $stmt->execute([$newId]);
        $row = $stmt->fetch();
        $row['file_size_fmt'] = formatSize((int)$row['file_size']);
        $row['ext'] = strtolower(pathinfo($row['original_name'], PATHINFO_EXTENSION));
        respond($row, 201);
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respondError('id required');

        $stmt = $db->prepare('SELECT file_name FROM reports WHERE id = ?');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if (!$row) respondError('Report not found', 404);

        // Delete physical file
        $path = $uploadDir . $row['file_name'];
        if (file_exists($path)) unlink($path);

        $db->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
        respond(['deleted' => true, 'id' => $id]);
        break;

    default:
        respondError('Method not allowed', 405);
}
