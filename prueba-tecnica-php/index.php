<?php
declare(strict_types=1);
use NumberFormatter as IntlNumberFormatter;
use Transliterator as IntlTransliterator;

//#################
// Manejo de errores (para evitar stack traces al usuario)
//#################

// Registrar errores pero NO mostrarlos
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

ini_set('log_errors', '1');

// Respuesta genérica para el usuario
function render_generic_error(): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<h1>Error interno</h1><p>Algo salió mal. Intentalo más tarde.</p>';
}

// Convierte errores en excepciones (evita avisos sueltos en pantalla)
set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false; // respeta @silencio
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Captura excepciones no controladas y NO muestra trazas
set_exception_handler(function (Throwable $e): void {
    error_log($e);            // Log completo (incluye stack trace)
    render_generic_error();   // Mensaje genérico al usuario
});

// Captura errores fatales al finalizar el script
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log(sprintf('FATAL: %s in %s:%d', $err['message'], $err['file'], $err['line']));
        render_generic_error();
    }
});


//#################
// Configuraciones
//#################

// Para trabajar con UTF-8
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

const MAX_PRICE = 1_000_000_000_000;
$default_filters = [
    'marca_modelo' => '',
    'precio_maximo' => null,
    'ordenar_por' => 'precio-menor-mayor'
];
$sort_map = [
    'precio-menor-mayor',
    'precio-mayor-menor',
];


//#################
// Wrappers (para no depender de las extensiones seleccionadas)
//#################
function trim_wrapper(string $str): string {
    return trim($str);
}

function str_to_lower_wrapper(string $str): string {
    return mb_strtolower($str);
}

function transliterate_wrapper(string $str): string {
    $tr = IntlTransliterator::create('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC');
    return $tr->transliterate($str);
}

function int_ars_formatter_wrapper(int $value): string {
    $fmt = new IntlNumberFormatter('es_AR', IntlNumberFormatter::CURRENCY);
    $fmt->setAttribute(IntlNumberFormatter::FRACTION_DIGITS, 0);
    return $fmt->formatCurrency($value, 'ARS');
}

function str_pos_wrapper(string $str, string $substr): false | int {
    return mb_strpos($str, $substr);
}

//#################
// Utilidades
//#################

// Función para escapar carácteres
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

// Función para normalizar carácteres, pasa a minúsculas y elimina acentuaciones y diáresis entre otras cosas
function normalize(string $text): string {
    $text = str_to_lower_wrapper(trim_wrapper($text));
    return transliterate_wrapper($text);
}

// Función para verificar si un string contiene a otro
function str_contains_normalized(string $str, string $substr): bool {
    if($substr === '') return true;
    $str_normalized = normalize($str);
    $substr_normalized = normalize($substr);
    return str_pos_wrapper($str_normalized, $substr_normalized) !== false;
}

//Función para formatear valores numéricos a formato moneda
function format_money_ars(int $amount): string {
    return int_ars_formatter_wrapper($amount);
}

//Función para obtener y validar los valores de los query params
function get_filters(array $query, array $default_filters, array $sort_map, int $max_price = MAX_PRICE ): array {
    $marca_modelo = isset($query['marca_modelo']) ? trim_wrapper((string)$query['marca_modelo']) : '';
    $ordenar_por = null;
    $precio_maximo = null;
    if(isset($query['ordenar_por']) && in_array($query['ordenar_por'], $sort_map, true)) {
        $ordenar_por = $query['ordenar_por'];
    } else {
        $ordenar_por = $default_filters['ordenar_por'];
    }
    if(isset($query['precio_maximo'])) {
        $precio_maximo = filter_var($query['precio_maximo'], FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 0,
                'max_range' => $max_price
            ]
        ]);
        if($precio_maximo === false) $precio_maximo = null;
    }
    return [
        'marca_modelo' => $marca_modelo,
        'precio_maximo' => $precio_maximo,
        'ordenar_por' => $ordenar_por
    ];
}

function filter_cars(array $cars, string $marcaModelo, int|null $precioMaximo, array $default_filters): array {
    $filtered = array_filter($cars, function($car) use($marcaModelo, $precioMaximo, $default_filters): bool {
        $matchesMarcaModelo = $marcaModelo === $default_filters['marca_modelo'] 
                                || str_contains_normalized($car['marca'], $marcaModelo) 
                                || str_contains_normalized($car['modelo'], $marcaModelo);
        $matchesPrecioMaximo = $precioMaximo === $default_filters['precio_maximo'] || $car['precio'] <= $precioMaximo;
        return $matchesMarcaModelo && $matchesPrecioMaximo;
    });
    return $filtered;
}

function order_cars(array $cars, string $ordenarPor): array {
    $asc = 'precio-menor-mayor' === $ordenarPor;
    $col = array_column($cars, 'precio');
    array_multisort($col, $asc ? SORT_ASC : SORT_DESC, $cars);
    return $cars;
};

//#################
// Datos
//#################

