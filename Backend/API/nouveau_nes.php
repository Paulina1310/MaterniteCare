<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';

$tableName = '"maternite_care"."NOUVEAU_NES"';
$primaryKey = '"ID_NOUVEAU_NES"';
$columns = '"ID_NOUVEAU_NES", "ID_PATIENTE", "ID_ACCOUCHEMENT", "NOM", "PRENOM", "DATE_NAISSANCE", "SEXE", "TAILLE", "POIDS", "SCORE_APGAR_1MIN", "SCORE_APGAR_5MIN", "FACTEUR_RHESUS", "OBSERVATION"';
$columnsInsert = '"ID_PATIENTE", "ID_ACCOUCHEMENT", "NOM", "PRENOM", "DATE_NAISSANCE", "SEXE", "TAILLE", "POIDS", "SCORE_APGAR_1MIN", "SCORE_APGAR_5MIN", "FACTEUR_RHESUS", "OBSERVATION"';

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
                    echo json_encode(["success" => false, "message" => "Nouveau-né non trouvé."]);
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
            if (empty($data['ID_PATIENTE']) || empty($data['ID_ACCOUCHEMENT']) || empty($data['NOM']) || empty($data['PRENOM']) || empty($data['FACTEUR_RHESUS'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Les champs ID_PATIENTE, ID_ACCOUCHEMENT, NOM, PRENOM et FACTEUR_RHESUS sont obligatoires."]);
                exit;
            }
            $sql = "INSERT INTO $tableName ($columnsInsert) VALUES (:ID_PATIENTE, :ID_ACCOUCHEMENT, :NOM, :PRENOM, :DATE_NAISSANCE, :SEXE, :TAILLE, :POIDS, :SCORE_APGAR_1MIN, :SCORE_APGAR_5MIN, :FACTEUR_RHESUS, :OBSERVATION) RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_ACCOUCHEMENT' => $data['ID_ACCOUCHEMENT'],
                ':NOM' => $data['NOM'],
                ':PRENOM' => $data['PRENOM'],
                ':DATE_NAISSANCE' => isset($data['DATE_NAISSANCE']) ? $data['DATE_NAISSANCE'] : date('Y-m-d H:i:s'),
                ':SEXE' => isset($data['SEXE']) ? $data['SEXE'] : null,
                ':TAILLE' => isset($data['TAILLE']) ? $data['TAILLE'] : null,
                ':POIDS' => isset($data['POIDS']) ? $data['POIDS'] : null,
                ':SCORE_APGAR_1MIN' => isset($data['SCORE_APGAR_1MIN']) ? $data['SCORE_APGAR_1MIN'] : null,
                ':SCORE_APGAR_5MIN' => isset($data['SCORE_APGAR_5MIN']) ? $data['SCORE_APGAR_5MIN'] : null,
                ':FACTEUR_RHESUS' => $data['FACTEUR_RHESUS'],
                ':OBSERVATION' => isset($data['OBSERVATION']) ? $data['OBSERVATION'] : null
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Nouveau-né créé avec succès.", "id" => $newId]);
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
            $sql = "UPDATE $tableName SET \"ID_PATIENTE\" = :ID_PATIENTE, \"ID_ACCOUCHEMENT\" = :ID_ACCOUCHEMENT, \"NOM\" = :NOM, \"PRENOM\" = :PRENOM, \"DATE_NAISSANCE\" = :DATE_NAISSANCE, \"SEXE\" = :SEXE, \"TAILLE\" = :TAILLE, \"POIDS\" = :POIDS, \"SCORE_APGAR_1MIN\" = :SCORE_APGAR_1MIN, \"SCORE_APGAR_5MIN\" = :SCORE_APGAR_5MIN, \"FACTEUR_RHESUS\" = :FACTEUR_RHESUS, \"OBSERVATION\" = :OBSERVATION WHERE $primaryKey = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_ACCOUCHEMENT' => $data['ID_ACCOUCHEMENT'],
                ':NOM' => $data['NOM'],
                ':PRENOM' => $data['PRENOM'],
                ':DATE_NAISSANCE' => isset($data['DATE_NAISSANCE']) ? $data['DATE_NAISSANCE'] : null,
                ':SEXE' => isset($data['SEXE']) ? $data['SEXE'] : null,
                ':TAILLE' => isset($data['TAILLE']) ? $data['TAILLE'] : null,
                ':POIDS' => isset($data['POIDS']) ? $data['POIDS'] : null,
                ':SCORE_APGAR_1MIN' => isset($data['SCORE_APGAR_1MIN']) ? $data['SCORE_APGAR_1MIN'] : null,
                ':SCORE_APGAR_5MIN' => isset($data['SCORE_APGAR_5MIN']) ? $data['SCORE_APGAR_5MIN'] : null,
                ':FACTEUR_RHESUS' => $data['FACTEUR_RHESUS'],
                ':OBSERVATION' => isset($data['OBSERVATION']) ? $data['OBSERVATION'] : null,
                ':id' => $id
            ]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Nouveau-né mis à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Nouveau-né non trouvé."]);
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
                echo json_encode(["success" => true, "message" => "Nouveau-né supprimé."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Nouveau-né non trouvé."]);
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