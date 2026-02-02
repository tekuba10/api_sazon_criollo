<?php
require __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare("
    UPDATE \"campañas\"
    SET activa = false
    WHERE activa = true
      AND fecha_final < NOW()
    ");

    $stmt->execute();

    echo date('Y-m-d H:i:s') . " - Campañas vencidas desactivadas\n";

} catch (Exception $e) {
    echo date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
}
