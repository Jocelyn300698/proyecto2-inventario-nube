# Justificación de los servicios

Documento que resume **por qué se eligió cada servicio** de la solución del
Proyecto 2, relacionando cada decisión con la **operación**, la
**disponibilidad**, la **seguridad**, la **continuidad del servicio** y los
**requisitos del Proyecto 2**.

> Nota: la solución se ejecuta sobre una VM propia con MySQL administrado por
> el grupo. **No** se utiliza PaaS para la base de datos ni Azure SQL.

| Servicio | Por qué se eligió | Relación con la solución |
| --- | --- | --- |
| **Azure VM (Ubuntu Server)** | El Proyecto 2 exige que la empresa conserve el control administrativo del motor de base de datos. | **Operación**: sobre la VM corren Nginx, PHP y MySQL. **Disponibilidad**: la VM es la infraestructura donde vive el sistema. **Requisitos**: satisfacer la restricción contractual de administrar la base de datos. |
| **Nginx** | Servidor web ligero y de alto rendimiento para publicar la aplicación. | **Operación**: publica la tienda (`/tienda/`) y el inventario (`/`). **Disponibilidad**: un mismo servidor atiende ambos módulos de la solución. |
| **PHP** | Backend sencillo y ampliamente usado para la lógica del servidor y la conexión con MySQL. | **Operación**: procesa el registro, consulta y actualización de artículos y entrega los datos a la tienda. **Seguridad**: usa PDO y `htmlspecialchars` para mitigar inyección SQL y XSS. |
| **MySQL (en la VM)** | La base de datos debe ser administrada por el grupo dentro de la propia VM (restricción contractual del Proyecto 2). | **Operación**: fuente única de datos para inventario y tienda (tabla `articulos`). **Requisitos**: no se usan bases de datos gestionadas ni Azure SQL. |
| **Azure Blob Storage** | Almacenamiento externo de objetos para que los respaldos no dependan únicamente de la misma VM. | **Continuidad del servicio**: copia externa accesible incluso si la VM se pierde. **Disponibilidad**: sustenta el plan de recuperación ante desastres. |
| **AzCopy** | Herramienta de Azure para transferir automáticamente los respaldos a Blob Storage. | **Continuidad del servicio**: automatiza la subida de la copia externa sin exponer credenciales en la línea de comandos. |
| **cron** | Automatiza la ejecución del respaldo **cada 6 horas** sin intervención manual. | **Operación**: programa `backup_db.sh` 4 veces al día. **Disponibilidad**: protege los datos con regularidad y sostiene el RPO de 6 horas. |
| **GitHub** | Repositorio de código y control de versiones del sistema. | **Operación/seguridad**: versiona el código y mantiene las credenciales fuera del repositorio (`config.php` en `.gitignore`). |

## Relación con los objetivos del Proyecto 2

- **Operación**: los servicios se combinan para que la tienda y el inventario
  funcionen como módulos de una misma solución sobre una base de datos única.
- **Disponibilidad**: respaldo local + copia externa en Azure Blob Storage y
  automatización con cron.
- **Seguridad**: credenciales fuera de Git y acceso restringido a la base.
- **Continuidad del servicio**: plan de recuperación con **RPO = 6 horas** y
  **RTO = 1 hora**, probado con una restauración real.
- **Requisitos del Proyecto 2**: MySQL administrado por el grupo dentro de la VM
  (no PaaS), acceso móvil responsivo y documentación del plan de respaldo.
