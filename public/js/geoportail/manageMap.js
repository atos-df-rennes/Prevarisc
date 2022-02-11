document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage de l'icône de visibilité
    $('span > i').on('click', function() {
        if ($(this).hasClass('icon-eye-open')) {
            this.setAttribute('title', 'Couche non visible')
            $(this).closest('div').next().val(1)
        } else {
            this.setAttribute('title', 'Couche visible')
            $(this).closest('div').next().val(0)
        }

        $(this).toggleClass('icon-eye-close icon-eye-open')
    })
})

function initViewer(divId, ignKeys, center, description, autoconfPath) {
    viewer = Gp.Map.load(
        divId, // identifiant du conteneur HTML
        {
            apiKey : ignKeys,
            configUrl : autoconfPath,
            // chargement de la cartographie en 2D
            viewMode : "2d",
            // niveau de zoom de la carte (de 1 à 21)
            zoom : 16,
            // centrage de la carte
            center : {
                x : center[0],
                y : center[1],
                projection : "EPSG:4326"
            },
            // Outils additionnels à proposer sur la carte
            controlsOptions : {
                "layerSwitcher" : {},
                "search" : {},
                "orientation" : {},
                "graphicscale" : {},
                "graticule" : {},
                "length" : {},
                "area" : {},
                "azimuth" : {}
            },
            markersOptions : [{
                content : description
            }]
        }
    );

    return viewer;
}

function addUserLayers(viewer, ignKey, layers) {
    layers.forEach(function(layer) {
        switch (layer.TYPE_COUCHECARTO) {
            case 'WMS':
                addWmsLayer(viewer, layer)
                break
            case 'WMTS':
                addWmtsLayer(viewer, layer, ignKey)
                break
            default:
                console.error('Type de couche non supporté: ' + layer.TYPE_COUCHECARTO)
                return
        }
    })

    return viewer;
}

function addWmsLayer(viewer, wmsLayer) {
    const source = new ol.source.TileWMS({
        url: wmsLayer.URL_COUCHECARTO,
        params: {
            'LAYERS': wmsLayer.LAYERS_COUCHECARTO,
            'FORMAT': wmsLayer.FORMAT_COUCHECARTO,
            'TILED': true
        }
    })

    const layer = new ol.layer.Tile({
        source: source,
        visible: wmsLayer.TRANSPARENT_COUCHECARTO === 1 ? false : true
    })

    if (wmsLayer.ORDRE_COUCHECARTO !== null) {
        layer.setZIndex(wmsLayer.ORDRE_COUCHECARTO)
    }

    viewer.getLibMap().addLayer(layer);

    // On renomme les couches utilisateurs
    $('.GPlayerName').eq(-(viewer.getLibMap().getLayers().getLength())).text(wmsLayer.NOM_COUCHECARTO)
    .attr('title', wmsLayer.NOM_COUCHECARTO)
}

function addWmtsLayer(viewer, wmtsLayer, ignKey) {
    const wmtsCapabilities = getCapabilities(ignKey, 'wmts')

    // Projection EPSG:3857
    const resolutions = [
            156543.03392804103,
            78271.5169640205,
            39135.75848201024,
            19567.879241005125,
            9783.939620502562,
            4891.969810251281,
            2445.9849051256406,
            1222.9924525628203,
            611.4962262814101,
            305.74811314070485,
            152.87405657035254,
            76.43702828517625,
            38.218514142588134,
            19.109257071294063,
            9.554628535647034,
            4.777314267823517,
            2.3886571339117584,
            1.1943285669558792,
            0.5971642834779396,
            0.29858214173896974,
            0.14929107086948493,
            0.07464553543474241
    ];
    
    // Données issues du getCapabilities correspondant à la couche renseignée par l'utilisateur
    // Permet d'avoir des informations complémentaires non renseignées par l'utilisateur pour l'ajout de la couche
    const wmtsLayerCapability = wmtsCapabilities.find(wmtsCapability => wmtsCapability.internalName === wmtsLayer.LAYERS_COUCHECARTO)

    const source = new ol.source.WMTS({
        url: wmtsLayer.URL_COUCHECARTO,
        layer: wmtsLayer.LAYERS_COUCHECARTO,
        matrixSet: wmtsLayerCapability.matrixSet,
        format: wmtsLayer.FORMAT_COUCHECARTO,
        tileGrid: new ol.tilegrid.WMTS({
            origin: wmtsLayerCapability.origin,
            resolutions: resolutions,
            matrixIds: wmtsLayerCapability.matrixIds,
        }),
        style: wmtsLayerCapability.style
    })

    const layer = new ol.layer.Tile({
        source: source,
        visible: wmtsLayer.TRANSPARENT_COUCHECARTO === 1 ? false : true
    })

    if (wmtsLayer.ORDRE_COUCHECARTO !== null) {
        layer.setZIndex(wmtsLayer.ORDRE_COUCHECARTO)
    }

    viewer.getLibMap().addLayer(layer);

    // On renomme les couches utilisateurs
    $('.GPlayerName').eq(-(viewer.getLibMap().getLayers().getLength())).text(wmtsLayer.NOM_COUCHECARTO)
    .attr('title', wmtsLayer.NOM_COUCHECARTO)
}