//Conjunto de datos de pruebas (solicitado a chatGPT para ahorrar tiempo)
$cars = [
  ['marca' => 'Chevrolet',  'modelo' => 'Onix 1.0T LT',          'precio' => 25560900],
  ['marca' => 'Toyota',     'modelo' => 'Yaris Hatchback XS',    'precio' => 26721000],
  ['marca' => 'Fiat',       'modelo' => 'Cronos 1.3 Like',       'precio' => 27819000],
  ['marca' => 'Peugeot',    'modelo' => '208 1.6 Active MT',     'precio' => 28390000],
  ['marca' => 'Volkswagen', 'modelo' => 'Polo Track',            'precio' => 25990000],
  ['marca' => 'Renault',    'modelo' => 'Logan Intens 1.6',      'precio' => 31200000],
  ['marca' => 'Ford',       'modelo' => 'Ka SE 1.5',             'precio' => 24500000],
  ['marca' => 'Nissan',     'modelo' => 'Versa Sense MT',        'precio' => 31450000],
  ['marca' => 'Honda',      'modelo' => 'City LX',               'precio' => 39800000],
  ['marca' => 'Chevrolet',  'modelo' => 'Cruze LT AT',           'precio' => 45200000],
  ['marca' => 'Toyota',     'modelo' => 'Corolla XLI 2.0',       'precio' => 54500000],
  ['marca' => 'Citroën',    'modelo' => 'C3 Live Pack',          'precio' => 28900000],
  ['marca' => 'Citroën',    'modelo' => 'C4 Cactus Feel',        'precio' => 38900000],
  ['marca' => 'Peugeot',    'modelo' => '408 Allure',            'precio' => 61200000],
  ['marca' => 'Volkswagen', 'modelo' => 'Vento 1.4 TSI',         'precio' => 68700000],
  ['marca' => 'Kia',        'modelo' => 'Cerato FE 2.0',         'precio' => 63900000],
  ['marca' => 'Hyundai',    'modelo' => 'Accent GLS',            'precio' => 61500000],
  ['marca' => 'Chery',      'modelo' => 'Arrizo 5 Comfort',      'precio' => 35200000],
  ['marca' => 'Audi',       'modelo' => 'A3 35 TFSI',            'precio' => 145000000],
  ['marca' => 'BMW',        'modelo' => '320i SportLine',        'precio' => 185000000],
];

//#################
// Controlador
//#################

[
    'marca_modelo' => $marcaModelo,
    'precio_maximo' => $precioMaximo,
    'ordenar_por' => $ordenarPor
] = get_filters($_GET, $default_filters, $sort_map);

$filtered_result = filter_cars($cars, $marcaModelo, $precioMaximo, $default_filters);
$ordered_result = order_cars($filtered_result, $ordenarPor);
$total = count($ordered_result);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComunidAuto | Prueba técnica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <main class="container p-4">
        <header class="mb-3">
            <h1 class="h3">Autos disponibles</h1>
            <p class="text-body-secondary">Filtro de automóviles por marca/modelo y/o precio.</p>
        </header>
        <section class="card">
            <div class="card-body">
                <form id="filter" method="GET" class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="marca_modelo_input" class="form-label">Buscar por marca o modelo</label>
                        <input type="search" name="marca_modelo" id="marca_modelo_input" class="form-control" autocomplete="off" spellcheck="false" value="<?= e($marcaModelo) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="precio_maximo_input" class="form-label">Precio máximo (ARS)</label>
                        <input type="search" id="precio_maximo_input" class="form-control" placeholder="Ej: 10.000.000" inputmode="numeric" value="<?= $precioMaximo !== null ? e(format_money_ars($precioMaximo)) : '' ?>">
                        <input type="hidden" id="precio_maximo_hidden" name="precio_maximo" value="<?= $precioMaximo !== null ? (int)$precioMaximo : '' ?>">
                    </div>
                    <div class="col-12 col-md-2 d-grid g-2">
                        <button type="submit" class="btn btn-primary btn-sm">Aplicar filtros</button>
                        <a href="/" class="btn btn-secondary btn-sm mt-2">Limpiar</a>
                    </div>
                </form>
                <div class="row g-3 mb-3 align-items-end">
                    <p class="col-12 col-md-8">
                        <span class="badge rounded-pill text-bg-primary"><?= e((string)$total) ?></span> <?= $total === 1 ? 'automóvil está siendo mostrado' : 'automóviles están siendo mostrados' ?>.
                    </p>
                    <div class="col-12 col-md-4">
                        <label for="ordenar_por" class="form-label">Ordenar por</label>
                        <select name="ordenar_por" id="ordenar_por" form="filter" class="form-select" onchange="this.form.requestSubmit()">
                            <option value="precio-menor-mayor" <?= $ordenarPor === 'precio-menor-mayor' ? 'selected' : '' ?>>Menor precio</option>
                            <option value="precio-mayor-menor" <?= $ordenarPor === 'precio-mayor-menor' ? 'selected' : '' ?>>Mayor precio</option>
                        </select>
                    </div>
                </div>
                <?php if($total === 0): ?>
                <div class="alert alert-warning" role="alert">
                    No se han encontrado automóviles con los filtros aplicados.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <caption>Listado de vehículos</caption>
                        <thead>
                            <tr>
                                <th class="text-nowrap" scope="col">Marca</th>
                                <th class="text-nowrap" scope="col">Modelo</th>
                                <th class="text-nowrap text-end" scope="col">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ordered_result as $car): ?>
                                <tr>
                                    <td class="text-nowrap"><?= e($car['marca']) ?></td>
                                    <td class="text-nowrap"><?= e($car['modelo']) ?></td>
                                    <td class="text-end text-nowrap"><?= e(format_money_ars($car['precio'])) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php endif ?>

            </div>
        </section>
    </main>
    <script>
        (() => {
            const visible = document.getElementById('precio_maximo_input');
            const hidden  = document.getElementById('precio_maximo_hidden');
            const fmt = new Intl.NumberFormat('es-AR');

            const onlyDigits = s => s.replace(/\D+/g, ''); // solo conserva los dígitos
            const sync = () => {
                const digits = onlyDigits(visible.value);
                hidden.value = digits ? String(parseInt(digits, 10)) : '';
                visible.value = digits ? fmt.format(parseInt(digits, 10)) : '';
            };

            visible.addEventListener('input', sync);
            visible.addEventListener('blur', sync);

            // estado inicial
            sync();
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>