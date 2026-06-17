<?php
// Charger les variables depuis le fichier .env situé dans le même dossier que ce script
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    // En développement, on affiche l'erreur. En production, on la logue discrètement.
    die("Erreur critique : Le fichier .env est introuvable dans le dossier backend.");
}
$env = parse_ini_file($envFile);

$host = $env['DB_HOST'];
$port = $env['DB_PORT'];
$dbname = $env['DB_NAME'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--search_path=maternite_care'";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // On affiche l'erreur pour le débogage local
    die("Échec de la connexion à la BDD : " . $e->getMessage());
}
?>