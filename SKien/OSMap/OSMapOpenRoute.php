<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class to determine route for given start/end geolocations.
 * using openroute service REST API
 *
 * @link https://openrouteservice.org
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapOpenRoute
{
    /** base url for all REST services of OpenRoute API     */
    protected const REST_API_URL = 'https://api.openrouteservice.org/v2/';

    /** vehicle types    */
    public const __VT = '';
    /** vehicle type: car    */
    public const VT_CAR = 'driving-car';
    /** vehicle type: hgv (heavy goods vehicle ... > 3.5t)   */
    public const VT_HGV = 'driving-hgv';
    /** vehicle type:   'normal' bicycle        */
    public const VT_BICYCLE = 'cycling-regular';
    /** vehicle type:   roadbike                */
    public const VT_ROAD_BIKE = 'cycling-road';
    /** vehicle type:   mountainbike            */
    public const VT_MTB = 'cycling-mountain';
    /** vehicle type:   electric bike           */
    public const VT_E_BIKE = 'cycling-electric';
    /** vehicle type:   'normal' walking        */
    public const VT_WALKING = 'foot-walking';
    /** vehicle type:   hiking                  */
    public const VT_HIKING = 'foot-walking';
    /** vehicle type:   wheelchair              */
    public const VT_WHEELCHAIR = 'wheelchair';

    /** method to calc route    */
    public const __METHOD = '';
    /** find shortest route  */
    public const FASTEST = 'shortest';
    /** find fastest route   */
    public const SHORTEST = 'fastest';

    /** format of the instructions    */
    public const __FORMAT = '';
    /** instructions as plain text   */
    public const IF_TEXT = 'text';
    /** instructions as html     */
    public const IF_HTML = 'html';

    /** unit of the distance values    */
    public const __DIST = '';
    /** distances in m (meter)   */
    public const UNITS_M = 'm';
    /** distances in km (kilometer)  */
    public const UNITS_KM = 'km';
    /** distances in miles   */
    public const UNITS_MILES = 'mi';

    /** format of the response    */
    public const __RESPONSE = '';
    /** format response as json  */
    public const FMT_JSON = 'json';
    /** format response as geojson   */
    public const FMT_GEOJSON = 'geojson';
    /** format response as gpx   */
    public const FMT_GPX = 'gpx';

    /** @var string     API Key from https://openrouteservice.org/dev   */
    protected string $strKey  = '';
    /** @var string     language (short ISO 3166-3; NOT all languages are supported!)       */
    protected string $strLanguage = '';
    /** @var string     format of the response      */
    protected string $strFormat      = self::FMT_JSON;
    /** @var string     vehicle type        */
    protected string $strVehicleType = self::VT_CAR;
    /** @var string     preference      */
    protected string $strPreference  = self::FASTEST;
    /** @var bool       generate instructions       */
    protected bool $bInstructions  = false;
    /** @var string     format for instruction      */
    protected string $strInstructionFormat   = self::IF_TEXT;
    /** @var string     units for distances (m, km, mi)     */
    protected string $strUnits       = self::UNITS_M;
    /** @var bool       include elevation informations (ascent/descent)     */
    protected bool $bElevation     = false;

    /** @var string     last error      */
    protected string $strError;
    /** @var string     raw response        */
    protected string $response;
    /** @var array<mixed>  JSON response as associative array      */
    protected array $aJson;

    /**
     * Create object and set API key
     *
     * to get a API key, you must register at (registration is free!)
     *  https://openrouteservice.org/dev/#/home
     * and request new token. All further description ist found at the
     * openrouteservice.org - page.
     *
     * @param string $strKey
     */
    public function __construct(string $strKey)
    {
        $this->strKey = $strKey;
    }

    /**
     * Calculate Route for requested points.
     * Parameters can be either objects of class OSMapPoint or comma
     * separated geolocation string 'latitude, longitude
     *
     * If the coordinates are passed as an array with more than 2 points,
     * the response will result in several segments with the respective sections.
     * (number of segments = number of points less than one)
     *
     * @param OSMapPoint|string|array<OSMapPoint|string> $pt1
     * @param OSMapPoint|string|null $pt2
     * @return bool
     */
    public function calcRoute($pt1, $pt2 = null) : bool
    {
        $bOK = false;
        $aCoordinates = array();
        if (is_array($pt1) && count($pt1) > 1 && !$pt2) {
            // multiple coordinates ...
            for ($i = 0; $i < count($pt1); $i++) {
                $aCoordinates[] = $this->coordinate($pt1[$i]);
            }
        } elseif (!is_array($pt1) && $pt2) {
            // ... start-end coordinates
            $aCoordinates[] = $this->coordinate($pt1);
            $aCoordinates[] = $this->coordinate($pt2);
        }

        // at least two points are necessary...
        if (count($aCoordinates) > 1) {
            $aData = array();
            $aData['coordinates']   = $aCoordinates;
            $aData['instructions']  = $this->bInstructions;

            if (strlen($this->strLanguage) > 0 && strtolower($this->strLanguage) != 'en') {
                $aData['language'] = $this->strLanguage;
            }
            if ($this->bElevation) {
                $aData['elevation'] = true;
            }
            if (strlen($this->strInstructionFormat) > 0 && strtolower($this->strInstructionFormat) != self::IF_TEXT) {
                $aData['instructions_format'] = 'html';
            }
            if (strlen($this->strPreference) > 0 && strtolower($this->strPreference) != self::FASTEST) {
                $aData['preference'] = $this->strPreference;
            }
            if (strlen($this->strUnits) > 0 && strtolower($this->strUnits) != self::UNITS_M) {
                $aData['units'] = $this->strUnits;
            }
            $jsonData = json_encode($aData);
            if ($jsonData !== false) {
                $strURL = self::REST_API_URL . 'directions/' . $this->strVehicleType . '/' . $this->strFormat;
                $bOK = $this->postHttpRequest($strURL, $jsonData);
            }
        } else {
            $this->strError = 'invalid coordinates.';
        }
        return $bOK;
    }

    /**
     * Build coordinate array .
     * API wants to have coordinates in lon, lat!
     *
     * @param OSMapPoint|string $pt
     * @return array<float,float>|null
     */
    protected function coordinate($pt) : ?array
    {
        $aCoordinate = null;
        if ($pt instanceof OSMapPoint && $pt->isSet()) {
            $aCoordinate = array($pt->lon, $pt->lat);
        } elseif (is_string($pt) && strpos($pt, ',') !== false) {
            // have to be string containing lat, lon separated by comma
            $aPt = explode(',', $pt);
            if (count($aPt) == 2) {
                $aCoordinate = array(floatval(trim($aPt[1])), floatval(trim($aPt[0])));
            }
        }
        return $aCoordinate;
    }

    /**
     * Post HttpRequest with given data.
     * in case of error more information can be obtained with getError ()
     *
     * @param string $strURL
     * @param string $jsonData
     * @return bool true if succeeded
     */
    protected function postHttpRequest(string $strURL, string $jsonData) : bool
    {
        $bOK = true;
        $curl = curl_init($strURL);
        if ($curl !== false) {

            $aHeader = [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json, application/geo+json, application/gpx+xml; charset=utf-8',
                'Content-Length: ' . strlen($jsonData),
                'Authorization: ' . $this->strKey,
            ];

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $this->aJson = [];
            if (curl_errno($curl)) {
                $this->strError = curl_error($curl);
                $bOK = false;
            } elseif (is_string($response)) {
                $this->response = $response;
                $iReturnCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                if ($iReturnCode != 200) {
                    $bOK = false;
                    $aJson = json_decode($this->response, true);
                    if (is_array($aJson) && isset($aJson['error'])) {
                        $this->strError = (is_array($aJson['error']) ? $aJson['error']['message'] : $aJson['error']);
                    }
                }
            }
            curl_close($curl);
        }

        return $bOK;
    }

    /**
     * Get distance in defined units (m, km, mi).
     * returns only a valid value, if format is self::FMT_JSON
     *
     * segment value only available, if OSMapOpenRoute::enableInstructions(true) is set.
     *
     * @param int $iSeg index of requested segment; return distance over all if -1 (default)
     * @return float
     */
    public function getDistance(int $iSeg = -1) : float
    {
        return $this->getValue('distance', $iSeg);
    }

    /**
     * Get duration in seconds.
     * returns only a valid value, if format is self::FMT_JSON
     *
     * segment value only available, if OSMapOpenRoute::enableInstructions(true) is set.
     *
     * @param int $iSeg index of requested segment; return duration over all if -1 (default)
     * @return float
     */
    public function getDuration(int $iSeg = -1) : float
    {
        return $this->getValue('duration', $iSeg);
    }

    /**
     * Get ascent in defined units (m, km, mi).
     * returns only valid value, if format is self::FMT_JSON
     * and OSMapOpenRoute::enableElevation(true) is set.
     *
     * segment value only available, if OSMapOpenRoute::enableInstructions(true) is set.
     *
     * @param int $iSeg index of requested segment; return duration over all if -1 (default)
     * @return float
     */
    public function getAscent(int $iSeg = -1) : float
    {
        return $this->getValue('ascent', $iSeg);
    }

    /**
     * Get descent in defined units (m, km, mi).
     * returns only valid value, if format is self::FMT_JSON
     * and OSMapOpenRoute::enableElevation(true) is set.
     *
     * segment value only available, if OSMapOpenRoute::enableInstructions(true) is set.
     *
     * @param int $iSeg index of requested segment; return duration over all if -1 (default)
     * @return float
     */
    public function getDescent(int $iSeg = -1) : float
    {
        return $this->getValue('descent', $iSeg);
    }

    /**
     * Get requested value.
     * if iSeg >= 0, values are read from the instruction list,
     * otherwise over all values from the summary.
     *
     * segment value is only available, if OSMapOpenRoute::enableInstructions(true) is set.
     *
     * @param string $strName
     * @param int $iSeg
     * @return float
     */
    protected function getValue(string $strName, int $iSeg = -1) : float
    {
        $fltValue = 0.0;
        if ($iSeg < 0) {
            $fltValue = $this->getSummaryValue($strName);
        } else {
            $fltValue = $this->getSegmentValue($iSeg, $strName);
        }
        return $fltValue;
    }

    /**
     * Get count of segments.
     * dependent of the number of point specified to calc the route, the response
     * contains one (for two points from-to) or more segments.
     * value is only available, if OSMapOpenRoute::enableInstructions(true) is set.
     * @return int
     */
    public function getSegmentCount() : int
    {
        $iCount = 0;
        if (($aSegments = $this->getSegments()) !== null) {
            $iCount = count($aSegments);
        }
        return $iCount;
    }

    /**
     * Get count of instruction steps inside given segment.
     * value is only available, if OSMapOpenRoute::enableInstructions(true) is set.
     * @param int $iSeg
     * @return int
     */
    public function getStepCount(int $iSeg) : int
    {
        $iCount = 0;
        if ($this->getSegments() !== null) {
            $aSteps = $this->getSegmentValue($iSeg, 'steps');
            $iCount = count($aSteps);
        }
        return $iCount;
    }

    /**
     * Get requested step from segment.
     * if $bArray set to true, result will be associative array, otherwise object from
     * class OSMapOpenRouteStep
     *
     * @param int $iSeg
     * @param int $iStep
     * @param bool $bArray
     * @return OSMapOpenRouteStep|array<string,mixed>|null
     */
    public function getStep(int $iSeg, int $iStep, bool $bArray = false)
    {
        $step = null;
        if ($this->getSegments() !== null) {
            $aSteps = $this->getSegmentValue($iSeg, 'steps');
            if ($aSteps && $iStep < count($aSteps)) {
                $aStep = $aSteps[$iStep];
                $aStep['instruction'] = utf8_decode($aStep['instruction']);
                $aStep['name'] = utf8_decode($aStep['name']);
                $step = ($bArray ? $aStep : new OSMapOpenRouteStep($aStep));
            }
        }
        return $step;
    }

    /**
     * Get all segments.
     * @return ?array<string,mixed>
     */
    protected function getSegments() : ?array
    {
        $aSegments = null;
        if ($this->strFormat == self::FMT_JSON && $this->response) {
            if (!$this->aJson) {
                $this->aJson = json_decode($this->response, true);
            }
            if (is_array($this->aJson) && isset($this->aJson['routes']) && is_array($this->aJson['routes'])) {
                $aRoute = $this->aJson['routes'][0];
                $aSegments = (isset($aRoute['segments']) && is_array($aRoute['segments'])) ? $aRoute['segments'] : null;
            }
        }
        return $aSegments;
    }

    /**
     * Read value from given segment of the JSON response
     *
     * @param int $iSeg number of the segment
     * @param string $strName
     * @return mixed|null
     */
    protected function getSegmentValue(int $iSeg, string $strName)
    {
        $value = null;
        $aSegments = $this->getSegments();
        if ($aSegments && $iSeg < count($aSegments)) {
            $value = isset($aSegments[$iSeg][$strName]) ? $aSegments[$iSeg][$strName] : null;
        }
        return $value;
    }

    /**
     * Read value from the sumary of the JSON response
     *
     * @param string $strName
     * @return mixed|null
     */
    protected function getSummaryValue(string $strName)
    {
        $value = null;
        if ($this->strFormat == self::FMT_JSON && $this->response) {
            if (!$this->aJson) {
                $this->aJson = json_decode($this->response, true);
            }
            if (is_array($this->aJson) && isset($this->aJson['routes']) && is_array($this->aJson['routes'])) {
                $aRoute = $this->aJson['routes'][0];
                $value = isset($aRoute['summary'][$strName]) ? $aRoute['summary'][$strName] : null;
            }
        }
        return $value;
    }

    /**
     * Get units for all distances (m, km, mi)
     * @return string
     */
    public function getUnits() : string
    {
        return $this->strUnits;
    }

    /**
     * Save response as json/gpx file dependent on format to file on server
     * @param string $strFilename
     */
    public function saveRoute(string $strFilename = '') : void
    {
        $this->saveOrDownload(true, $strFilename);
    }

    /**
     * Download response as json/gpx file dependent on format
     * @param string $strFilename
     */
    public function downloadRoute(string $strFilename = '') : void
    {
        $this->saveOrDownload(false, $strFilename);
    }

    /**
     * save or download response as json/gpx file dependent on format
     * @param bool $bSave   true to save file on server, false to force download
     * @param string $strFilename
     */
    protected function saveOrDownload(bool $bSave, string $strFilename) : void
    {
        if ($this->response) {
            $strData = '';
            $strType = '';
            // ... make it readable before saving/downloading
            if ($this->strFormat == self::FMT_JSON || $this->strFormat == self::FMT_GEOJSON) {
                $strData = json_encode(json_decode($this->response), JSON_PRETTY_PRINT);
                if (strlen($strFilename) == 0) {
                    $strFilename = 'route.json';
                } elseif (strpos($strFilename, '.') === false) {
                    $strFilename .= '.json';
                }
                if ($bSave) {
                    file_put_contents($strFilename, $strData);
                } else {
                    $strType = ($this->strFormat == self::FMT_JSON ? 'json' : 'geo+json');
                }
            } elseif ($this->strFormat == self::FMT_GPX) {
                $oDoc = new \DOMDocument();
                $oDoc->preserveWhiteSpace = false;
                $oDoc->formatOutput = true;
                $oDoc->loadXML($this->response);
                if (strlen($strFilename) == 0) {
                    $strFilename = 'route.gpx';
                } elseif (strpos($strFilename, '.') === false) {
                    $strFilename .= '.gpx';
                }
                if ($bSave) {
                    $oDoc->save($strFilename);
                } else {
                    $strData = $oDoc->saveXML();
                    $strType = 'gpx+xml';
                }
            }
            if (!$bSave && $strData !== false) {
                header('Content-Type: application/' . $strType . '; charset=utf-8');
                header('Content-Length: ' . strlen($strData));
                header('Connection: close' );
                header('Content-Disposition: attachment; filename=' . $strFilename);
                echo $strData;
            }
        }
    }

    /**
     * Raw response data for further processing.
     * @return string (JSON, GeoJSO, or GPX-XML Data)
     */
    public function getResponse() : string
    {
        return $this->response;
    }

    /**
     * Get the last error.
     * @return string
     */
    public function getError() : string
    {
        return $this->strError;
    }

    /**
     * Set language.
     * ISO 3166-3  -  NOT all languages are supported!
     *
     * supported languages:
     * 'en', 'de', 'cn', 'es', 'ru', 'dk', 'fr', 'it', 'nl', 'br', 'se', 'tr', 'gr'
     *
     * @param string $strLanguage
     */
    public function setLanguage(string $strLanguage) : void
    {
        $this->strLanguage = strtolower($strLanguage);
    }

    /**
     * Set vehicle type.
     * valid types:
     * - self::VT_CAR           car
     * - self::VT_HGV           hgv (heavy goods vehicle ... > 3.5t)
     * - self::VT_BICYCLE       'normal' bicycle
     * - self::VT_ROAD_BIKE     roadbike
     * - self::VT_MTB           mountainbike
     * - self::VT_E_BIKE        electric bike
     * - self::VT_WALKING       'normal' walking
     * - self::VT_HIKING        hiking
     * - self::VT_WHEELCHAIR    wheelchair
     *
     * if no type selected self::VT_CAR is assumed
     *
     * @param string $strVehicleType
     */
    public function setVehicleType(string $strVehicleType) : void
    {
        $this->strVehicleType = $strVehicleType;
    }

    /**
     * Set  the format of the data.
     * self::FMT_JSON, self::FMT_GEOJSON or self::FMT_GPX
     *
     * access to details via properties/methods only available for format self::FMT_JSON
     * the other formats may be usefull for display.
     *
     * @param string $strFormat
     */
    public function setFormat(string $strFormat) : void
    {
        $this->strFormat = $strFormat;
    }

    /**
     * Set the preference for calculation.
     * whether self::FASTEST (default) or self::SHORTEST
     * @param string $strPreference
     */
    public function setPreference(string $strPreference) : void
    {
        $this->strPreference = $strPreference;
    }

    /**
     * Enable generating of instructions.
     * @param bool $bInstructions
     */
    public function enableInstructions(bool $bInstructions = true) : void
    {
        $this->bInstructions = $bInstructions;
    }

    /**
     * Format of the instructions.
     * whether  self::IF_TEXT (default) or self::IF_HTML
     * @param string $strInstructionFormat
     */
    public function setInstructionFormat(string $strInstructionFormat) : void
    {
        $this->strInstructionFormat = $strInstructionFormat;
    }

    /**
     * Set units for distance values.
     * one of self::UNITS_M (default),self::UNITS_KM or self::UNITS_MILES
     * @param string $strUnits
     */
    public function setUnits(string $strUnits) : void
    {
        $this->strUnits = $strUnits;
    }

    /**
     * Enable generation of elevation information (for over all route and segments)
     * @param bool $bElevation
     */
    public function enableElevation(bool $bElevation = true) : void
    {
        $this->bElevation = $bElevation;
    }
}
