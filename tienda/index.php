<?php
// ============================================================
//  Tienda de equipos de cómputo — integrada con el inventario
//  Proyecto 1 + Proyecto 2 (ITI-522)
// ------------------------------------------------------------
//  Esta tienda NO trae productos escritos a mano: los obtiene
//  de la MISMA tabla de artículos que usa el Sistema de
//  Gestión de Inventario (MySQL administrado en la VM).
//
//  La conexión se lee desde config.php (archivo local, NO
//  versionado). No se copian credenciales en este archivo.
//
//  Nota: la tienda está en la carpeta `tienda/` y registra
//  productos del inventario en modo lectura (SOLO SELECT).
// ============================================================

$db = require __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db['DB_HOST']};dbname={$db['DB_NAME']};charset={$db['DB_CHARSET']}",
        $db['DB_USER'],
        $db['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('No se pudo conectar con la base de datos. Intente más tarde.');
}

// ---------- Leer los artículos del inventario (SOLO lectura) ----------
$articulos = $pdo
    ->query("SELECT id, codigo, nombre, descripcion, cantidad, precio
             FROM articulos
             ORDER BY nombre ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

// Datos que el carrito necesita en JavaScript. Se codifican de forma segura
// para incrustarlos dentro de un bloque <script> sin romper el HTML.
$productos_json = json_encode(
    array_map(static function (array $a): array {
        return [
            'id'         => (int)$a['id'],
            'codigo'     => $a['codigo'],
            'nombre'     => $a['nombre'],
            'descripcion'=> $a['descripcion'],
            'precio'     => (float)$a['precio'],
            'stock'      => (int)$a['cantidad'],
        ];
    }, $articulos),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
) ?: '[]';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Equipos de Cómputo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="tienda.css">
</head>

<body>
    <header class="header">
        <h1>🛒 Tienda de Equipos de Cómputo</h1>
        <p class="subtitle">Proyecto 1 + Proyecto 2 · ITI-522 – Computación en la Nube</p>
        <p class="professor"><strong>Profesor:</strong> Lic. Randall Zamora Rojas</p>
    </header>

    <nav class="nav">
        <ul>
            <li><a href="#tienda">Tienda</a></li>
            <li><a href="#carrito">Carrito</a></li>
            <li><a href="/">Gestión de Inventario</a></li>
        </ul>
    </nav>

    <main>
        <section id="tienda" class="section store-section">
            <h2>🛍️ Productos</h2>
            <p class="store-description">
                Los productos provienen directamente del Sistema de Gestión de Inventario.
                Si en el módulo administrativo cambian el precio o la cantidad de un artículo,
                este cambio se refleja aquí al recargar la página.
            </p>

            <div class="store-layout">
                <!-- Grilla de Productos (renderizada en PHP desde la base de datos) -->
                <div class="products-grid" id="products-grid">
                    <?php if (!$articulos): ?>
                        <p class="cart-empty">No hay productos disponibles en el inventario.</p>
                    <?php else: ?>
                        <?php foreach ($articulos as $a): ?>
                            <?php
                            $tieneStock = (int)$a['cantidad'] > 0;
                            $id = (int)$a['id'];
                            ?>
                            <div class="product-card" data-id="<?= $id ?>">
                                <?php if (!$tieneStock): ?>
                                    <span class="stock-badge stock-agotado">Sin existencias</span>
                                <?php elseif ((int)$a['cantidad'] <= 5): ?>
                                    <span class="stock-badge stock-pocas">Quedan pocas</span>
                                <?php else: ?>
                                    <span class="stock-badge stock-disponible">Disponible</span>
                                <?php endif; ?>

                                <div class="product-icon">📦</div>
                                <h4><?= htmlspecialchars($a['nombre']) ?></h4>
                                <p class="product-code"><?= htmlspecialchars($a['codigo']) ?></p>
                                <p class="product-desc"><?= htmlspecialchars($a['descripcion'] ?? '—') ?></p>
                                <p class="product-price">₡<?= number_format((float)$a['precio'], 2) ?></p>

                                <?php if ($tieneStock): ?>
                                    <p class="product-stock">Disponible: <?= (int)$a['cantidad'] ?> unidad(es)</p>
                                    <button class="btn-add" data-id="<?= $id ?>" onclick="agregarAlCarrito(<?= $id ?>)">
                                        🛒 Agregar al carrito
                                    </button>
                                <?php else: ?>
                                    <p class="product-stock product-stock-agotado">Sin existencias</p>
                                    <button class="btn-add" disabled>🚫 Agotado</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Panel del Carrito -->
                <aside class="cart-panel" id="carrito">
                    <h3>🛍️ Carrito de Compras</h3>
                    <div id="cart-items" class="cart-items">
                        <p class="cart-empty">Tu carrito está vacío 🛒</p>
                    </div>
                    <div class="cart-summary">
                        <div class="cart-total-line">
                            <span>Total:</span>
                            <span id="cart-total">₡0</span>
                        </div>
                        <button id="clear-cart-btn" class="btn-clear">🗑️ Vaciar Carrito</button>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>© 2026 – Universidad Tecnológica Nacional. Curso: Computación en la Nube. Tienda integrada con el Sistema de Gestión de Inventario.</p>
    </footer>

    <!-- Datos del inventario para el carrito (misma base de datos) -->
    <script>
        window.PRODUCTOS = <?= $productos_json ?>;
    </script>
    <script src="tienda.js"></script>
</body>

</html>