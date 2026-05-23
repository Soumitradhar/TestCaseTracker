<?php
// ============================================================
//  api/generate_report.php
//  Generates a PDF report for a project using Python/reportlab
//
//  GET ?project=neo-nature&notes=...  → streams PDF
// ============================================================

require_once __DIR__ . '/../config.php';

$project = trim($_GET['project'] ?? '');
$notes   = trim($_GET['notes']   ?? '');

if (!$project) respondError('project param required');

$db = getDB();

// Get project info
$stmt = $db->prepare('SELECT * FROM projects WHERE slug = ?');
$stmt->execute([$project]);
$proj = $stmt->fetch();
if (!$proj) respondError('Project not found', 404);

// Get test cases
$stmt = $db->prepare(
    'SELECT tc_id, title, description AS `desc`, priority, status, updated_at
     FROM test_cases WHERE project_id = ? ORDER BY id ASC'
);
$stmt->execute([$proj['id']]);
$cases = $stmt->fetchAll();

// Get run history
$stmt = $db->prepare(
    'SELECT run_id, total, pass, fail, skip, pending, created_at
     FROM test_runs WHERE project_id = ? ORDER BY id DESC LIMIT 20'
);
$stmt->execute([$proj['id']]);
$runs = $stmt->fetchAll();

// Build JSON payload for Python script
$payload = json_encode([
    'project' => $proj['name'],
    'color'   => $proj['color'],
    'notes'   => $notes,
    'cases'   => $cases,
    'runs'    => $runs,
]);

// Write payload to temp file
$tmpJson = tempnam(sys_get_temp_dir(), 'tf_report_') . '.json';
file_put_contents($tmpJson, $payload);

$outPdf = tempnam(sys_get_temp_dir(), 'tf_out_') . '.pdf';

// Run Python generator script
$scriptPath = __DIR__ . '/../scripts/generate_report.py';
$cmd = escapeshellcmd("python3 " . escapeshellarg($scriptPath))
     . ' ' . escapeshellarg($tmpJson)
     . ' ' . escapeshellarg($outPdf)
     . ' 2>&1';

$output = shell_exec($cmd);

// Cleanup json
@unlink($tmpJson);

if (!file_exists($outPdf) || filesize($outPdf) < 100) {
    @unlink($outPdf);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDF generation failed. Make sure Python3 and reportlab are installed on the server. Output: ' . $output]);
    exit();
}

// Stream PDF
$filename = 'TestReport-' . preg_replace('/[^a-zA-Z0-9-]/', '-', $proj['name']) . '-' . date('Y-m-d') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($outPdf));
header('Cache-Control: no-cache');
readfile($outPdf);
@unlink($outPdf);
exit();
