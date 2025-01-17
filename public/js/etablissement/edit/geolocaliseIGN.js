function geolocaliseIGN(idModal, options) {

    if($(idModal+' .modal-body').scrollTop() !== 0) {
        $(idModal+' .modal-body').scrollTop(0);
    }

    if (idModal.includes('ajout')) {
        $(idModal+' input').val('');
        $(idModal+" input[name='voie_ac'], "+idModal+" input[name='numero'], "+idModal+" input[name='complement']").val("").attr("disabled", true).blur();
        $("span.result").text("Inconnu");
    }

    $(`${idModal} #${options.geo_container_id}`).css('visibility', 'hidden');

    // Gestion de l'événement de clic sur le bouton de géolocalisation
    $(idModal + ' #geolocme').off('click').click(function () {
        if (!$(this).prop('disabled')) {
            geocodeAndShowMap(idModal, options);
        } else {
            console.warn("Le bouton de géolocalisation est désactivé.");
        }    });

    // Initialise la carte si nécessaire
    initMapViewer(idModal, options);
}

function initMapViewer(idModal, options) {
    const $geoContainer = $(`${idModal} #${options.geo_container_id}`);
    const viewportCount = $geoContainer.find('.ol-viewport').length;

    if (viewportCount === 0) {
        $(idModal + ' #geolocme').attr('disabled', true);
        $(idModal+' #geolocme_nominatim').attr('disabled', true);

        // Initialise la carte avec les options fournies
        viewer = initViewer(
            `${options.geo_container_id}`,
            options.key_ign,
            [options.default_lon, options.default_lat],
            '<b>Centre par défaut</b>',
            options.autoconf_path
        );


        // Ajoute les couches utilisateur
        viewer = addUserLayers(viewer, options.couches_cartographiques);

        // Gestion après le chargement de la carte
        viewer.listen('mapLoaded', function() {
            var fsControl = new ol.control.FullScreen({});
            viewer.getLibMap().addControl(fsControl);

              // Masque les éléments de la carte par défaut
              $('.ol-overlay-container').css('display', 'none');
              $('div[id^=GPtoolbox-measure-main-]').css('display', 'none');
              $('.ol-rotate').css('display', 'none');

              // Active les boutons de géolocalisation
            $(idModal + ' #geolocme').removeAttr('disabled');
            $(idModal+' #geolocme_nominatim').removeAttr('disabled');



            if (idModal.includes('edit')) {
                geocodeAndShowMap(idModal, options);
            }
        });

         // Gestion de la rotation de la carte
         viewer.listen('azimuthChanged', function () {
            viewer.getAzimuth() === 0 ? $('.ol-rotate').css('display', 'none') : $('.ol-rotate').css('display', 'block');
        });

        // Ajoute un marqueur à l’emplacement cliqué sur la carte
        viewer.getLibMap().on('singleclick', function (evt) {
            const lonlat = updateCoordinates(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            putMarkerAt(viewer.getLibMap(), lonlat);
        });
    } 
}

function geocodeAndShowMap(idModal, options) {
    let adresse = "";

    // Vérifie quel onglet est actif (BDD ou API) pour obtenir l'adresse
    if ($(idModal).find('#api').is(':visible') || $(idModal).find('#apiedit').is(':visible')) {
        adresse = $(idModal + " input[name='adresse_complete']").val().trim();
        if (!adresse) {
            $("span.result").text("Adresse complète manquante");
            return false;
        }
    }  else { 
        var numero = $(idModal+" input[name='numero']").val().trim();
        var voie = $(idModal+" input[name='voie_ac']").val().trim();
        var codepostal = $(idModal+" input[name='code_postal']").val();
        var commune = $(idModal+" input[name='commune_ac']").val().replace(/\(.*\)/g, '');

        if (!commune) {
            $("span.result").text("Pas de commune renseignée");
            return false;
        }

        if (!voie) {
            $("span.result").text("Pas de voie renseignée");
            return false;
        }

        if (numero) {
            adresse += numero + ", ";
        }

        var regExp = /\(([^)]+)\)/;
        var matches = regExp.exec(voie);

        if (matches) {
            commune = matches[1];
            voie = voie.split(regExp)[0].trim();
        }

        adresse += voie + ", " + codepostal +  ", " + commune;
    }
    console.log("Adresse pour géolocalisation : ", adresse);


    // Démarre la géolocalisation
    $("span.result").text("Géolocalisation en cours...");
    geocodeWithJsAutoconf(
        options.geo_container_id,
        adresse,
        'StreetAddress',
        'EPSG:4326',
        viewer
    );

    return false;
}
