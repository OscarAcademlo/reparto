<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Obtener los datos de nombre y dirección de la URL
$nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$direccion = isset($_GET['direccion']) ? $_GET['direccion'] : '';

// Verificar que los parámetros estén presentes
if (empty($direccion)) {
    echo "<script>alert('No se ha proporcionado ninguna dirección de destino.');</script>";
    exit;
}

$file_path = 'seguimiento_data.json'; // Archivo JSON donde se guardan los datos

// Cargar los datos del archivo JSON
if (file_exists($file_path)) {
    $data = json_decode(file_get_contents($file_path), true);
    // Buscar los datos del repartidor que coincidan con el nombre y la dirección
    $repartidorEncontrado = false;
    foreach ($data as $entry) {
        if ($entry['nombre'] === $nombre && $entry['direccion'] === $direccion) {
            $repartidorEncontrado = true;
            break;
        }
    }
} else {
    $data = [];
    $repartidorEncontrado = false;
}

// Verificar si se encontraron los datos del repartidor
if (!$repartidorEncontrado) {
    echo "<script>alert('No se pudo encontrar la ubicación del repartidor.');</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://openlayers.org/en/v6.5.0/css/ol.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #d16ba5, #86a8e7, #5ffbf1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            overflow-x: hidden;
        }

        #map {
            height: 70vh;
            width: 100%;
            max-width: 100%;
            display: block;
            margin-top: 20px;
            position: relative;
        }

        .info-container {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Mapa -->
    <div id="map">
        <div class="info-container">
            <span id="distancia">Distancia: Calculando...</span><br>
            <span id="tiempo-estimado">Tiempo estimado de llegada: Calculando...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.js"></script>
    <script src="https://openlayers.org/en/v6.5.0/build/ol.js"></script>
    <script>
        const nombre = '<?php echo $nombre; ?>';
        const direccion = '<?php echo $direccion; ?>';

        if (!direccion) {
            alert('No se ha proporcionado ninguna dirección de destino.');
        } else {
            // Configuración del mapa
            const map = new ol.Map({
                target: 'map',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat([-71.3103, -41.1335]), // Centro de Bariloche
                    zoom: 12,
                    maxZoom: 18
                }),
                pixelRatio: window.devicePixelRatio
            });

            const vectorSource = new ol.source.Vector();
            const vectorLayer = new ol.layer.Vector({ source: vectorSource });
            map.addLayer(vectorLayer);

            // Función para mostrar la ubicación del repartidor y trazar la ruta
            function mostrarRepartidorYDestino() {
                // Obtener la ubicación actual del repartidor desde el archivo JSON
                fetch('seguimiento_data.json')
                    .then(response => response.json())
                    .then(data => {
                        const repartidor = data.find(entry => entry.nombre === nombre && entry.direccion === direccion);
                        
                        if (!repartidor) {
                            alert('No se pudo encontrar la ubicación del repartidor.');
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(function(position) {
                            const lon = position.coords.longitude;
                            const lat = position.coords.latitude;
                            const origen = [lon, lat];

                            // Mostrar marcador del repartidor
                            const marcadorRepartidor = new ol.Feature({
                                geometry: new ol.geom.Point(ol.proj.fromLonLat(origen)),
                                name: 'Repartidor'
                            });
                            marcadorRepartidor.setStyle(new ol.style.Style({
                                image: new ol.style.Circle({
                                    radius: 7,
                                    fill: new ol.style.Fill({ color: 'red' }),
                                    stroke: new ol.style.Stroke({ color: 'white', width: 2 })
                                }),
                                text: new ol.style.Text({
                                    text: 'Repartidor',
                                    offsetY: -15,
                                    fill: new ol.style.Fill({ color: 'red' })
                                })
                            }));
                            vectorSource.addFeature(marcadorRepartidor);

                            // Mostrar marcador del destino
                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}, San Carlos de Bariloche, Argentina`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.length > 0) {
                                        const destino = [parseFloat(data[0].lon), parseFloat(data[0].lat)];

                                        const marcadorDestino = new ol.Feature({
                                            geometry: new ol.geom.Point(ol.proj.fromLonLat(destino)),
                                            name: 'Destino'
                                        });
                                        marcadorDestino.setStyle(new ol.style.Style({
                                            image: new ol.style.Circle({
                                                radius: 7,
                                                fill: new ol.style.Fill({ color: 'blue' }),
                                                stroke: new ol.style.Stroke({ color: 'white', width: 2 })
                                            }),
                                            text: new ol.style.Text({
                                                text: 'Destino',
                                                offsetY: -15,
                                                fill: new ol.style.Fill({ color: 'blue' })
                                            })
                                        }));
                                        vectorSource.addFeature(marcadorDestino);

                                        // Trazar la ruta entre repartidor y destino
                                        fetch(`https://router.project-osrm.org/route/v1/driving/${lon},${lat};${destino[0]},${destino[1]}?overview=full&geometries=geojson`)
                                            .then(response => response.json())
                                            .then(routeData => {
                                                const route = new ol.format.GeoJSON().readFeature(routeData.routes[0].geometry, {
                                                    featureProjection: 'EPSG:3857'
                                                });

                                                route.setId('ruta');
                                                route.setStyle(new ol.style.Style({
                                                    stroke: new ol.style.Stroke({
                                                        color: '#FF5733',
                                                        width: 3
                                                    })
                                                }));
                                                vectorSource.addFeature(route);

                                                // Calcular y mostrar distancia y tiempo estimado
                                                const distancia = routeData.routes[0].distance / 1000;
                                                const velocidadPromedio = 40; // km/h
                                                const tiempoEstimado = (distancia / velocidadPromedio) * 60;

                                                document.getElementById('distancia').textContent = `Distancia: ${distancia.toFixed(2)} km`;
                                                document.getElementById('tiempo-estimado').textContent = `Tiempo estimado de llegada: ${tiempoEstimado.toFixed(0)} minutos`;

                                                // Ajustar la vista del mapa para incluir todos los elementos
                                                map.getView().fit(
                                                    vectorSource.getExtent(),
                                                    { maxZoom: 15, duration: 1000 }
                                                );
                                            })
                                            .catch(error => console.error('Error al calcular la ruta:', error));
                                    } else {
                                        alert('No se pudo encontrar la dirección ingresada.');
                                    }
                                })
                                .catch(error => console.error('Error al buscar la dirección:', error));
                        }, function(error) {
                            alert('Error al obtener la ubicación del repartidor: ' + error.message);
                        });
                    })
                    .catch(error => console.error('Error al leer el archivo JSON:', error));
            }

            // Mostrar el repartidor y destino al cargar la página
            mostrarRepartidorYDestino();
        }
    </script>
</body>
</html>
