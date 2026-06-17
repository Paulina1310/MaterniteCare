<?php
// 1. Débogage et CORS
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Connexion à la BDD

require_once __DIR__ . '/../config/database.php';



$tableName = '"maternite_care"."GROSSESSE"'; // Nom de la table (avec guillemets pour les majuscules)
$primaryKey = '"ID_GROSSESSE"'; // Nom de la clé primaire
// Liste des colonnes pour le SELECT et le INSERT (adapte selon ton schéma)
$columns = '"ID_GROSSESSE", "ID_PATIENTE", "DATE_ACCOUCHEMENT_PREVUE", "NOMBRE_GROSSESSE", "TRIMESTRE", "OBSERVATION","STATUT_GROSSESSE","NIVEAU_RISQUE","NOMBRE_FOETUS"';

$columnsInsert = '"ID_GROSSESSE", "ID_PATIENTE", "DATE_ACCOUCHEMENT_PREVUE", "NOMBRE_GROSSESSE", "TRIMESTRE", "OBSERVATION","STATUT_GROSSESSE","NIVEAU_RISQUE","NOMBRE_FOETUS"'; 

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- GET : Récupérer tout ou un seul élément ---
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Récupérer UNE seule grossesse par son ID
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
                // Récupérer TOUTES les grossesses
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
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    // --- POST : Créer un nouvel élément ---
    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Vérification basique (adapte les champs obligatoires)
            if (empty($data['ID_PATIENTE']) || empty($data['DATE_ACCOUCHEMENT_PREVUE'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Les champs ID_PATIENTE et DATE_ACCOUCHEMENT_PREVUE sont obligatoires."]);
                exit;
            }

            $sql = "INSERT INTO $tableName ($columnsInsert) VALUES (:ID_GROSSESSE, :ID_PATIENTE, :DATE_ACCOUCHEMENT_PREVUE, :NOMBRE_GROSSESSE, :TRIMESTRE, :OBSERVATION, :STATUT_GROSSESSE, :NIVEAU_RISQUE, :NOMBRE_FOETUS) RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_GROSSESSE' => $data['ID_GROSSESSE'],
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':DATE_ACCOUCHEMENT_PREVUE' => $data['DATE_ACCOUCHEMENT_PREVUE'],
                ':NOMBRE_GROSSESSE' => $data['NOMBRE_GROSSESSE'],
                ':TRIMESTRE' => $data['TRIMESTRE'],
                ':OBSERVATION' => $data['OBSERVATION'],
                ':STATUT_GROSSESSE' => $data['STATUT_GROSSESSE'],
                ':NIVEAU_RISQUE' => $data['NIVEAU_RISQUE'],
                ':NOMBRE_FOETUS' => $data['NOMBRE_FOETUS']
            ]);
            
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Grossesse créée avec succès.", "id" => $newId]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    // --- PUT : Modifier un élément ---
    case 'PUT':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID manquant dans l'URL."]);
                exit;
            }

            $sql = "UPDATE $tableName SET \"ID_GROSSESSE\" = :ID_GROSSESSE, \"ID_PATIENTE\" = :ID_PATIENTE, \"DATE_ACCOUCHEMENT_PREVUE\" = :DATE_ACCOUCHEMENT_PREVUE, \"NOMBRE_GROSSESSE\" = :NOMBRE_GROSSESSE, \"TRIMESTRE\" = :TRIMESTRE, \"OBSERVATION\" = :OBSERVATION, \"STATUT_GROSSESSE\" = :STATUT_GROSSESSE, \"NIVEAU_RISQUE\" = :NIVEAU_RISQUE, \"NOMBRE_FOETUS\" = :NOMBRE_FOETUS WHERE $primaryKey = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_GROSSESSE' => $data['ID_GROSSESSE'],
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':DATE_ACCOUCHEMENT_PREVUE' => $data['DATE_ACCOUCHEMENT_PREVUE'],
                ':NOMBRE_GROSSESSE' => $data['NOMBRE_GROSSESSE'],
                ':TRIMESTRE' => $data['TRIMESTRE'],
                ':OBSERVATION' => $data['OBSERVATION'],
                ':STATUT_GROSSESSE' => $data['STATUT_GROSSESSE'],
                ':NIVEAU_RISQUE' => $data['NIVEAU_RISQUE'],
                ':NOMBRE_FOETUS' => $data['NOMBRE_FOETUS'],
                ':id'          => $id
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Grossesse mise à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Grossesse non trouvée."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    // --- DELETE : Supprimer un élément ---
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
                echo json_encode(["success" => true, "message" => "Grossesse supprimée."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Grossesse non trouvée."]);
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