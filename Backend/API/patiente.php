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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- GET : Récupérer les PATIENTEs ---
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare('SELECT * FROM "maternite_care"."PATIENTE" WHERE "ID_PATIENTE" = :id');
                $stmt->execute([':id' => $_GET['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo json_encode(["success" => true, "data" => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(["success" => false, "message" => "PATIENTE non trouvée."]);
                }
            } else {
                $stmt = $pdo->query('SELECT * FROM "maternite_care"."PATIENTE" ORDER BY "ID_PATIENTE" DESC');
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

    // --- POST : Créer une PATIENTE ---
    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Vérification des champs obligatoires (NOT NULL)
            $required = ['ID_PATIENTE', 'ID_PERSONNE', 'CODE_SUIVI', 'MATRICULE', 'GROUPE_SANGUIN', 'FACTEUR_RHESUS', 'ANTECEDENT_MED', 'ANTECEDENT_FAMILIAL','ALLERGIE'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "Le champ '$field' est obligatoire."]);
                    exit;
                }
            }

            $sql = 'INSERT INTO "maternite_care"."PATIENTE" 
                    ("ID_PATIENTE", "ID_PERSONNE", "CODE_SUIVI", "MATRICULE", "GROUPE_SANGUIN", "FACTEUR_RHESUS", "ANTECEDENT_MED", "ANTECEDENT_FAMILIAL", "ALLERGIE") 
                    VALUES (:ID_PATIENTE, :ID_PERSONNE, :CODE_SUIVI, :MATRICULE, :GROUPE_SANGUIN, :FACTEUR_RHESUS, :ANTECEDENT_MED, :ANTECEDENT_FAMILIAL, :ALLERGIE) 
                    RETURNING "ID_PATIENTE"';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_PERSONNE' => $data['ID_PERSONNE'],
                ':CODE_SUIVI' => $data['CODE_SUIVI'],
                ':MATRICULE' => $data['MATRICULE'],
                ':GROUPE_SANGUIN' => $data['GROUPE_SANGUIN'],
                ':FACTEUR_RHESUS' => $data['FACTEUR_RHESUS'],
                ':ANTECEDENT_MED' => $data['ANTECEDENT_MED'],
                ':ANTECEDENT_FAMILIAL' => $data['ANTECEDENT_FAMILIAL'],
                ':ALLERGIE' => $data['ALLERGIE']
            ]);
            
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "PATIENTE créée avec succès.", "id" => $newId]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    // --- PUT : Modifier une PATIENTE ---
    case 'PUT':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID manquant dans l'URL (ex: ?id=1)."]);
                exit;
            }

            $sql = 'UPDATE "maternite_care"."PATIENTE" 
                    SET "ID_PATIENTE" = :ID_PATIENTE, 
                        "ID_TYPE_PATIENTE" = :ID_TYPE_PATIENTE, 
                        "DATE_DEBUT" = :DATE_DEBUT, 
                        "DATE_ACCOUCHEMENT" = :DATE_ACCOUCHEMENT, 
                        "NOMBRE_PATIENTE" = :NOMBRE_PATIENTE, 
                        "TRIMESTRE" = :TRIMESTRE, 
                        "OBSERVATION" = :OBSERVATION, 
                        "STATUT_PATIENTE" = :STATUT_PATIENTE, 
                        "NIVEAU_RISQUE" = :NIVEAU_RISQUE, 
                        "NOMBRE_FOETUS" = :NOMBRE_FOETUS 
                    WHERE "ID_PATIENTE" = :id';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ID_PATIENTE' => $data['ID_PATIENTE'],
                ':ID_TYPE_PATIENTE' => $data['ID_TYPE_PATIENTE'],
                ':DATE_DEBUT' => $data['DATE_DEBUT'],
                ':DATE_ACCOUCHEMENT' => $data['DATE_ACCOUCHEMENT'],
                ':NOMBRE_PATIENTE' => $data['NOMBRE_PATIENTE'],
                ':TRIMESTRE' => isset($data['TRIMESTRE']) ? $data['TRIMESTRE'] : null,
                ':OBSERVATION' => isset($data['OBSERVATION']) ? $data['OBSERVATION'] : null,
                ':STATUT_PATIENTE' => $data['STATUT_PATIENTE'],
                ':NIVEAU_RISQUE' => $data['NIVEAU_RISQUE'],
                ':NOMBRE_FOETUS' => $data['NOMBRE_FOETUS'],
                ':id' => $id
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "PATIENTE mise à jour."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "PATIENTE non trouvée."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erreur SQL: " . $e->getMessage()]);
        }
        break;

    // --- DELETE : Supprimer une PATIENTE ---
    case 'DELETE':
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID manquant."]);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM "maternite_care"."PATIENTE" WHERE "ID_PATIENTE" = :id');
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "PATIENTE supprimée."]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "PATIENTE non trouvée."]);
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