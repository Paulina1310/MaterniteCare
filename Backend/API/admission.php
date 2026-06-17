<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';

$tableName = '"maternite_care"."ADMISSION"';
$primaryKey = '"ID_ADMISSION"';
$columns = '"ID_ADMISSION", "ID_PATIENTE", "ID_GROSSESSE", "ID_PERSONNEL", "ID_WORKSPACE", "ID_LITS", "DATE_ADMISSION", "DATE_SORTIE", "MOTIF", "SERVICE", "NIVEAU_URGENCE", "STATUT_ADMISSION"';
$columnsInsert = '"ID_PATIENTE", "ID_GROSSESSE", "ID_PERSONNEL", "ID_WORKSPACE", "ID_LITS", "DATE_ADMISSION", "MOTIF", "SERVICE", "NIVEAU_URGENCE", "STATUT_ADMISSION"';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT $columns FROM $tableName WHERE $primaryKey = :id");
                $stmt->execute([':id' => $_GET['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    echo json_encode(["success" => true, "data" => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(["success" => false, "message" => "Admission non trouvée."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $columns FROM $tableName ORDER BY $primaryKey DESC");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "data" => $results, "count" => count($results)], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (empty($data['ID_PATIENTE']) || empty($data['ID_GROSSESSE']) || empty($data['ID_PERSONNEL']) || empty($data['ID_WORKSPACE']) || empty($data['ID_LITS']) || empty($data['MOTIF']) || empty($data['SERVICE'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Les champs ID_PATIENTE, ID_GROSSESSE, ID_PERSONNEL, ID_WORKSPACE, ID_LITS, MOTIF et SERVICE sont obligatoires."]);
                exit;
            }
            $sql = "INSERT INTO $tableName ($columnsInsert) VALUES (:ID_PATIENTE, :ID_GROSSESSE, :ID_PERSONNEL, :ID_WORKSPACE, :ID_LITS, :DATE_ADMISSION, :MOTIF, :SERVICE, :NIVEAU_URGENCE, :STATUT_ADMISSION) RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_GROSSESSE' => $data['ID_GROSSESSE'],
                ':ID_PERSONNEL' => $data['ID_PERSONNEL'],
                ':ID_WORKSPACE' => $data['ID_WORKSPACE'],
                ':ID_LITS' => $data['ID_LITS'],
                ':DATE_ADMISSION' => isset($data['DATE_ADMISSION']) ? $data['DATE_ADMISSION'] : date('Y-m-d H:i:s'),
                ':MOTIF' => $data['MOTIF'],
                ':SERVICE' => $data['SERVICE'],
                ':NIVEAU_URGENCE' => isset($data['NIVEAU_URGENCE']) ? $data['NIVEAU_URGENCE'] : 'Normal',
                ':STATUT_ADMISSION' => isset($data['STATUT_ADMISSION']) ? $data['STATUT_ADMISSION'] : 'Actif'
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Admission créée avec succès.", "id" => $newId]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID manquant."]);
                exit;
            }
            $sql = "UPDATE $tableName SET \"ID_PATIENTE\" = :ID_PATIENTE, \"ID_GROSSESSE\" = :ID_GROSSESSE, \"ID_PERSONNEL\" = :ID_PERSONNEL, \"ID_WORKSPACE\" = :ID_WORKSPACE, \"ID_LITS\" = :ID_LITS, \"DATE_SORTIE\" = :DATE_SORTIE, \"MOTIF\" = :MOTIF, \"SERVICE\" = :SERVICE, \"NIVEAU_URGENCE\" = :NIVEAU_URGENCE, \"STATUT_ADMISSION\" = :STATUT_ADMISSION WHERE $primaryKey = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_GROSSESSE' => $data['ID_GROSSESSE'],
                ':ID_PERSONNEL' => $data['ID_PERSONNEL'],
                ':ID_WORKSPACE' => $data['ID_WORKSPACE'],
                ':ID_LITS' => $data['ID_LITS'],
                ':DATE_SORTIE' => isset($data['DATE_SORTIE']) ? $data['DATE_SORTIE'] : null,
                ':MOTIF' => $data['MOTIF'],
                ':SERVICE' => $data['SERVICE'],
                ':NIVEAU_URGENCE' => isset($data['NIVEAU_URGENCE']) ? $data['NIVEAU_URGENCE'] : 'Normal',
                ':STATUT_ADMISSION' => isset($data['STATUT_ADMISSION']) ? $data['STATUT_ADMISSION'] : 'Actif',
                ':id' => $id
            ]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Admission mise à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Admission non trouvée."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID manquant."]);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM $tableName WHERE $primaryKey = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Admission supprimée."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Admission non trouvée."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
        break;
}
?>