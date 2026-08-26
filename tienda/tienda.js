/** ==========================================================================
 *  Tienda integrada — carrito de compras (Proyecto 1 adaptado)
 * --------------------------------------------------------------------------
 *  Los productos NO están escritos aquí: se cargan en `window.PRODUCTOS`
 *  desde tienda/index.php (PHP que lee la tabla `articulos` de MySQL).
 *  El carrito funciona en el navegador (lado del cliente) y respeta la
 *  cantidad disponible (`stock`) que viene del inventario.
 * ========================================================================== */

// Datos provistos por el servidor (PHP → MySQL). Forma por artículo:
//   { id, codigo, nombre, descripcion, precio, stock }
const productos = Array.isArray(window.PRODUCTOS) ? window.PRODUCTOS : [];

/** Estado del carrito: [{ id, cantidad, ...restoDelProducto }] */
let carrito = [];

/** ==========================================================================
 *  FORMATEAR PRECIO EN COLONES COSTARRICENSES
 * ========================================================================== */
function formatearColones(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2 });
}

/** ==========================================================================
 *  RENDERIZAR PRODUCTOS EN LA GRILLA
 *  (La grilla se genera en PHP; aquí solo se arman los botones ya servidos.
 *   Esta función ya no es necesaria para poblar la grilla, se conserva para
 *   compatibilidad con la lógica original del carrito.)
 * ========================================================================== */
function renderizarProductos() {
    // La grilla ya viene renderizada desde el servidor (index.php).
    // No re-renderizamos aquí para evitar perder estado.
}

/** ==========================================================================
 *  OBTENER UN PRODUCTO POR SU ID DEL INVENTARIO
 * ========================================================================== */
function buscarProducto(idProducto) {
    return productos.find(p => p.id === idProducto) || null;
}

/** ==========================================================================
 *  OBTENER CUÁNTAS UNIDADES HAY EN EL CARRITO DE UN PRODUCTO
 * ========================================================================== */
function cantidadEnCarrito(idProducto) {
    const item = carrito.find(i => i.id === idProducto);
    return item ? item.cantidad : 0;
}

/** ==========================================================================
 *  AGREGAR PRODUCTO AL CARRITO (respeta el máximo disponible)
 * ========================================================================== */
function agregarAlCarrito(idProducto) {
    const producto = buscarProducto(idProducto);
    if (!producto) return;

    const max = producto.stock;
    if (max <= 0) {
        alert('Este producto no tiene existencias disponibles.');
        return;
    }

    const enCarrito = cantidadEnCarrito(idProducto);
    if (enCarrito >= max) {
        alert(`Solo hay ${max} unidad(es) disponible(s) de "${producto.nombre}".`);
        return;
    }

    const existente = carrito.find(item => item.id === idProducto);
    if (existente) {
        existente.cantidad += 1;
    } else {
        carrito.push({ ...producto, cantidad: 1 });
    }

    renderizarCarrito();

    // Feedback visual sutil en el botón servido por PHP
    const btn = document.querySelector(`.btn-add[data-id="${idProducto}"]`);
    if (btn && !btn.disabled) {
        btn.textContent = '✅ Agregado';
        setTimeout(() => { btn.textContent = '🛒 Agregar al carrito'; }, 800);
    }
}

/** ==========================================================================
 *  CAMBIAR CANTIDAD DE UN PRODUCTO EN EL CARRITO
 *  (no permite subir por encima del stock disponible)
 * ========================================================================== */
function cambiarCantidad(idProducto, delta) {
    const item = carrito.find(i => i.id === idProducto);
    if (!item) return;

    const producto = buscarProducto(idProducto);
    const max = producto ? producto.stock : 0;

    const nuevaCantidad = item.cantidad + delta;

    if (delta > 0 && nuevaCantidad > max) {
        alert(`No puede agregar más unidades; solo hay ${max} disponible(s).`);
        return;
    }

    if (nuevaCantidad <= 0) {
        carrito = carrito.filter(i => i.id !== idProducto);
    } else {
        item.cantidad = nuevaCantidad;
    }

    renderizarCarrito();
}

/** ==========================================================================
 *  ELIMINAR PRODUCTO DEL CARRITO
 * ========================================================================== */
function eliminarDelCarrito(idProducto) {
    carrito = carrito.filter(i => i.id !== idProducto);
    renderizarCarrito();
}

/** ==========================================================================
 *  VACIAR EL CARRITO
 * ========================================================================== */
function vaciarCarrito() {
    carrito = [];
    renderizarCarrito();
}

/** ==========================================================================
 *  CALCULAR TOTAL DEL CARRITO
 * ========================================================================== */
function calcularTotal() {
    return carrito.reduce((total, item) => total + Number(item.precio) * item.cantidad, 0);
}

/** ==========================================================================
 *  RENDERIZAR EL CARRITO
 * ========================================================================== */
function renderizarCarrito() {
    const container = document.getElementById('cart-items');
    const totalSpan = document.getElementById('cart-total');

    if (!container || !totalSpan) return;

    if (carrito.length === 0) {
        container.innerHTML = '<p class="cart-empty">Tu carrito está vacío 🛒</p>';
        totalSpan.textContent = formatearColones(0);
        return;
    }

    container.innerHTML = carrito.map(item => {
        const max = item.stock;
        const enMax = item.cantidad >= max;
        return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nombre}</div>
                    <div class="cart-item-detail">${formatearColones(item.precio)} c/u · disp. ${max}</div>
                </div>
                <div class="cart-item-qty">
                    <button onclick="cambiarCantidad(${item.id}, -1)" title="Quitar uno">−</button>
                    <span>${item.cantidad}</span>
                    <button onclick="cambiarCantidad(${item.id}, 1)" ${enMax ? 'disabled' : ''} title="Agregar uno">+</button>
                </div>
                <div style="font-weight:700;font-size:0.85rem;color:var(--color-secundario);white-space:nowrap;">
                    ${formatearColones(Number(item.precio) * item.cantidad)}
                </div>
                <button class="btn-remove-item" onclick="eliminarDelCarrito(${item.id})" title="Eliminar">✕</button>
            </div>
        `;
    }).join('');

    totalSpan.textContent = formatearColones(calcularTotal());
}

/** ==========================================================================
 *  INICIALIZAR LA TIENDA
 * ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    renderizarProductos();
    renderizarCarrito();

    const btnVaciar = document.getElementById('clear-cart-btn');
    if (btnVaciar) {
        btnVaciar.addEventListener('click', vaciarCarrito);
    }
});