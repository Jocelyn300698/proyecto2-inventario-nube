<?php
// ------------------------------------------------------------
//  Sistema de Gestión de Inventario — Proyecto 2 (ITI-522)
//  Copia de desarrollo.
//  La conexión a MySQL se lee desde config.php (archivo local,
//  NO versionado). Ver config.example.php para la plantilla.
// ------------------------------------------------------------

$db = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db['DB_HOST']};dbname={$db['DB_NAME']};charset={$db['DB_CHARSET']}",
        $db['DB_USER'],
        $db['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de conexión con la base de datos. Verifique la configuración en config.php.');
}

// ---------- Validaciones del servidor ----------
function longitud_texto(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function validar_articulo(array $post): array
{
    $errores = [];

    $codigo    = trim($post['codigo'] ?? '');
    $nombre    = trim($post['nombre'] ?? '');
    $cantidad  = $post['cantidad'] ?? '';
    $precio    = $post['precio'] ?? '';

    if ($codigo === '') {
        $errores[] = 'El código es obligatorio.';
    } elseif (longitud_texto($codigo) > 50) {
        $errores[] = 'El código no puede superar los 50 caracteres.';
    }

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (longitud_texto($nombre) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if (!is_numeric($cantidad) || $cantidad < 0 || (float)$cantidad != (int)$cantidad) {
        $errores[] = 'La cantidad debe ser un número entero mayor o igual a 0.';
    }

    if (!is_numeric($precio) || $precio < 0) {
        $errores[] = 'El precio debe ser un número mayor o igual a 0.';
    }

    return $errores;
}

$errores = [];
$mensaje = '';

// ---------- Registrar artículo ----------
if (isset($_POST['guardar'])) {
    $errores = validar_articulo($_POST);

    if (!$errores) {
        $s = $pdo->prepare(
            "INSERT INTO articulos
            (codigo, nombre, descripcion, cantidad, precio)
            VALUES (?, ?, ?, ?, ?)"
        );

        $s->execute([
            trim($_POST['codigo']),
            trim($_POST['nombre']),
            trim($_POST['descripcion'] ?? ''),
            $_POST['cantidad'],
            $_POST['precio']
        ]);

        header("Location: index.php?guardado=1");
        exit;
    }
}

// ---------- Actualizar artículo ----------
if (isset($_POST['actualizar'])) {
    $id = $_POST['id'] ?? '';

    if (!is_numeric($id)) {
        $errores[] = 'Artículo no válido para actualizar.';
    } else {
        $errores = validar_articulo($_POST);
    }

    if (!$errores) {
        $s = $pdo->prepare(
            "UPDATE articulos
            SET codigo=?, nombre=?, descripcion=?, cantidad=?, precio=?
            WHERE id=?"
        );

        $s->execute([
            trim($_POST['codigo']),
            trim($_POST['nombre']),
            trim($_POST['descripcion'] ?? ''),
            $_POST['cantidad'],
            $_POST['precio'],
            $id
        ]);

        header("Location: index.php?actualizado=1");
        exit;
    }
}

// ---------- Mensajes de éxito ----------
if (isset($_GET['guardado'])) {
    $mensaje = 'Artículo registrado correctamente.';
} elseif (isset($_GET['actualizado'])) {
    $mensaje = 'Artículo actualizado correctamente.';
}

// ---------- Consultar (listado y edición) ----------
$editar = null;

if (isset($_GET['editar'])) {
    $s = $pdo->prepare("SELECT * FROM articulos WHERE id=?");
    $s->execute([$_GET['editar']]);
    $editar = $s->fetch(PDO::FETCH_ASSOC);
}

$articulos = $pdo
    ->query("SELECT * FROM articulos ORDER BY id DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistema de Gestión de Inventario</title>
<style>
:root {
    --primario: #1d4ed8;
    --primario-oscuro: #1e40af;
    --fondo: #f1f5f9;
    --tarjeta: #ffffff;
    --texto: #0f172a;
    --texto-suave: #475569;
    --borde: #cbd5e1;
    --exito-fondo: #dcfce7;
    --exito-borde: #86efac;
    --exito-texto: #14532d;
    --error-fondo: #fee2e2;
    --error-borde: #fca5a5;
    --error-texto: #7f1d1d;
    --radio: 10px;
    --sombra: 0 1px 3px rgba(15, 23, 42, .12);
}

* { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--fondo);
    color: var(--texto);
    line-height: 1.5;
    font-size: 16px;
}

.contenedor {
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
    padding: 0 16px;
}

/* ----- Encabezado ----- */
.encabezado {
    background: var(--primario);
    color: #ffffff;
    padding: 20px 0;
    margin-bottom: 20px;
}

.encabezado h1 {
    margin: 0;
    font-size: 1.35rem;
    line-height: 1.3;
}

.encabezado .subtitulo {
    margin: 4px 0 0;
    font-size: .85rem;
    opacity: .9;
}

/* ----- Tarjetas ----- */
.tarjeta {
    background: var(--tarjeta);
    border-radius: var(--radio);
    box-shadow: var(--sombra);
    padding: 20px;
    margin-bottom: 20px;
}

.tarjeta h2 {
    margin: 0 0 16px;
    font-size: 1.1rem;
}

/* ----- Formulario ----- */
.campo { margin-bottom: 14px; }

.campo label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: .9rem;
    color: var(--texto-suave);
}

.campo input,
.campo textarea {
    width: 100%;
    padding: 12px;
    font-size: 16px; /* evita el zoom automático en iOS */
    border: 1px solid var(--borde);
    border-radius: 8px;
    background: #ffffff;
    color: var(--texto);
}

.campo input:focus,
.campo textarea:focus {
    outline: 2px solid var(--primario);
    outline-offset: 1px;
    border-color: var(--primario);
}

.campos-fila {
    display: flex;
    gap: 14px;
}

.campos-fila .campo { flex: 1; }

/* ----- Botones ----- */
.acciones {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 6px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px; /* área táctil cómoda */
    padding: 12px 20px;
    font-size: 1rem;
    font-weight: 600;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}

.btn-primario {
    background: var(--primario);
    color: #ffffff;
    min-width: 180px;
}

.btn-primario:hover { background: var(--primario-oscuro); }

.btn-secundario {
    background: #e2e8f0;
    color: var(--texto);
    min-width: 140px;
}

.btn-secundario:hover { background: #cbd5e1; }

/* ----- Alertas ----- */
.alerta {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: .95rem;
    border: 1px solid transparent;
}

.alerta-exito {
    background: var(--exito-fondo);
    border-color: var(--exito-borde);
    color: var(--exito-texto);
}

.alerta-error {
    background: var(--error-fondo);
    border-color: var(--error-borde);
    color: var(--error-texto);
}

.alerta ul {
    margin: 8px 0 0;
    padding-left: 20px;
}

/* ----- Tabla ----- */
.tabla {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 620px;
}

th, td {
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid var(--borde);
    font-size: .95rem;
    vertical-align: top;
}

th {
    font-weight: 600;
    color: var(--texto-suave);
    background: #f8fafc;
    white-space: nowrap;
}

.btn-editar {
    color: var(--primario);
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}

.btn-editar:hover { text-decoration: underline; }

.vacio { color: var(--texto-suave); }

/* ----- Enlace a la tienda ----- */
.btn-tienda {
    background: #ffffff;
    color: var(--primario);
    margin-top: 12px;
    min-width: 120px;
}

.btn-tienda:hover { background: #e2e8f0; }

/* ----- Pie ----- */
.pie {
    text-align: center;
    color: var(--texto-suave);
    font-size: .8rem;
    padding: 8px 0 24px;
}

/* ============================================================
   Pantallas pequeñas (teléfonos)
   ============================================================ */
@media (max-width: 600px) {
    body { font-size: 15px; }

    .contenedor { padding: 0 12px; }

    .encabezado {
        padding: 16px 0;
        margin-bottom: 12px;
    }

    .encabezado h1 { font-size: 1.2rem; }

    .tarjeta {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 12px;
    }

    .campos-fila {
        flex-direction: column;
        gap: 0;
    }

    .btn { width: 100%; }

    /* Listado como tarjetas apiladas (más cómodo que el scroll) */
    .tabla { overflow: visible; }
    table { min-width: 0; }
    thead { display: none; }

    tr {
        display: block;
        border: 1px solid var(--borde);
        border-radius: 8px;
        padding: 4px 12px;
        margin-bottom: 10px;
        background: #ffffff;
    }

    td {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        border: 0;
        padding: 8px 0;
    }

    td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--texto-suave);
        flex-shrink: 0;
    }

    td[data-label="Descripción"] {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
}
</style>
</head>
<body>

<header class="encabezado">
    <div class="contenedor">
        <h1>Sistema de Gestión de Inventario</h1>
        <p class="subtitulo">Proyecto 2 · ITI-522 Computación en la Nube</p>
        <p class="enlace-ver-tienda">
            <a class="btn btn-tienda" href="tienda/index.php">🛒 Ver tienda</a>
            <a class="btn btn-tienda" href="/tienda/">↩️ Volver a la tienda</a>
        </p>
    </div>
</header>

<main class="contenedor">

<?php if ($mensaje !== ''): ?>
    <div class="alerta alerta-exito" role="status"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="alerta alerta-error" role="alert">
        <strong>Corrija los siguientes errores:</strong>
        <ul>
        <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="tarjeta">
    <h2><?= $editar ? 'Editar artículo' : 'Registrar artículo' ?></h2>

    <form method="post">
        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">

        <div class="campo">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" maxlength="50" required
                   value="<?= htmlspecialchars($editar['codigo'] ?? '') ?>"
                   placeholder="Ej. ART-001">
        </div>

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required
                   value="<?= htmlspecialchars($editar['nombre'] ?? '') ?>"
                   placeholder="Ej. Caja de cartón 40x30">
        </div>

        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" maxlength="255" rows="3"
                      placeholder="Opcional"><?= htmlspecialchars($editar['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="campos-fila">
            <div class="campo">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" min="0" step="1"
                       inputmode="numeric" required
                       value="<?= htmlspecialchars($editar['cantidad'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="precio">Precio</label>
                <input type="number" id="precio" name="precio" min="0" step="0.01"
                       inputmode="decimal" required
                       value="<?= htmlspecialchars($editar['precio'] ?? '') ?>">
            </div>
        </div>

        <?php if ($editar): ?>
            <div class="acciones">
                <button type="submit" name="actualizar" class="btn btn-primario">Actualizar artículo</button>
                <a class="btn btn-secundario" href="index.php">Cancelar</a>
            </div>
        <?php else: ?>
            <div class="acciones">
                <button type="submit" name="guardar" class="btn btn-primario">Registrar artículo</button>
            </div>
        <?php endif; ?>
    </form>
</section>

<section class="tarjeta">
    <h2>Artículos</h2>

    <?php if (!$articulos): ?>
        <p class="vacio">No hay artículos registrados todavía.</p>
    <?php else: ?>
        <div class="tabla">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($articulos as $a): ?>
                    <tr>
                        <td data-label="Código"><?= htmlspecialchars($a['codigo']) ?></td>
                        <td data-label="Nombre"><?= htmlspecialchars($a['nombre']) ?></td>
                        <td data-label="Descripción"><?= htmlspecialchars($a['descripcion'] ?? '—') ?></td>
                        <td data-label="Cantidad"><?= (int)$a['cantidad'] ?></td>
                        <td data-label="Precio">₡<?= number_format((float)$a['precio'], 2) ?></td>
                        <td data-label="Acción">
                            <a class="btn-editar" href="?editar=<?= (int)$a['id'] ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<footer class="pie">
    <p>Sistema de Gestión de Inventario · Proyecto 2 — ITI-522 · VM Azure</p>
</footer>

</main>
</body>
</html>
