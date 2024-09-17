<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
?>


<?php
$nombre = $_POST['nombre'];
$direccion = $_POST['direccion'];

// Verifica si el archivo seguimiento_data.json existe
$file = 'seguimiento_data.json';


if (!file_exists($file)) {
    file_put_contents($file, '[]'); // Crea un archivo JSON vacío si no existe
}

// Leer el contenido actual del archivo JSON
$data = json_decode(file_get_contents($file), true);

// Agregar los nuevos datos
$data[] = array('nombre' => $nombre, 'direccion' => $direccion);

// Guardar los datos actualizados en el archivo JSON
if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
    echo "Datos guardados correctamente";
} else {
    echo "Error al guardar los datos";
}
?>
