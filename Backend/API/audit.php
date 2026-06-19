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

$tableName = '"maternite_care"."AUDIT"';
$primaryKey = '"ID_AUDIT"';
$columns = '"ID_AUDIT", "ID_PERSONNEL", "DATE_ACTION", "ACTION_AUDIT", "MODULE", "TABLE_CONCERNEE"';

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
                    echo json_encode(["success" => false, "message" => "Audit non trouvé."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $columns FROM $tableName ORDER BY \"DATE_ACTION\" DESC LIMIT 100");
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
            if (empty($data['ID_PERSONNEL']) || empty($data['ACTION_AUDIT'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Champs obligatoires manquants."]);
                exit;
            }
            $sql = "INSERT INTO $tableName (\"ID_PERSONNEL\", \"DATE_ACTION\", \"ACTION_AUDIT\", \"MODULE\", \"TABLE_CONCERNEE\")
                    VALUES (:ID_PERSONNEL, CURRENT_TIMESTAMP, :ACTION_AUDIT, :MODULE, :TABLE_CONCERNEE)
                    RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PERSONNEL' => $data['ID_PERSONNEL'],
                ':ACTION_AUDIT' => $data['ACTION_AUDIT'],
                ':MODULE' => isset($data['MODULE']) ? $data['MODULE'] : null,
                ':TABLE_CONCERNEE' => isset($data['TABLE_CONCERNEE']) ? $data['TABLE_CONCERNEE'] : null
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Audit créé.", "id" => $newId]);
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
