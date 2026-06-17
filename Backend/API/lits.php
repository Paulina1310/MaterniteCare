<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';

$tableName = '"maternite_care"."LITS"';
$primaryKey = '"ID_LITS"';
$columns = '"ID_LITS", "NUMERO", "LOCALISATION", "ID_WORKSPACE", "STATUT"';
$columnsInsert = '"NUMERO", "LOCALISATION", "ID_WORKSPACE", "STATUT"';

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
                    echo json_encode(["success" => false, "message" => "Lit non trouvé."]);
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
            if (empty($data['NUMERO'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Le champ NUMERO est obligatoire."]);
                exit;
            }
            $sql = "INSERT INTO $tableName ($columnsInsert) VALUES (:NUMERO, :LOCALISATION, :ID_WORKSPACE, :STATUT) RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':NUMERO' => $data['NUMERO'],
                ':LOCALISATION' => isset($data['LOCALISATION']) ? $data['LOCALISATION'] : null,
                ':ID_WORKSPACE' => isset($data['ID_WORKSPACE']) ? $data['ID_WORKSPACE'] : null,
                ':STATUT' => isset($data['STATUT']) ? $data['STATUT'] : 'Libre'
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Lit créé avec succès.", "id" => $newId]);
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
            $sql = "UPDATE $tableName SET \"NUMERO\" = :NUMERO, \"LOCALISATION\" = :LOCALISATION, \"ID_WORKSPACE\" = :ID_WORKSPACE, \"STATUT\" = :STATUT WHERE $primaryKey = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':NUMERO' => $data['NUMERO'],
                ':LOCALISATION' => isset($data['LOCALISATION']) ? $data['LOCALISATION'] : null,
                ':ID_WORKSPACE' => isset($data['ID_WORKSPACE']) ? $data['ID_WORKSPACE'] : null,
                ':STATUT' => isset($data['STATUT']) ? $data['STATUT'] : 'Libre',
                ':id' => $id
            ]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Lit mis à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Lit non trouvé."]);
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
                echo json_encode(["success" => true, "message" => "Lit supprimé."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Lit non trouvé."]);
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