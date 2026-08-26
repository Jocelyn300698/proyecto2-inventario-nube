# Plan de Recuperación ante Desastres

Documento que define la estrategia de respaldo, los objetivos de recuperación
(RPO/RTO) y el procedimiento a seguir para restaurar el **Sistema de Gestión
de Inventario** y la **tienda** ante una falla o pérdida de datos.

---

## Objetivo del plan

Establecer un mecanismo **automatizado** de respaldo de la base de datos
MySQL del proyecto y un procedimiento **documentado y probado** de
recuperación, de modo que ante una pérdida total de datos el grupo pueda
restaurar el servicio dentro de los objetivos definidos:

- **RPO = 6 horas** — pérdida máxima aceptable de información.
- **RTO = 1 hora** — tiempo máximo para volver a operar.

---

## Estrategia de respaldo

| Componente          | Descripción                                                        |
| ------------------- | ------------------------------------------------------------------ |
| **Origen**          | MySQL administrado en la VM (base `inventario`, tabla `articulos`). |
| **Herramienta**     | `mysqldump` (volcado consistente: `--single-transaction`, rutinas, triggers). |
| **Compresión**      | `gzip` (archivo `inventario_AAAAMMDD_HHMMSS.sql.gz`).              |
| **Respaldo local**  | `/home/UTN/backups_inventario/db/` en la propia VM.                |
| **Copia externa**   | Azure Blob Storage mediante `azcopy` (el respaldo local se conserva siempre). |
| **Frecuencia**      | Cada **6 horas** mediante cron.                                    |

### Automatización con cron

El respaldo se ejecuta automáticamente **4 veces por día** con la siguiente
programación (`crontab` del usuario `UTN`):

```
15 */6 * * * /home/UTN/proyectos_azure/proyecto2_inventario/scripts/backup_db.sh >> /home/UTN/backups_inventario/logs/backup.log 2>&1
```

Horarios de ejecución:

- 00:15
- 06:15
- 12:15
- 18:15

La salida (éxitos y errores) se registra en
`/home/UTN/backups_inventario/logs/backup.log`. El script define su propio
`PATH` para funcionar sin depender de una sesión interactiva, usa rutas
absolutas y lee las credenciales desde un archivo protegido **fuera del
repositorio** (nunca en la línea de comandos ni en Git).

---

## RPO — Objetivo de Punto de Recuperación: 6 horas

El **RPO (Recovery Point Objective)** define la cantidad máxima de
información que el negocio está dispuesto a perder ante un desastre.

**RPO = 6 horas.**

### Justificación

- Se generan **cuatro respaldos diarios** (uno cada 6 horas).
- Ante una pérdida completa, como máximo podrían perderse los cambios
  realizados **desde el último respaldo** (hasta 6 horas de operación).
- Por eso el objetivo de pérdida máxima de información es de **hasta 6 horas**.
- Se eligió este valor porque el inventario **cambia durante la operación**
  (altas, bajas y modificaciones de artículos) y una pérdida de 24 horas
  sería demasiado alta.
- Aumentar la frecuencia reduciría todavía más el RPO, pero también
  aumenta el **almacenamiento** consumido y el **número de operaciones**
  de subida a Azure.

---

## RTO — Objetivo de Tiempo de Recuperación: 1 hora

El **RTO (Recovery Time Objective)** define el tiempo máximo en que el
servicio debe volver a estar operativo tras un desastre.

**RTO = 1 hora.**

### Justificación

- El objetivo es recuperar el servicio en un **máximo de una hora** desde
  que se inicia el procedimiento de recuperación.
- El RTO es un **objetivo de recuperación del negocio**: contempla todo el
  proceso (identificar el fallo, obtener el respaldo, restaurar y verificar
  cada módulo), **no solamente** el tiempo que tarda el comando `mysql`.
- El procedimiento completo está documentado y ya fue **probado** una vez
  de forma controlada, lo que da confianza en que el objetivo es alcanzable.

---

## Procedimiento de recuperación

Pasos a seguir desde la detección del fallo hasta comprobar que la tienda
y el inventario vuelven a operar:

1. **Identificar el fallo.** Confirmar el alcance (base de datos, aplicación,
   servidor) y determinar que se requiere restaurar desde un respaldo.
2. **Obtener el respaldo válido.** Elegir el respaldo más reciente íntegro
   (verificar el log en `backup.log` y la integridad con `gzip -t`) desde el
   respaldo local o desde Azure Blob Storage.
3. **Restaurar MySQL.** Restaurar la base `inventario` con `mysql` usando el
   archivo `inventario_AAAAMMDD_HHMMSS.sql.gz` descomprimido.
4. **Verificar la tabla de artículos.** Confirmar que la tabla `articulos`
   existe y contiene los registros esperados.
5. **Comprobar el Sistema de Gestión de Inventario.** Abrir `index.php` y
   verificar que permite registrar, consultar y actualizar artículos.
6. **Comprobar la tienda.** Abrir `tienda/index.php` y confirmar que muestra
   los productos leídos de la base restaurada.
7. **Confirmar que Nginx y PHP siguen funcionando.** Verificar el estado de
   los servicios web para garantizar que ambos módulos quedan accesibles.

> El RTO de 1 hora aplica al procedimiento completo, no a un solo paso.

---

## Prueba realizada

Se realizó una **prueba controlada de restauración** que confirmó que el
procedimiento funciona:

1. Se creó un registro de prueba identificado como **`RESTORE-001`**.
2. Se generó un respaldo de la base de datos.
3. Se **eliminó** el registro `RESTORE-001`.
4. Se **restauró** el respaldo.
5. El registro **`RESTORE-001` reapareció**, confirmando que la restauración
   recuperó los datos.
6. Se comprobó que el **Sistema de Gestión de Inventario volvió a operar**
   normalmente.

El resultado: el sistema volvió a operar tras la restauración.

---

## Responsabilidades del grupo

Al administrar MySQL en la VM, el grupo es responsable de:

- **Instalación** — del motor de base de datos y herramientas asociadas.
- **Configuración** — parámetros, usuarios y conexión con la aplicación.
- **Seguridad** — credenciales protegidas y acceso restringido.
- **Respaldos** — generación automática y verificación periódica.
- **Restauración** — ejecutar y validar el procedimiento de recuperación.
- **Monitoreo básico** — revisar los logs de respaldo y el estado del servicio.
- **Continuidad** — mantener el plan actualizado y probarlo periódicamente.
