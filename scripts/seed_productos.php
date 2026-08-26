<?php
// ============================================================
//  seed_productos.php — Agregar productos comerciales al carrito
// ------------------------------------------------------------
//  Inserta los productos ART002..ART008 SOLO si su código aún
//  no existe en la tabla `articulos` (evita duplicados).
//  Conserva todos los artículos existentes (ART001 incluido).
//  No modifica la estructura de la base de datos.
// ============================================================

$db  = require __DIR__ . '/../config.php';
$pdo = new PDO(
    "mysql:host={$db['DB_HOST']};dbname={$db['DB_NAME']};charset={$db['DB_CHARSET']}",
    $db['DB_USER'],
    $db['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$productos = [
    ['ART002', 'PC de Escritorio Gamer', 'Computadora de escritorio para gaming y trabajo de alto rendimiento', 8,  620000],
    ['ART003', 'Monitor LG 27 pulgadas 4K', 'Monitor UHD 4K de 27 pulgadas para oficina, diseño y entretenimiento', 12, 185000],
    ['ART004', 'Teclado Mecánico', 'Teclado mecánico retroiluminado para productividad y gaming', 20, 45000],
    ['ART005', 'Mouse Inalámbrico', 'Mouse inalámbrico ergonómico con conexión USB', 25, 18000],
    ['ART006', 'Audífonos Gamer', 'Audífonos con micrófono integrado y sonido estéreo', 15, 38000],
    ['ART007', 'Impresora Multifuncional', 'Impresora multifuncional con impresión, escáner y conexión Wi-Fi', 6, 98000],
    ['ART008', 'Webcam Full HD', 'Cámara web Full HD 1080p con micrófono integrado', 18, 32000],
];

$existe = $pdo->prepare("SELECT COUNT(*) FROM articulos WHERE codigo = ?");
$insert = $pdo->prepare(
    "INSERT INTO articulos (codigo, nombre, descripcion, cantidad, precio) VALUES (?, ?, ?, ?, ?)"
);

foreach ($productos as [$codigo, $nombre, $descripcion, $cantidad, $precio]) {
    $existe->execute([$codigo]);
    if ((int)$existe->fetchColumn() > 0) {
        echo "SKIP  $codigo (ya existe)\n";
        continue;
    }
    $insert->execute([$codigo, $nombre, $descripcion, $cantidad, $precio]);
    echo "OK    $codigo - $nombre\n";
}

echo "\nProductos actuales:\n";
foreach ($pdo->query("SELECT codigo, nombre, cantidad, precio FROM articulos ORDER BY codigo") as $fila) {
    echo "  {$fila['codigo']}\t{$fila['nombre']}\t{$fila['cantidad']}\t{$fila['precio']}\n";
}