function getCapabilities(ignKey, format) {
    let parser = null
    const baseFormat = format.split(' ')[0]
    let urlToCall = 'https://wxs.ign.fr/' + ignKey + '/geoportail/{formatUrl}?SERVICE=' + baseFormat + '&REQUEST=GetCapabilities{options}'

    switch (format) {
        case 'wmts':
            parser = new ol.format.WMTSCapabilities()
            urlToCall = urlToCall.replace('{formatUrl}', 'wmts').replace('{options}', '')
            break
        case 'wms raster':
            parser = new ol.format.WMSCapabilities()
            urlToCall = urlToCall.replace('{formatUrl}', 'r/wms').replace('{options}', '&VERSION=1.3.0')
            break
        case 'wms vecteur':
            parser = new ol.format.WMSCapabilities()
            urlToCall = urlToCall.replace('{formatUrl}', 'v/wms').replace('{options}', '&VERSION=1.3.0')
            break
        default:
            console.error('Format non supporté: ' + format + '\nLes formats supportés sont: WMTS / WMS Raster / WMS Vecteur')
            return
    }

    let layersToReturn = []

    $.ajax({
        url: urlToCall,
        type: 'get',
        async: false,
        success: function (result) {
            const parsedResult = parser.read(result)

            let contents = ''
            let layerUrl = ''
            let layerOrigins = ''
            let contentMatrixSetIds =  []

            if (format === 'wmts') {
                contents = parsedResult.Contents
                layerUrl = parsedResult.OperationsMetadata.GetCapabilities.DCP.HTTP.Get[0].href.slice(0, -1)

                const contentMatrixSet = contents.TileMatrixSet
                const matrixSetToUse = contentMatrixSet.find(matrixSet => matrixSet.Identifier === 'PM')
                layerOrigins = matrixSetToUse.TileMatrix[0].TopLeftCorner

                for (let i = 0; i < matrixSetToUse.TileMatrix.length; i++) {
                    contentMatrixSetIds.push(i)
                }

                layerOrigins.forEach(function (part, index) {
                    this[index] = Math.trunc(part)
                }, layerOrigins)
            } else {
                contents = parsedResult.Capability.Layer
                layerUrl = parsedResult.Capability.Request.GetCapabilities.DCPType[0].HTTP.Get.OnlineResource.slice(0, -1)
            }

            const contentLayers = contents.Layer
            if (contentLayers === undefined) {
                return
            }

            let layers = []
            let layerFormat = null
            contentLayers.forEach(function (layer) {
                if (layer.Style === undefined && format !== 'wms vecteur') {
                    return
                } else if (layer.Style === undefined && format === 'wms vecteur') {
                    layerFormat = 'image/png'
                }

                let obj = {
                    // NOM_COUCHECARTO
                    name: layer.Title,
                    // TYPE_COUCHECARTO
                    type: baseFormat.toUpperCase(),
                    // URL_COUCHECARTO
                    url: layerUrl
                }

                if (format === 'wmts') {
                    // LAYERS_COUCHECARTO
                    obj.internalName = layer.Identifier
                    // FORMAT_COUCHECARTO
                    obj.format = layer.Format[0]
                    obj.style = layer.Style[0].Identifier
                    obj.matrixSet = layer.TileMatrixSetLink[0].TileMatrixSet
                    obj.origin = layerOrigins
                    obj.matrixIds = contentMatrixSetIds
                } else {
                    // LAYERS_COUCHECARTO
                    obj.internalName = layer.Name
                    // FORMAT_COUCHECARTO
                    obj.format = layerFormat !== null ? layerFormat : layer.Style[0].LegendURL[0].Format
                }

                layers.push(obj)
            })

            layersToReturn = layers
        }
    })

    return layersToReturn
}

function putMarkerAt(viewer, center, nbCouches) {
    // Si on a déjà un marker, on le retire
    if (viewer.getLayers().getLength() != nbCouches) {
        var toRemove = viewer.getLayers().item(viewer.getLayers().getLength()-1);
        viewer.removeLayer(toRemove);
    }

    var coordinates = ol.proj.fromLonLat([center[0],center[1]]);
    var point = new ol.geom.Point(coordinates);
    var marker = new ol.Feature(point);

    // On crée le nouveau marker aux coordonnées indiquées
    var vectorSource = new ol.source.Vector({
        features: [marker]
    });
    var styleMarker = new ol.style.Style({
        image: new ol.style.Icon({
            src: "/images/red-dot.png"
        })
    });
    var vectorLayer = new ol.layer.Vector({
        source: vectorSource,
        style: styleMarker
    });
    viewer.addLayer(vectorLayer);

    // On renomme la couche
    $('.GPlayerName').eq(-(viewer.getLayers().getLength())).text('Position du marqueur');
}

function updateCoordinates(center, sourceProj, destProj) {
    var lonlat = new ol.proj.transform(center, sourceProj, destProj);
    $("input[name='lon']").val(lonlat[0]);
    $("input[name='lat']").val(lonlat[1]);

    return lonlat;
}

function geocodeWithJsAutoconf(apiKey, adresse, filterOptionsType, projection, viewer, nbCouches) {
    Gp.Services.geocode({
        apiKey: apiKey,
        location: adresse,
        filterOptions: [{
            type: filterOptionsType
        }],
        srs: projection,
        onSuccess: function(t) {
            var newCenter = {
                x: t.locations[0].position.y,
                y: t.locations[0].position.x,
                projection: projection
            };
            viewer.setCenter(newCenter);
            $("span.result").text("Géolocalisée IGN");
            $('#geoportail-container').css('visibility', 'visible');
            // Changement des coordonnées et du marker 
            lonlat = updateCoordinates([viewer.getCenter().x, viewer.getCenter().y], 'EPSG:3857', 'EPSG:4326');
            putMarkerAt(viewer.getLibMap(), lonlat, nbCouches);
        },
        onFailure: function() {
            console.log('Erreur du service de géocodage ! Veuillez réessayer');
        }
    });
}