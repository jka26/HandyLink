<!DOCTYPE html>
<html>
<head>
    <title>Map</title>
    <link rel="stylesheet" href="../assets/map.css">
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
        .map-controls {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="map-controls">
        <input id="search" type="text" placeholder="Search location">
        <button onclick="searchLocation()">Search</button>
    </div>
    <div id="map"></div>

    <script>
        let map;
        let markers = [];
        let markerCluster;

        // Initialize the map
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 5.6037, lng: -0.1870 }, // Accra coordinates
                zoom: 12
            });

            // Add click listener to map
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
            });

            // Initialize drawing tools
            const drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.MARKER,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.MARKER,
                        google.maps.drawing.OverlayType.CIRCLE,
                        google.maps.drawing.OverlayType.POLYGON,
                    ]
                }
            });
            drawingManager.setMap(map);

            // Add sample markers (replace with your data)
            const locations = [
                { lat: 5.6037, lng: -0.1870, title: "Helper 1" },
                { lat: 5.6157, lng: -0.1826, title: "Helper 2" },
                // Add more locations
            ];

            // Add markers and enable clustering
            locations.forEach(location => {
                const marker = new google.maps.Marker({
                    position: { lat: location.lat, lng: location.lng },
                    map: map,
                    title: location.title
                });
                markers.push(marker);

                // Add info window
                const infowindow = new google.maps.InfoWindow({
                    content: `<h3>${location.title}</h3><p>Available for work</p>`
                });

                marker.addListener('click', () => {
                    infowindow.open(map, marker);
                });
            });

            // Enable marker clustering
            markerCluster = new MarkerClusterer(map, markers, {
                imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
            });
        }

        // Function to place a new marker
        function placeMarker(location) {
            const marker = new google.maps.Marker({
                position: location,
                map: map
            });
            markers.push(marker);
            
            // Update clusters
            markerCluster.clearMarkers();
            markerCluster.addMarkers(markers);
        }

        // Function to search locations
        function searchLocation() {
            const geocoder = new google.maps.Geocoder();
            const address = document.getElementById('search').value;

            geocoder.geocode({ address: address }, (results, status) => {
                if (status === 'OK') {
                    map.setCenter(results[0].geometry.location);
                    placeMarker(results[0].geometry.location);
                } else {
                    alert('Location not found');
                }
            });
        }

        // Add this function to handle adding new helpers
        function addHelper(helperData) {
            const marker = new google.maps.Marker({
                position: { lat: helperData.lat, lng: helperData.lng },
                map: map,
                title: helperData.name
            });

            const infowindow = new google.maps.InfoWindow({
                content: `
                    <div class="helper-info">
                        <h3>${helperData.name}</h3>
                        <p>Service: ${helperData.service}</p>
                        <p>Rating: ${helperData.rating} ⭐</p>
                        <button onclick="bookHelper('${helperData.id}')">Book Now</button>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infowindow.open(map, marker);
            });

            markers.push(marker);
            markerCluster.clearMarkers();
            markerCluster.addMarkers(markers);
        }

        // Function to get user's current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(pos);
                        placeMarker(pos);
                    },
                    () => {
                        alert("Error: The Geolocation service failed.");
                    }
                );
            } else {
                alert("Error: Your browser doesn't support geolocation.");
            }
        }

        // Function to filter helpers by service type
        function filterHelpers(service) {
            markers.forEach(marker => {
                if (marker.serviceType === service) {
                    marker.setVisible(true);
                } else {
                    marker.setVisible(false);
                }
            });
        }
    </script>

    <!-- Load Google Maps JavaScript API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=drawing,places&callback=initMap" async defer></script>
    <!-- Load MarkerClusterer -->
    <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>

</body>
</html>