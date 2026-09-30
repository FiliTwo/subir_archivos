<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responder(int $estado, array $datos): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$directorio = __DIR__ . DIRECTORY_SEPARATOR . 'archivos';
$tiposPermitidos = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'gif' => ['image/gif'],
    'webp' => ['image/webp'],
    'pdf' => ['application/pdf'],
];
$limiteBytes = 10 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['accion'] ?? '') === 'listar') {
    if (!is_dir($directorio)) {
        responder(200, ['archivos' => []]);
    }

    $lista = [];
    foreach (new DirectoryIterator($directorio) as $elemento) {
        if (!$elemento->isFile()) {
            continue;
        }

        $extension = strtolower($elemento->getExtension());
        if (!isset($tiposPermitidos[$extension])) {
            continue;
        }

        $lista[] = [
            'nombre' => $elemento->getFilename(),
            'tamano' => $elemento->getSize(),
        ];
    }

    usort($lista, static fn(array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']));
    responder(200, ['archivos' => $lista]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['mensaje' => 'Método no permitido.']);
}

if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
    responder(400, ['mensaje' => 'Selecciona un archivo antes de continuar.']);
}

$archivo = $_FILES['archivo'];
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    $mensaje = $archivo['error'] === UPLOAD_ERR_NO_FILE
        ? 'Selecciona un archivo antes de continuar.'
        : 'No se pudo recibir el archivo. Inténtalo de nuevo.';
    responder(400, ['mensaje' => $mensaje]);
}

if ($archivo['size'] <= 0 || $archivo['size'] > $limiteBytes) {
    responder(400, ['mensaje' => 'El archivo debe pesar menos de 10 MB.']);
}

$extension = strtolower(pathinfo(basename((string) $archivo['name']), PATHINFO_EXTENSION));
if (!isset($tiposPermitidos[$extension])) {
    responder(415, ['mensaje' => 'Tipo no permitido. Usa una imagen JPG, PNG, GIF, WEBP o un PDF.']);
}

$nombrePersonalizado = trim((string) ($_POST['nombre'] ?? ''));
if ($nombrePersonalizado === '' || preg_match('/[\/\\\\:*?"<>|\x00-\x1F]/', $nombrePersonalizado) === 1) {
    responder(400, ['mensaje' => 'Escribe un nombre válido para el archivo.']);
}

$nombrePersonalizado = preg_replace('/\.' . preg_quote($extension, '/') . '$/i', '', $nombrePersonalizado) ?? '';
$nombrePersonalizado = trim($nombrePersonalizado, " .\t\n\r\0\x0B");
$cantidadCaracteres = preg_match_all('/./us', $nombrePersonalizado);
if ($nombrePersonalizado === '' || $cantidadCaracteres === false || $cantidadCaracteres > 100 || strlen($nombrePersonalizado) > 230) {
    responder(400, ['mensaje' => 'El nombre está vacío o es demasiado largo.']);
}

$temporal = (string) $archivo['tmp_name'];
if (!is_uploaded_file($temporal)) {
    responder(400, ['mensaje' => 'El archivo recibido no es válido.']);
}

$detector = new finfo(FILEINFO_MIME_TYPE);
$mime = $detector->file($temporal);
if (!in_array($mime, $tiposPermitidos[$extension], true)) {
    responder(415, ['mensaje' => 'El contenido del archivo no coincide con su extensión.']);
}

if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
    responder(500, ['mensaje' => 'No se pudo preparar la carpeta de destino.']);
}

try {
    $nombreGuardado = $nombrePersonalizado . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
} catch (Throwable $error) {
    responder(500, ['mensaje' => 'No se pudo generar un nombre seguro para el archivo.']);
}

if (!move_uploaded_file($temporal, $directorio . DIRECTORY_SEPARATOR . $nombreGuardado)) {
    responder(500, ['mensaje' => 'No se pudo guardar el archivo. Revisa los permisos de la carpeta.']);
}

responder(201, [
    'mensaje' => 'El archivo se subió correctamente.',
    'nombre' => $nombreGuardado,
]);