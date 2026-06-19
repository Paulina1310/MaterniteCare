<?php
// DÉSACTIVER l'affichage des erreurs pour éviter le HTML dans la réponse
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur connexion base de données"
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
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
            
            // Requête pour trouver l'utilisateur
            $stmt = $pdo->prepare('
                SELECT u."ID_UTILISATEUR", u."ID_PERSONNE", u."EMAIL", u."MOT_DE_PASSE",
                       u."TYPE_COMPTE", u."ACTIF"
                FROM "maternite_care"."UTILISATEUR" u
                WHERE u."EMAIL" = :email
            ');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['ACTIF']) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Email ou mot de passe incorrect."
                ]);
                exit;
            }
            
            if (!password_verify($password, $user['MOT_DE_PASSE'])) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Email ou mot de passe incorrect."
                ]);
                exit;
            }
            
            // Récupérer le profil
            $profil = null;
            $niveauAcces = 0;
            
            if ($user['TYPE_COMPTE'] === 'PERSONNEL') {
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
                $niveauAcces = $profil['NIVEAU_ACCES'] ?? 0;
            } else if ($user['TYPE_COMPTE'] === 'PATIENTE') {
                $stmtProfil = $pdo->prepare('
                    SELECT p."NOM", p."PRENOM", pat."ID_PATIENTE", pat."CODE_SUIVI", pat."MATRICULE",
                           pat."GROUPE_SANGUIN", pat."FACTEUR_RHESUS"
                    FROM "maternite_care"."PERSONNE" p
                    JOIN "maternite_care"."PATIENTE" pat ON p."ID_PERSONNE" = pat."ID_PERSONNE"
                    WHERE p."ID_PERSONNE" = :id
                ');
                $stmtProfil->execute([':id' => $user['ID_PERSONNE']]);
                $profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);
            }
            
            // Mettre à jour dernière connexion
            $stmtUpdate = $pdo->prepare('
                UPDATE "maternite_care"."UTILISATEUR"
                SET "DERNIERE_CONNEXION" = CURRENT_TIMESTAMP
                WHERE "ID_UTILISATEUR" = :id
            ');
            $stmtUpdate->execute([':id' => $user['ID_UTILISATEUR']]);
            
            $token = bin2hex(random_bytes(32));
            
            // ✅ CORRECTION : Redirections selon l'environnement
            // Sur Render : utilise la variable FRONTEND_URL
            // En local : utilise le chemin relatif
            $isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');
            $frontendUrl = $isLocal ? '' : (getenv('FRONTEND_URL') ?: 'https://maternitecare-frontend.onrender.com');
            
            // ✅ Noms de fichiers CORRECTS (correspondent aux vrais fichiers)
            $redirection = $frontendUrl . '/dash_patiente.html';
            
            if ($user['TYPE_COMPTE'] === 'PERSONNEL') {
                if ($niveauAcces >= 10) {
                    $redirection = $frontendUrl . '/dash_admin.html';      // ✅ Admin
                } else {
                    $redirection = $frontendUrl . '/medecin_dash.html';    // ✅ Médecin
                }
            }
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Connexion réussie.",
                "token" => $token,
                "redirection" => $redirection,
                "user" => [
                    "id_utilisateur" => $user['ID_UTILISATEUR'],
                    "id_personne" => $user['ID_PERSONNE'],
                    "email" => $user['EMAIL'],
                    "type_compte" => $user['TYPE_COMPTE'],
                    "niveau_acces" => $niveauAcces,
                    "profil" => $profil,
                    "id_patiente" => $profil['ID_PATIENTE'] ?? null
                ]
            ], JSON_PRETTY_PRINT);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur base de données"
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