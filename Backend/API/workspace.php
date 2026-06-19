<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur connexion: " . $e->getMessage()]);
    exit();
}

$tableName = '"maternite_care"."WORKSPACE"';
$primaryKey = '"ID_WORKSPACE"';
$columns = '"ID_WORKSPACE", "NOM", "NUMERO", "CAPACITE", "STATUT_WORKSPACE", "ID_TYPE_WORKSPACE"';

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
                    echo json_encode(["success" => false, "message" => "Workspace non trouvé."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $columns FROM $tableName ORDER BY $primaryKey");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "data" => $results, "count" => count($results)]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (empty($data['NOM']) || empty($data['NUMERO'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Champs obligatoires manquants."]);
                exit;
            }
            $sql = "INSERT INTO $tableName (\"NOM\", \"NUMERO\", \"CAPACITE\", \"STATUT_WORKSPACE\", \"ID_TYPE_WORKSPACE\")
                    VALUES (:NOM, :NUMERO, :CAPACITE, :STATUT_WORKSPACE, :ID_TYPE_WORKSPACE)
                    RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':NOM' => $data['NOM'],
                ':NUMERO' => $data['NUMERO'],
                ':CAPACITE' => isset($data['CAPACITE']) ? $data['CAPACITE'] : 10,
                ':STATUT_WORKSPACE' => isset($data['STATUT_WORKSPACE']) ? $data['STATUT_WORKSPACE'] : 'Disponible',
                ':ID_TYPE_WORKSPACE' => isset($data['ID_TYPE_WORKSPACE']) ? $data['ID_TYPE_WORKSPACE'] : 1
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Workspace créé.", "id" => $newId]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
}
?>
