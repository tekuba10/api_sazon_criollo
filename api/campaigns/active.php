<?php
require __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
  SELECT id_campaña, titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, dirigido, fecha_inicio, fecha_final, created_at
  FROM public.campañas
  WHERE fecha_final > NOW()  -- activas si no han terminado
  ORDER BY created_at DESC
");

echo json_encode([
  "status" => "ok",
  "campaigns" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);

