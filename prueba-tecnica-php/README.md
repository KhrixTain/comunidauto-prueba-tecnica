# ComunidAuto – Prueba técnica (PHP)

## Enunciado (sección PHP)

Según el enunciado de la prueba técnica:

1) **Listado de autos:** dada una lista estática (marca, modelo, precio), mostrarlos en una **tabla HTML**.  
2) **Filtro por precio:** mostrar solo los autos con precio **menor** a un valor dado (ej: $10.000.000).  
3) **Buscador por marca/modelo:** permitir ingresar un texto (ej: "Ford") y mostrar los autos que contengan esa palabra en la **marca o el modelo**.  

---

## Requisitos explícitos (cubiertos)

- Listado de autos en **tabla HTML**.
- **Filtro por precio**: `precio <= valor`.
- **Buscador por marca/modelo**: texto libre en ambos campos.

## Requisitos implícitos / decisiones técnicas

- **Entorno**: PHP ≥ 8.0; extensiones `intl` (NumberFormatter/Transliterator) y `mbstring`.
- **Estado por URL**: filtros via querystring (`marca_modelo`, `precio_maximo`, `ordenar_por`) con defaults y whitelisting.
- **Búsqueda tolerante**: *case-insensitive* y **sin tildes** (normalización + transliteración).
- **Moneda (ARS)**: formateo sin decimales en backend (`NumberFormatter('es_AR', CURRENCY)`) y en UI (`Intl.NumberFormat`).
- **Ordenamiento**: por precio **ASC/DESC**.

---

## Buenas prácticas seguidas

- **Seguridad de salida**: helper `e()` con `htmlspecialchars` (previene XSS).
- **Manejo de errores robusto**: `set_error_handler` (errores→excepciones), `set_exception_handler` y `register_shutdown_function`; **log** completo y **mensaje genérico** al usuario (sin stack trace). HTTP 500 en fallas.
- **i18n/encoding**: `UTF-8` en todo el flujo (`mb_internal_encoding`, `default_charset`); `intl` para moneda y `Transliterator` para búsquedas.
- **UX de formularios**: input visible de precio con miles formateados + input `hidden` sincronizado (se envía un **entero limpio**).
- **Accesibilidad/UI**: tabla responsive con Bootstrap, `caption`, labels asociados y encabezados de columna.
- **Código mantenible**: funciones puras (`filter_cars`, `order_cars`), **wrappers** para dependencias (`mb_*`, `intl`), constantes y defaults centralizados.
- **Defensivo**: `MAX_PRICE` para acotar entradas; whitelisting en `ordenar_por`; sanitización de parámetros.

---

### Setup rápido

```bash
php -c "php.ini" -S localhost:8080
# abrir http://localhost:8080/
```
