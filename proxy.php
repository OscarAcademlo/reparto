<?php
header('Content-Type: application/json');

// Verifica si hay una consulta 'q' proporcionada en la solicitud
if (isset($_GET['q'])) {
    $query = urlencode($_GET['q']);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}";

    // Utiliza cURL para reenviar la solicitud a Nominatim
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'tu-proyecto/1.0 (https://reparto.oscarsoft.me; tu-email@example.com)');
    $response = curl_exec($ch);
    curl_close($ch);

    // Devuelve la respuesta JSON al cliente
    echo $response;
} else {
    echo json_encode(["error" => "No query provided."]);
}
?>
