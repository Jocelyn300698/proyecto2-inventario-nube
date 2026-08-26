<?php
// ============================================================
//  config.example.php — Plantilla de configuración (versionable)
// ============================================================
//  Este archivo contiene SOLO valores de ejemplo y SÍ puede
//  incluirse en el repositorio Git.
//
//  Uso:
//   1) Copiar este archivo como config.php
//      (config.php NO se versiona; está en .gitignore).
//   2) Completar DB_PASSWORD con la contraseña real del motor
//      MySQL instalado en la VM del grupo.
//
//  NUNCA colocar credenciales reales en este archivo ni en Git.
// ============================================================

return [
    'DB_HOST'     => 'localhost',          // Servidor MySQL (VM administrada por el grupo)
    'DB_NAME'     => 'inventario',         // Nombre de la base de datos
    'DB_USER'     => 'usuario_ejemplo',    // Usuario MySQL (reemplazar)
    'DB_PASSWORD' => 'contraseña_ejemplo', // Contraseña (reemplazar, no usar este valor)
    'DB_CHARSET'  => 'utf8mb4',
];
