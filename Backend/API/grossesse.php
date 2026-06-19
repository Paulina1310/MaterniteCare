<?php
// 1. Débogage et CORS
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handler OPTIONS (obligatoire pour CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Connexion à la BDD
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur connexion: " . $e->getMessage()]);
    exit();
}

// 3. Diagnostic : vérifier la connexion
try {
    $testStmt = $pdo->query("SELECT current_database(), current_schema()");
    $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
    // echo json_encode(["debug" => $testResult]); exit(); // Décommente pour voir la BDD active
} catch (Exception $e) {
    // Ignore les erreurs de diagnostic
}

// 4. Configuration des noms (SANS espaces, comme dans ta BDD)
$tableName = '"maternite_care"."GROSSESSE"';
$primaryKey = '"ID_GROSSESSE"';
$columns = '"ID_GROSSESSE", "ID_PATIENTE", "ID_TYPE_GROSSESSE", "DATE_DEBUT", "DATE_ACCOUCHEMENT_PREVUE", "NOMBRE_GROSSESSE", "TRIMESTRE", "OBSERVATION", "STATUT_GROSSESSE", "NIVEAU_RISQUE", "NOMBRE_FOETUS"';

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
                    echo json_encode(["success" => false, "message" => "Grossesse non trouvée."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $columns FROM $tableName ORDER BY $primaryKey DESC");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    "success" => true,
                    "data" => $results,
                    "count" => count($results)
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Erreur SQL: " . $e->getMessage(),
                "table_testee" => $tableName
            ]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['ID_PATIENTE']) || empty($data['DATE_ACCOUCHEMENT_PREVUE'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Champs obligatoires manquants."]);
                exit;
            }
            
            $sql = "INSERT INTO $tableName (\"ID_PATIENTE\", \"ID_TYPE_GROSSESSE\", \"DATE_DEBUT\", \"DATE_ACCOUCHEMENT_PREVUE\", \"NOMBRE_GROSSESSE\", \"TRIMESTRE\", \"OBSERVATION\", \"STATUT_GROSSESSE\", \"NIVEAU_RISQUE\", \"NOMBRE_FOETUS\") 
                    VALUES (:ID_PATIENTE, :ID_TYPE_GROSSESSE, :DATE_DEBUT, :DATE_ACCOUCHEMENT_PREVUE, :NOMBRE_GROSSESSE, :TRIMESTRE, :OBSERVATION, :STATUT_GROSSESSE, :NIVEAU_RISQUE, :NOMBRE_FOETUS) 
                    RETURNING $primaryKey";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_TYPE_GROSSESSE' => $data['ID_TYPE_GROSSESSE'] ?? 1,
                ':DATE_DEBUT' => $data['DATE_DEBUT'] ?? date('Y-m-d H:i:s'),
                ':DATE_ACCOUCHEMENT_PREVUE' => $data['DATE_ACCOUCHEMENT_PREVUE'],
                ':NOMBRE_GROSSESSE' => $data['NOMBRE_GROSSESSE'] ?? 1,
                ':TRIMESTRE' => $data['TRIMESTRE'] ?? null,
                ':OBSERVATION' => $data['OBSERVATION'] ?? null,
                ':STATUT_GROSSESSE' => $data['STATUT_GROSSESSE'] ?? 'En cours',
                ':NIVEAU_RISQUE' => $data['NIVEAU_RISQUE'] ?? 'Normal',
                ':NOMBRE_FOETUS' => $data['NOMBRE_FOETUS'] ?? 1
            ]);
            
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Grossesse créée.", "id" => $newId]);
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