# Cambios respecto al Proyecto 1

Tabla comparativa que resume cómo la solución evolucionó entre el **Proyecto 1**
(frontend de tienda) y el **Proyecto 2** (aplicación integrada con inventario,
backend y respaldos).

| Elemento | Proyecto 1 | Decisión | Proyecto 2 | Justificación técnica |
| --- | --- | --- | --- | --- |
| **Frontend de tienda** | Tienda estática HTML, CSS y JavaScript. | SE MANTIENE Y SE MODIFICA | La tienda se conserva pero ahora obtiene los productos desde MySQL. | Se mantiene el trabajo previo y se integra con datos reales. |
| **Carrito de compras** | Carrito JavaScript. | SE MANTIENE | Continúa funcionando y controla cantidades según las existencias obtenidas de MySQL. | Mantiene funcionalidad ya desarrollada y se integra con inventario real. |
| **Inventario** | No existía como módulo administrativo completo. | SE AGREGA | Sistema de Gestión de Inventario para registrar, consultar y actualizar artículos. | Es requisito del nuevo escenario. |
| **Backend** | Frontend principalmente estático. | SE AGREGA / SE MODIFICA | PHP procesa operaciones y conexión con MySQL. | Se necesita persistencia y lógica del servidor. |
| **Base de datos** | No era parte integral del frontend desplegado. | SE AGREGA | MySQL ejecutándose dentro de la VM y administrado por el grupo. | Restricción contractual del Proyecto 2. |
| **Acceso móvil** | No era requisito principal. | SE MODIFICA | Diseño responsivo para tienda e inventario. | Los transportistas deben consultar y actualizar inventario desde teléfono. |
| **Nginx** | Se utilizaba para publicar el frontend. | SE MANTIENE Y SE AMPLÍA | Publica tienda y Sistema de Gestión de Inventario con PHP. | Un mismo servidor web atiende ambos módulos con el backend PHP. |
| **Respaldos** | No existía estrategia completa. | SE AGREGA | mysqldump + gzip + almacenamiento local + Azure Blob Storage. | Protege los datos ante pérdidas o fallos. |
| **Automatización** | No existía. | SE AGREGA | cron ejecuta respaldo cada 6 horas. | Elimina la dependencia de una copia manual. |
| **Recuperación ante desastres** | No estaba implementada. | SE AGREGA | RPO 6 horas, RTO 1 hora y restauración real demostrada. | Define y comprueba los objetivos de recuperación del servicio. |
| **Almacenamiento externo** | No existía. | SE AGREGA | Azure Blob Storage. | Copia externa de respaldos fuera de la VM. |
| **Control de versiones** | No formaba parte de esta implementación. | SE AGREGA | Repositorio GitHub del sistema. | Ordena y versiona el código de la solución. |