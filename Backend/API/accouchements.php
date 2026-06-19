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

$tableName = '"maternite_care"."ACCOUCHEMENT"';
$primaryKey = '"ID_ACCOUCHEMENT"';
$columns = '"ID_ACCOUCHEMENT", "ID_PATIENTE", "ID_PERSONNEL", "ID_WORKSPACE", "DATE_ACCOUCHEMENT", "TYPE_ACCOUCHEMENT", "RESULTAT", "POIDS_BEBE", "TAILLE_BEBE", "NOMBRE_FAUSSE_COUCHES"';

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
                    echo json_encode(["success" => false, "message" => "Accouchement non trouvé."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $columns FROM $tableName ORDER BY \"DATE_ACCOUCHEMENT\" DESC");
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
            if (empty($data['ID_PATIENTE']) || empty($data['DATE_ACCOUCHEMENT'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Champs obligatoires manquants."]);
                exit;
            }
            $sql = "INSERT INTO $tableName (\"ID_PATIENTE\", \"ID_PERSONNEL\", \"ID_WORKSPACE\", \"DATE_ACCOUCHEMENT\", \"TYPE_ACCOUCHEMENT\", \"RESULTAT\", \"POIDS_BEBE\", \"TAILLE_BEBE\", \"NOMBRE_FAUSSE_COUCHES\")
                    VALUES (:ID_PATIENTE, :ID_PERSONNEL, :ID_WORKSPACE, :DATE_ACCOUCHEMENT, :TYPE_ACCOUCHEMENT, :RESULTAT, :POIDS_BEBE, :TAILLE_BEBE, :NOMBRE_FAUSSE_COUCHES)
                    RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_PERSONNEL' => isset($data['ID_PERSONNEL']) ? $data['ID_PERSONNEL'] : null,
                ':ID_WORKSPACE' => isset($data['ID_WORKSPACE']) ? $data['ID_WORKSPACE'] : null,
                ':DATE_ACCOUCHEMENT' => $data['DATE_ACCOUCHEMENT'],
                ':TYPE_ACCOUCHEMENT' => isset($data['TYPE_ACCOUCHEMENT']) ? $data['TYPE_ACCOUCHEMENT'] : 'Voie basse',
                ':RESULTAT' => isset($data['RESULTAT']) ? $data['RESULTAT'] : null,
                ':POIDS_BEBE' => isset($data['POIDS_BEBE']) ? $data['POIDS_BEBE'] : null,
                ':TAILLE_BEBE' => isset($data['TAILLE_BEBE']) ? $data['TAILLE_BEBE'] : null,
                ':NOMBRE_FAUSSE_COUCHES' => isset($data['NOMBRE_FAUSSE_COUCHES']) ? $data['NOMBRE_FAUSSE_COUCHES'] : 0
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Accouchement créé.", "id" => $newId]);
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
