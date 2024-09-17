<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
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
        const map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM({
                        url: 'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png'
                    })
                })
            ],
            view: new ol.View({
                center: ol.proj.fromLonLat([-71.3103, -41.1335]), // Centro de Bariloche
                zoom: 14,
                maxZoom: 18
            }),
            pixelRatio: window.devicePixelRatio
        });

        const vectorSource = new ol.source.Vector();
        const vectorLayer = new ol.layer.Vector({ source: vectorSource });
        map.addLayer(vectorLayer);

        // Función para actualizar la posición del repartidor en tiempo real
        function actualizarPosicionRepartidor() {
            fetch('ubicacion_repartidor.json')
                .then(response => response.json())
                .then(data => {
                    const lon = data.lon;
                    const lat = data.lat;
                    const origen = [lon, lat];

                    let marcadorRepartidor = vectorSource.getFeatureById('repartidor');
                    if (!marcadorRepartidor) {
                        marcadorRepartidor = new ol.Feature({
                            geometry: new ol.geom.Point(ol.proj.fromLonLat(origen)),
                            name: 'Repartidor'
                        });
                        marcadorRepartidor.setId('repartidor');
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
                    } else {
                        marcadorRepartidor.getGeometry().setCoordinates(ol.proj.fromLonLat(origen));
                    }
                    
                    setTimeout(actualizarPosicionRepartidor, 5000); // Actualizar cada 5 segundos
                })
                .catch(error => console.error('Error al obtener la ubicación:', error));
        }

        // Iniciar la actualización de la posición del repartidor
        actualizarPosicionRepartidor();
    </script>
</body>
</html>
