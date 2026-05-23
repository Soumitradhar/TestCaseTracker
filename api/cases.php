<?php
// ============================================================
//  api/cases.php  —  Test Cases CRUD API
//
//  GET    ?project=neo-nature          → list all cases
//  POST   {project, title, desc, priority}  → create
//  PUT    {id, title, desc, priority, status} → update
//  DELETE ?id=5                         → delete
// ============================================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── Helper: get project_id from slug ──────────────────────
function getProjectId(PDO $db, string $slug): int {
    $stmt = $db->prepare('SELECT id FROM projects WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) respondError('Project not found', 404);
    return (int)$row['id'];
}

// ── Helper: generate next TC-xxx id ───────────────────────
function nextTcId(PDO $db, int $projectId): string {
    $stmt = $db->prepare(
        'SELECT tc_id FROM test_cases WHERE project_id = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$projectId]);
    $row = $stmt->fetch();
    if (!$row) return 'TC-001';
    $num = (int)substr($row['tc_id'], 3) + 1;
    return 'TC-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

switch ($method) {

    // ── LIST ──────────────────────────────────────────────
    case 'GET':
        $slug = $_GET['project'] ?? '';
        if (!$slug) respondError('project param required');
        $pid = getProjectId($db, $slug);
        $stmt = $db->prepare(
            'SELECT id, tc_id, title, description AS `desc`, priority, status,
                    created_at, updated_at
             FROM test_cases
             WHERE project_id = ?
             ORDER BY id ASC'
        );
        $stmt->execute([$pid]);
        respond($stmt->fetchAll());
        break;

    // ── CREATE ────────────────────────────────────────────
    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $slug     = trim($body['project'] ?? '');
        $title    = trim($body['title']   ?? '');
        $desc     = trim($body['desc']    ?? '');
        $priority = $body['priority'] ?? 'Medium';

        if (!$slug || !$title) respondError('project and title are required');
        if (!in_array($priority, ['High','Medium','Low'])) $priority = 'Medium';

        $pid  = getProjectId($db, $slug);
        $tcId = nextTcId($db, $pid);

        $stmt = $db->prepare(
            'INSERT INTO test_cases (tc_id, project_id, title, description, priority, status)
             VALUES (?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$tcId, $pid, $title, $desc, $priority]);
        $newId = (int)$db->lastInsertId();

        $stmt = $db->prepare('SELECT id, tc_id, title, description AS `desc`, priority, status, created_at FROM test_cases WHERE id = ?');
        $stmt->execute([$newId]);
        respond($stmt->fetch(), 201);
        break;

    // ── UPDATE ────────────────────────────────────────────
    case 'PUT':
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($body['id'] ?? 0);
        if (!$id) respondError('id required');

        $fields = [];
        $params = [];

        if (isset($body['title'])) {
            $fields[] = 'title = ?';
            $params[]  = trim($body['title']);
        }
        if (isset($body['desc'])) {
            $fields[] = 'description = ?';
            $params[]  = trim($body['desc']);
        }
        if (isset($body['priority']) && in_array($body['priority'], ['High','Medium','Low'])) {
            $fields[] = 'priority = ?';
            $params[]  = $body['priority'];
        }
        if (isset($body['status']) && in_array($body['status'], ['pending','pass','fail','skip'])) {
            $fields[] = 'status = ?';
            $params[]  = $body['status'];
        }

        if (!$fields) respondError('Nothing to update');

        $params[] = $id;
        $db->prepare('UPDATE test_cases SET ' . implode(', ', $fields) . ' WHERE id = ?')
           ->execute($params);

        $stmt = $db->prepare('SELECT id, tc_id, title, description AS `desc`, priority, status, updated_at FROM test_cases WHERE id = ?');
        $stmt->execute([$id]);
        respond($stmt->fetch());
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respondError('id required');
        $db->prepare('DELETE FROM test_cases WHERE id = ?')->execute([$id]);
        respond(['deleted' => true, 'id' => $id]);
        break;

    default:
        respondError('Method not allowed', 405);
}
