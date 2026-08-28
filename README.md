# Proyecto 2 - Sistema de Inventario en la Nube

Proyecto desarrollado para el curso **Computación en la Nube - ITI-522**.
Integrantes:
Tatiana Urbina
Jocelyn Carballo
Royner Gutierrez
Camila Chaves

La solución corresponde a la evolución del Proyecto 1. Se mantiene la tienda de equipos de cómputo y se integra con un **Sistema de Gestión de Inventario**, utilizando una base de datos MySQL administrada dentro de una máquina virtual de Microsoft Azure.

## Tecnologías utilizadas

* Microsoft Azure
* Ubuntu Server
* Nginx
* PHP
* MySQL
* HTML, CSS y JavaScript
* Azure Blob Storage
* AzCopy
* cron
* Git y GitHub

## Funcionalidades

El sistema permite:

* Registrar artículos.
* Consultar el inventario.
* Actualizar artículos.
* Mostrar los productos del inventario en la tienda.
* Reflejar cambios de precio y cantidad automáticamente.
* Utilizar la aplicación desde dispositivos móviles.
* Agregar productos al carrito de compras.
* Controlar existencias disponibles.

## Arquitectura

La aplicación funciona sobre una máquina virtual de Azure.

```text
Usuarios / Teléfonos
        |
        v
      Internet
        |
        v
 Microsoft Azure
        |
        v
   VM Ubuntu
        |
        v
      Nginx
     /     \
    /       \
Inventario  Tienda
    \       /
       PHP
        |
      MySQL
        |
  backup_db.sh
        |
   mysqldump + gzip
        |
   Respaldo local
        |
      AzCopy
        |
 Azure Blob Storage
```

## Respaldo y recuperación

La base de datos se respalda automáticamente cada **6 horas** mediante `cron`.

Los respaldos:

1. Se generan con `mysqldump`.
2. Se comprimen con `gzip`.
3. Se almacenan localmente.
4. Se copian a Azure Blob Storage mediante AzCopy.

Se realizó una prueba real de restauración, verificando que los datos eliminados fueran recuperados correctamente.

* **RPO:** 6 horas.
* **RTO:** 1 hora.

## Seguridad

Las credenciales de MySQL no se almacenan directamente en el código publicado.

El archivo:

```text
config.php
```

contiene la configuración local y está excluido de Git mediante `.gitignore`.

Se utiliza `config.example.php` como plantilla sin credenciales reales.

## Estructura principal

```text
proyecto2_inventario/
├── index.php
├── config.example.php
├── tienda/
│   ├── index.php
│   ├── tienda.css
│   └── tienda.js
├── scripts/
│   ├── backup_db.sh
│   └── seed_productos.php
├── docs/
│   ├── arquitectura_revisada.md
│   ├── cambios_respecto_proyecto1.md
│   ├── justificacion_servicios.md
│   └── recuperacion_desastres.md
└── .gitignore
```

## Acceso

Sistema de Gestión de Inventario:

```text
http://IP_PUBLICA/
```

Tienda:

```text
http://IP_PUBLICA/tienda/
```

## Repositorio

https://github.com/Jocelyn300698/proyecto2-inventario-nube

## Nota

La base de datos MySQL se ejecuta directamente sobre la máquina virtual y no utiliza un servicio de base de datos administrado, de acuerdo con los requisitos del Proyecto 2.
