<?php
// 1. Débogage et CORS
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gérer les requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Connexion à la BDD
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- POST : Connexion (Login) ---
    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Vérification des champs obligatoires
            if (empty($data['EMAIL']) || empty($data['MOT_DE_PASSE'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Email et mot de passe obligatoires."
                ]);
                exit;
            }
            
            $email = trim($data['EMAIL']);
            $password = $data['MOT_DE_PASSE'];
            
            // 1. Chercher l'utilisateur dans la table UTILISATEUR
            $stmt = $pdo->prepare('
                SELECT u."ID_UTILISATEUR", u."ID_PERSONNE", u."EMAIL", u."MOT_DE_PASSE", 
                       u."TYPE_COMPTE", u."ACTIF"
                FROM "maternite_care"."UTILISATEUR" u
                WHERE u."EMAIL" = :email
            ');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si l'utilisateur existe et est actif
            if (!$user || !$user['ACTIF']) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Email ou mot de passe incorrect."
                ]);
                exit;
            }
            
            // 2. Vérifier le mot de passe hashé
            if (!password_verify($password, $user['MOT_DE_PASSE'])) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Email ou mot de passe incorrect."
                ]);
                exit;
            }
            
            // 3. Récupérer les informations complémentaires selon le type de compte
            $profil = null;
            
            if ($user['TYPE_COMPTE'] === 'PERSONNEL') {
                // Récupérer les infos du personnel
                $stmtProfil = $pdo->prepare('
                    SELECT p."NOM", p."PRENOM", per."MATRICULE", per."SPECIALITE", 
                           r."LIBELLE" AS "ROLE", r."NIVEAU_ACCES"
                    FROM "maternite_care"."PERSONNE" p
                    JOIN "maternite_care"."PERSONNEL" per ON p."ID_PERSONNE" = per."ID_PERSONNE"
                    JOIN "maternite_care"."ROLE_PERSONNEL" r ON per."ID_ROLE" = r."ID_ROLE"
                    WHERE p."ID_PERSONNE" = :id
                ');
                $stmtProfil->execute([':id' => $user['ID_PERSONNE']]);
                $profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);
            } else if ($user['TYPE_COMPTE'] === 'PATIENT') {
                // Récupérer les infos de la patiente
                $stmtProfil = $pdo->prepare('
                    SELECT p."NOM", p."PRENOM", pat."CODE_SUIVI", pat."MATRICULE", 
                           pat."GROUPE_SANGUIN", pat."FACTEUR_RHESUS"
                    FROM "maternite_care"."PERSONNE" p
                    JOIN "maternite_care"."PATIENTE" pat ON p."ID_PERSONNE" = pat."ID_PERSONNE"
                    WHERE p."ID_PERSONNE" = :id
                ');
                $stmtProfil->execute([':id' => $user['ID_PERSONNE']]);
                $profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);
            }
            
            // 4. Mettre à jour la dernière connexion
            $stmtUpdate = $pdo->prepare('
                UPDATE "maternite_care"."UTILISATEUR" 
                SET "DERNIERE_CONNEXION" = CURRENT_TIMESTAMP 
                WHERE "ID_UTILISATEUR" = :id
            ');
            $stmtUpdate->execute([':id' => $user['ID_UTILISATEUR']]);
            
            // 5. Générer un token de session (en production, utilise JWT)
            $token = bin2hex(random_bytes(32));
            
            // 6. Réponse de succès
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Connexion réussie.",
                "token" => $token,
                "user" => [
                    "id_utilisateur" => $user['ID_UTILISATEUR'],
                    "id_personne" => $user['ID_PERSONNE'],
                    "email" => $user['EMAIL'],
                    "type_compte" => $user['TYPE_COMPTE'],
                    "profil" => $profil
                ]
            ], JSON_PRETTY_PRINT);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur SQL: " . $e->getMessage()
            ]);
        }
        break;

    // --- GET : Vérification du token ou récupération du profil ---
    case 'GET':
        try {
            // Récupérer le token depuis les headers
            $headers = getallheaders();
            $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
            
            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Token manquant. Veuillez vous connecter."
                ]);
                exit;
            }
            
            // En production, vérifie le token dans une table de sessions ou JWT
            // Pour l'instant, on retourne juste une confirmation
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Token valide.",
                "token" => $token
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur: " . $e->getMessage()
            ]);
        }
        break;

    // --- DELETE : Déconnexion (Logout) ---
    case 'DELETE':
        try {
            $headers = getallheaders();
            $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
            
            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Token manquant."
                ]);
                exit;
            }
            
            // En production, invalider le token dans la base de données
            // Pour l'instant, on retourne juste une confirmation
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Déconnexion réussie."
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur: " . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Méthode non autorisée."
        ]);
        break;
}
?>