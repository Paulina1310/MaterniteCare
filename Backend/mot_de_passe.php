<?php
// Mot de passe pour le médecin
echo "Médecin: " . password_hash('Medecin123!', PASSWORD_DEFAULT) . "\n";

// Mot de passe pour la patiente
echo "Patiente: " . password_hash('Patiente123!', PASSWORD_DEFAULT) . "\n";

echo "admin: " . password_hash('admin123!', PASSWORD_DEFAULT) . "\n";

?>