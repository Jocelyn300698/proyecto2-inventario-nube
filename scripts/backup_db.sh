#!/usr/bin/env bash
# ============================================================
#  backup_db.sh — Respaldo automático local de MySQL
# ------------------------------------------------------------
#  Crea un respaldo comprimido (gzip) de la base de datos
#  utilizada por la aplicación, usando `mysqldump`.
#
#  Usa `--defaults-extra-file` para leer host / usuario /
#  contraseña desde un archivo protegido FUERA del repositorio,
#  sin exponer credenciales en la línea de comandos ni el script.
#
#  Salida: /home/UTN/backups_inventario/db/inventario_AAAAMMDD_HHMMSS.sql.gz
#
#  Flujo:
#   1) mysqldump + gzip        -> RESPALDO LOCAL OK / ERROR DE RESPALDO
#   2) Subida a Azure Blob (si AzCopy y configuración hay):
#        - RESPALDO AZURE OK
#        - ERROR AL SUBIR RESPALDO A AZURE (conserva el archivo local)
#
#  En caso de fallo de Azure el respaldo local SIEMPRE se conserva.
# ============================================================

set -o pipefail

# PATH seguro: cron ejecuta con un PATH mínimo; fijamos rutas estándar
# para que mysqldump, gzip y azcopy se encuentren siempre.
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

# --- Configuración -------------------------------------------------
CREDENTIALS_FILE="/home/UTN/.config/inventario/mysql-backup.cnf"
BACKUP_DIR="/home/UTN/backups_inventario/db"
DB_NAME="inventario"

# Configuración de Azure Blob Storage (fuera del repositorio).
BLOB_ENV_FILE="/home/UTN/.config/inventario/blob-backup.env"
# -------------------------------------------------------------------

# Verificar que existe el archivo de credenciales protegido.
if [ ! -f "$CREDENTIALS_FILE" ]; then
    echo "ERROR DE RESPALDO: no se encontro el archivo de credenciales en $CREDENTIALS_FILE"
    exit 1
fi

# Verificar que mysqldump está disponible.
if ! command -v mysqldump >/dev/null 2>&1; then
    echo "ERROR DE RESPALDO: mysqldump no esta disponible"
    exit 1
fi

# Crear el directorio de respaldos si no existe.
if ! mkdir -p "$BACKUP_DIR"; then
    echo "ERROR DE RESPALDO: no se pudo crear $BACKUP_DIR"
    exit 1
fi

# Nombre de archivo con marca de tiempo: inventario_AAAAMMDD_HHMMSS.sql.gz
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTPUT_FILE="${BACKUP_DIR}/${DB_NAME}_${STAMP}.sql.gz"

# Realizar el volcado y comprimirlo con gzip.
# Las credenciales se leen desde el archivo protegido (--defaults-extra-file);
# la contraseña NO aparece en la línea de comandos.
LBL_ERROR_MSG="none"

if mysqldump \
        --defaults-extra-file="$CREDENTIALS_FILE" \
        --single-transaction --routines --triggers --no-tablespaces \
        "$DB_NAME" | gzip > "$OUTPUT_FILE"; then

    # Verificar que el archivo fue creado y no está vacío.
    if [ -s "$OUTPUT_FILE" ]; then
        # Verificar integridad del gzip y existencia real del archivo.
        if gzip -t "$OUTPUT_FILE"; then
            echo "RESPALDO LOCAL OK: $OUTPUT_FILE"
        else
            LBL_ERROR_MSG="el archivo comprimido no es valido (gzip -t)"
        fi
    else
        LBL_ERROR_MSG="el archivo no se creo o esta vacio"
    fi
else
    # En caso de fallo del volcado, eliminar el archivo parcial (sin subir nada).
    rm -f "$OUTPUT_FILE"
    echo "ERROR DE RESPALDO"
    exit 1
fi

# Si el respaldo local falló, no intentar subir a Azure.
if [ "$LBL_ERROR_MSG" != "none" ]; then
    echo "ERROR DE RESPALDO: $LBL_ERROR_MSG"
    exit 1
fi

# ============================================================
#  Paso Azure Blob Storage (opcional/cargado solo si configurado)
# ============================================================
# Cargar configuración de Azure si el archivo protegido existe.
if [ -f "$BLOB_ENV_FILE" ]; then
    # shellcheck disable=SC1090
    set -a
    # shellcheck disable=SC1090
    . "$BLOB_ENV_FILE"
    set +a
fi

# Determinar si hay configuración de Azure lista.
AZURE_CONFIGURED=1
if [ -z "$AZURE_BLOB_URL" ] || [ -z "$AZURE_BLOB_SAS" ]; then
    AZURE_CONFIGURED=0
fi

if [ "$AZURE_CONFIGURED" -eq 1 ]; then
    # Verificar que azcopy está disponible.
    if ! command -v azcopy >/dev/null 2>&1; then
        echo "ERROR AL SUBIR RESPALDO A AZURE: azcopy no esta instalado"
        echo "AVISO: el respaldo local se conserva en $OUTPUT_FILE"
        exit 1
    fi

    # Construir destino. La URL del container ya incluye HTTPS y el SAS se
    # anexa para identificar el archivo con su ruta/nombre dentro del container.
    AZURE_DEST="${AZURE_BLOB_URL}/${OUTPUT_FILE##*/}${AZURE_BLOB_SAS}"

    # Subir el respaldo. El SAS va dentro de la URL (forma estándar de AzCopy),
    # NO se imprime ni se guarda en Git; la contraseña nunca va en la CLI.
    if azcopy copy "$OUTPUT_FILE" "$AZURE_DEST" >/dev/null 2>&1; then
        echo "RESPALDO AZURE OK: ${AZURE_BLOB_URL}/${OUTPUT_FILE##*/}"
        exit 0
    else
        # Falla de Azure: conservar el respaldo local, no borrarlo.
        echo "ERROR AL SUBIR RESPALDO A AZURE"
        echo "AVISO: el respaldo local se conserva en $OUTPUT_FILE"
        exit 1
    fi
else
    # Sin configuración de Azure: solo respaldo local exitoso.
    echo "RESPALDO LOCAL OK"
    exit 0
fi