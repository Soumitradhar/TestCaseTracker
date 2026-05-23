<?php
// ============================================================
//  api/runs.php  —  Test Runs API
//
//  GET  ?project=neo-nature   → list all runs for project
//  POST {project, total, pass, fail, skip, pending, snapshot} → save run
// ============================================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

function getProjectId(PDO $db, string $slug): int {
    $stmt = $db->prepare('SELECT id FROM projects WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) respondError('Project not found', 404);
    return (int)$row['id'];
}

function nextRunId(PDO $db, int $projectId): string {
    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM test_runs WHERE project_id = ?');
    $stmt->execute([$projectId]);
    $cnt = (int)$stmt->fetchColumn();
    return 'RUN-' . str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);
}

switch ($method) {

    case 'GET':
        $slug = $_GET['project'] ?? '';
        if (!$slug) respondError('project param required');
        $pid = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'SELECT id, run_id, total, pass, fail, skip, pending, snapshot, created_at
             FROM test_runs
             WHERE project_id = ?
             ORDER BY id DESC'
        );
        $stmt->execute([$pid]);
        $rows = $stmt->fetchAll();
        // decode snapshot JSON string back to array
        foreach ($rows as &$row) {
            $row['snapshot'] = json_decode($row['snapshot'] ?? '[]', true);
        }
        respond($rows);
        break;

    case 'POST':
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $slug    = trim($body['project'] ?? '');
        if (!$slug) respondError('project required');

        $pid     = getProjectId($db, $slug);
        $runId   = nextRunId($db, $pid);
        $snapshot = json_encode($body['snapshot'] ?? []);

        $stmt = $db->prepare(
            'INSERT INTO test_runs (run_id, project_id, total, pass, fail, skip, pending, snapshot)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $runId, $pid,
            (int)($body['total']   ?? 0),
            (int)($body['pass']    ?? 0),
            (int)($body['fail']    ?? 0),
            (int)($body['skip']    ?? 0),
            (int)($body['pending'] ?? 0),
            $snapshot,
        ]);
        $newId = (int)$db->lastInsertId();
        $stmt  = $db->prepare('SELECT id, run_id, total, pass, fail, skip, pending, snapshot, created_at FROM test_runs WHERE id = ?');
        $stmt->execute([$newId]);
        $row = $stmt->fetch();
        $row['snapshot'] = json_decode($row['snapshot'], true);
        respond($row, 201);
        break;

    default:
        respondError('Method not allowed', 405);
}
