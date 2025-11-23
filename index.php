<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WebGIS dengan Geoserver & LeafletJS</title>

    <!-- BOOTSTRAP 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #ecf0f1;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        /* Floating buttons */
        .floating-btn {
            position: absolute;
            z-index: 9999;
            right: 20px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 22px;
            transition: 0.3s;
        }

        #btnLayers {
            top: 20px;
            background: #3498db;
        }

        #btnLegend {
            top: 90px;
            background: #2ecc71;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            background: #f1c40f;
            color: #2d3436;
        }

        /* PANEL CARD */
        .panel-card {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 9998;
            width: 280px;
            display: none;
            border-left: 4px solid #3498db;
            top: 80px;
            left: 10px;
        }

        .legend-panel {
            border-left: 4px solid #2ecc71;
            top: 280px;
            left: 10px;
        }

        .card-body {
            max-height: 350px;
            overflow-y: auto;
        }

        /* Scrollbar */
        .card-body::-webkit-scrollbar {
            width: 6px;
        }

        .card-body::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <!-- ========== FLOATING BUTTONS ========== -->
    <button id="btnLayers" class="floating-btn shadow">
        <i class="fa fa-layer-group"></i>
    </button>

    <button id="btnLegend" class="floating-btn shadow">
        <i class="fa fa-list"></i>
    </button>

    <!-- ========== LAYER PANEL ========== -->
    <div id="layerPanel" class="card panel-card shadow">
        <div class="card-header bg-primary text-white">
            <strong>Layer Control</strong>
        </div>
        <div class="card-body" id="layerControlContainer">
            <!-- Checkbox layer akan diinject JS -->
        </div>
    </div>

    <!-- ========== LEGEND PANEL ========== -->
    <div id="legendPanel" class="card panel-card legend-panel shadow">
        <div class="card-header bg-success text-white">
            <strong>Legend</strong>
        </div>
        <div class="card-body" id="legendContainer">
            <!-- Legend akan muncul otomatis -->
        </div>
    </div>

    <!-- MAP -->
    <div id="map"></div>

    <!-- BOOTSTRAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- LEAFLET -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <script>
        // =========================
        // MAP SETUP
        // =========================
        var map = L.map("map").setView([-7.732521, 110.402376], 11);

        var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors",
        }).addTo(map);

        // ===============================
        // LAYER PANE (URUTAN LAYER)
        // ===============================

        // polygon paling bawah
        map.createPane('panePolygon');
        map.getPane('panePolygon').style.zIndex = 200;

        // garis di tengah
        map.createPane('paneLine');
        map.getPane('paneLine').style.zIndex = 300;

        // titik paling atas
        map.createPane('panePoint');
        map.getPane('panePoint').style.zIndex = 400;

        // =========================
        // WMS LAYERS
        // =========================
        var layers = {
            
            "Sleman Ibu Kota Kec": L.tileLayer.wms("http://localhost:8080/geoserver/pgweb10/wms", {
                layers: "pgweb10:Sleman_Ibukota_Kec",
                format: "image/png",
                transparent: true,
                pane: 'panePoint'
            }),

            "Jalan SL": L.tileLayer.wms("http://localhost:8080/geoserver/pgweb10/wms", {
                layers: "pgweb10:Jalan_SL",
                format: "image/png",
                transparent: true,
                pane: 'paneLine'
            }),

            "Sleman Admin Kec": L.tileLayer.wms("http://localhost:8080/geoserver/pgweb10/wms", {
                layers: "pgweb10:Sleman_Admin_Kec",
                format: "image/png",
                transparent: true,
                pane: 'panePolygon'
            }),

            "Penggunaan Lahan Sleman 2022": L.tileLayer.wms(
                "https://geoportal.slemankab.go.id/geoserver/wms", {
                    layers: "geonode:3404_50kb_ar_penggunaan_lahan_2022",
                    format: "image/png",
                    transparent: true
                })
        };

        // Add ALL layers by default (bisa diubah)
        Object.values(layers).forEach(l => l.addTo(map));

        // =========================
        // GENERATE LAYER CHECKBOXES
        // =========================
        const layerContainer = document.getElementById("layerControlContainer");

        Object.keys(layers).forEach(name => {
            let div = document.createElement("div");
            div.classList.add("form-check");

            div.innerHTML = `
                <input class="form-check-input" type="checkbox" value="${name}" id="chk_${name}" checked>
                <label class="form-check-label">${name}</label>
            `;

            layerContainer.appendChild(div);

            document.getElementById(`chk_${name}`).addEventListener("change", function() {
                if (this.checked) {
                    layers[name].addTo(map);
                    addLegend(name);
                } else {
                    map.removeLayer(layers[name]);
                    removeLegend(name);
                }
            });
        });

        // =========================
        // LEGEND WMS
        // =========================
        const layerInfo = {
            "Sleman Ibu Kota Kec": {
                url: "http://localhost:8080/geoserver/pgweb10/wms",
                layer: "pgweb10:Sleman_Ibukota_Kec"
            },
            "Jalan SL": {
                url: "http://localhost:8080/geoserver/pgweb10/wms",
                layer: "pgweb10:Jalan_SL"
            },
            "Sleman Admin Kec": {
                url: "http://localhost:8080/geoserver/pgweb10/wms",
                layer: "pgweb10:Sleman_Admin_Kec"
            },
            "Penggunaan Lahan Sleman 2022": {
                url: "https://geoportal.slemankab.go.id/geoserver/wms",
                layer: "geonode:3404_50kb_ar_penggunaan_lahan_2022"
            },
        };

        function getLegendUrl(name) {
            const info = layerInfo[name];
            return `${info.url}?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=${info.layer}`;
        }

        const legendContainer = document.getElementById("legendContainer");

        function addLegend(name) {
            const id = "legend_" + name.replace(/[^a-zA-Z0-9]/g, "_");
            if (document.getElementById(id)) return;

            let div = document.createElement("div");
            div.id = id;
            div.classList.add("mb-3");

            div.innerHTML = `
                <strong>${name}</strong><br>
                <img src="${getLegendUrl(name)}" class="border p-1 mt-1 rounded">
            `;

            legendContainer.appendChild(div);
        }

        function removeLegend(name) {
            const id = "legend_" + name.replace(/[^a-zA-Z0-9]/g, "_");
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // Tambahkan legend default
        Object.keys(layers).forEach(name => addLegend(name));

        // =========================
        // PANEL SHOW/HIDE
        // =========================
        const layerPanel = document.getElementById("layerPanel");
        const legendPanel = document.getElementById("legendPanel");

        document.getElementById("btnLayers").onclick = () => {
            layerPanel.style.display = layerPanel.style.display === "block" ? "none" : "block";
        };

        document.getElementById("btnLegend").onclick = () => {
            legendPanel.style.display = legendPanel.style.display === "block" ? "none" : "block";
        };
    </script>

</body>

</html>