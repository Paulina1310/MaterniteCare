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



$tableName = '"maternite_care"."PERSONNE"'; // Nom de la table (avec guillemets pour les majuscules)
$primaryKey = '"ID_PERSONNE"'; // Nom de la clé primaire
// Liste des colonnes pour le SELECT et le INSERT (adapte selon ton schéma)
$columns = '"ID_PERSONNE", "NOM", "PRENOM", "DATE_NAISSANCE", "SEXE", "TEL", "ADRESSE", "NATIONALITE", "PAYS_D_ORIGINE", "EMAIL", "PROFESSION", "NOMBRE_ENFANT", "NOM_CONJOINT", "PRENOM_CONJOINT", "AGE_CONJOINT", "PROFESSION_CONJOINT", "SITUATION_MATRIMONIALE"';

$columnsInsert = '"NOM", "PRENOM", "DATE_NAISSANCE", "SEXE", "TEL", "ADRESSE", "NATIONALITE", "PAYS_D_ORIGINE", "EMAIL", "PROFESSION", "NOMBRE_ENFANT", "NOM_CONJOINT", "PRENOM_CONJOINT", "AGE_CONJOINT", "PROFESSION_CONJOINT", "SITUATION_MATRIMONIALE"'; 

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- GET : Récupérer tout ou un seul élément ---
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Récupérer UNE seule personne par son ID
                $stmt = $pdo->prepare("SELECT $columns FROM $tableName WHERE $primaryKey = :id");
                $stmt->execute([':id' => $_GET['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo json_encode(["success" => true, "data" => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(["success" => false, "message" => "Personne non trouvée."]);
                }
            } else {
                // Récupérer TOUTES les personnes
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
            if (empty($data['NOM']) || empty($data['PRENOM']) || empty($data['DATE_NAISSANCE']) || empty($data['ADRESSE']) || empty($data['NATIONALITE']) || empty($data['PAYS_D_ORIGINE']) || empty($data['EMAIL'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Les champs NOM, PRENOM, DATE_NAISSANCE, ADRESSE, NATIONALITE, PAYS_D_ORIGINE et EMAIL sont obligatoires."]);
                exit;
            }

            $sql = "INSERT INTO $tableName ($columnsInsert) VALUES (:NOM, :PRENOM, :DATE_NAISSANCE, :SEXE, :TEL, :ADRESSE, :NATIONALITE, :PAYS_D_ORIGINE, :EMAIL, :PROFESSION, :NOMBRE_ENFANT, :NOM_CONJOINT, :PRENOM_CONJOINT, :AGE_CONJOINT, :PROFESSION_CONJOINT, :SITUATION_MATRIMONIALE) RETURNING $primaryKey";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':NOM' => $data['NOM'],
                ':PRENOM' => $data['PRENOM'],
                ':DATE_NAISSANCE' => $data['DATE_NAISSANCE'],
                ':SEXE' => isset($data['SEXE']) ? $data['SEXE'] : 'Feminin',
                ':TEL' => isset($data['TEL']) ? $data['TEL'] : null,
                ':ADRESSE' => $data['ADRESSE'],
                ':NATIONALITE' => $data['NATIONALITE'],
                ':PAYS_D_ORIGINE' => $data['PAYS_D_ORIGINE'],
                ':EMAIL' => $data['EMAIL'],
                ':PROFESSION' => isset($data['PROFESSION']) ? $data['PROFESSION'] : null,
                ':NOMBRE_ENFANT' => isset($data['NOMBRE_ENFANT']) ? $data['NOMBRE_ENFANT'] : null,
                ':NOM_CONJOINT' => isset($data['NOM_CONJOINT']) ? $data['NOM_CONJOINT'] : null,
                ':PRENOM_CONJOINT' => isset($data['PRENOM_CONJOINT']) ? $data['PRENOM_CONJOINT'] : null,
                ':AGE_CONJOINT' => isset($data['AGE_CONJOINT']) ? $data['AGE_CONJOINT'] : null,
                ':PROFESSION_CONJOINT' => isset($data['PROFESSION_CONJOINT']) ? $data['PROFESSION_CONJOINT'] : null,
                ':SITUATION_MATRIMONIALE' => isset($data['SITUATION_MATRIMONIALE']) ? $data['SITUATION_MATRIMONIALE'] : 'Célibataire'
            ]);
            
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Personne créée avec succès.", "id" => $newId]);
            
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

            $sql = "UPDATE $tableName SET \"NOM\" = :NOM, \"PRENOM\" = :PRENOM, \"DATE_NAISSANCE\" = :DATE_NAISSANCE, \"SEXE\" = :SEXE, \"TEL\" = :TEL, \"ADRESSE\" = :ADRESSE, \"NATIONALITE\" = :NATIONALITE, \"PAYS_D_ORIGINE\" = :PAYS_D_ORIGINE, \"EMAIL\" = :EMAIL, \"PROFESSION\" = :PROFESSION, \"NOMBRE_ENFANT\" = :NOMBRE_ENFANT, \"NOM_CONJOINT\" = :NOM_CONJOINT, \"PRENOM_CONJOINT\" = :PRENOM_CONJOINT, \"AGE_CONJOINT\" = :AGE_CONJOINT, \"PROFESSION_CONJOINT\" = :PROFESSION_CONJOINT, \"SITUATION_MATRIMONIALE\" = :SITUATION_MATRIMONIALE WHERE $primaryKey = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':NOM' => $data['NOM'],
                ':PRENOM' => $data['PRENOM'],
                ':DATE_NAISSANCE' => $data['DATE_NAISSANCE'],
                ':SEXE' => isset($data['SEXE']) ? $data['SEXE'] : 'Feminin',
                ':TEL' => isset($data['TEL']) ? $data['TEL'] : null,
                ':ADRESSE' => $data['ADRESSE'],
                ':NATIONALITE' => $data['NATIONALITE'],
                ':PAYS_D_ORIGINE' => $data['PAYS_D_ORIGINE'],
                ':EMAIL' => $data['EMAIL'],
                ':PROFESSION' => isset($data['PROFESSION']) ? $data['PROFESSION'] : null,
                ':NOMBRE_ENFANT' => isset($data['NOMBRE_ENFANT']) ? $data['NOMBRE_ENFANT'] : null,
                ':NOM_CONJOINT' => isset($data['NOM_CONJOINT']) ? $data['NOM_CONJOINT'] : null,
                ':PRENOM_CONJOINT' => isset($data['PRENOM_CONJOINT']) ? $data['PRENOM_CONJOINT'] : null,
                ':AGE_CONJOINT' => isset($data['AGE_CONJOINT']) ? $data['AGE_CONJOINT'] : null,
                ':PROFESSION_CONJOINT' => isset($data['PROFESSION_CONJOINT']) ? $data['PROFESSION_CONJOINT'] : null,
                ':SITUATION_MATRIMONIALE' => isset($data['SITUATION_MATRIMONIALE']) ? $data['SITUATION_MATRIMONIALE'] : 'Célibataire',
                ':id'          => $id
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Personne mise à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Personne non trouvée."]);
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
                echo json_encode(["success" => true, "message" => "Personne supprimée."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Personne non trouvée."]);
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