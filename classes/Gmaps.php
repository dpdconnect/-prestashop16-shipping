<?php

namespace DpdConnect\classes;

use Configuration;

class Gmaps
{

    public function getGeoData($postal_code, $isoCode)
    {
        $gmapsKey = Configuration::get('gmaps_server_key');

        $data = urlencode('country:' . $isoCode . '|postal_code:' . $postal_code);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key=". $gmapsKey . "&components=". $data . '&sensor=false';
        $source = file_get_contents($url);

        $gmapsData = json_decode($source);

        $LATITUDE = $gmapsData->results[0]->geometry->location->lat;
        $LONGITUDE = $gmapsData->results[0]->geometry->location->lng;
        return [
            'longitude' => $LONGITUDE,
            'latitude' => $LATITUDE
        ];
    }
}
