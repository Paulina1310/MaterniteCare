<?php
// Configuration pour Render (utilise les variables d'environnement)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'maternite_care';
$username = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';
$port = getenv('DB_PORT') ?: '5432';

// Si DATABASE_URL est défini (Render), l'utiliser
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $host = $url['host'];
    $port = $url['port'] ?? 5432;
    $dbname = ltrim($url['path'], '/');
    $username = $url['user'] ?? 'postgres';
    $password = $url['pass'] ?? 'postgres';
}

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur connexion BDD: " . $e->getMessage()]);
    exit;
}
?>