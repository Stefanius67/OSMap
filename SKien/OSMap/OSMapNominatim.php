<?php
declare(strict_types=1);

namespace SKien\OSMap;

use stdClass;

/**
 * Class to determine geolocation from address or vice versa.
 *
 * uses the Nominatim API to search OSM (Open Street Map) data
 *
 *  !! Important !!
 *  be aware of Nominatim usage policy
 *  @link https://operations.osmfoundation.org/policies/nominatim
 *
 *  most important item certainly the 'Unacceptable Use'
 *  (following uses are strictly forbidden and will get you banned):
 *  - Auto-complete search.
 *    This is not yet supported by Nominatim and you must not implement
 *    such a service on the client side using the API.
 *  - Systematic queries.
 *    This includes reverse queries in a grid, searching for complete lists
 *    of postcodes, towns etc. and downloading all POIs in an area. If you
 *    need complete sets of data, get it from the OSM planet or an extract.
 *
 *  and take care of the OpenStreetMap ODbL
 *  @link https://www.openstreetmap.org/copyright
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapNominatim
{
    /** direction of search   */
    public const __DIR  = '';
    /** search geolocation for address   */
    public const SEARCH = 'search';
    /** reverse search address for geolocation  */
    public const REVERSE = 'reverse';

    /** format of the longitude/latitude values   */
    public const __FORMAT  = 0;
    /** DMS format sexagesimal (degree minute second.tenth-of-second)   */
    public const FORMAT_DMS = 0;
    /** DMS format nautical / GPS (degree minute . tenth-of-minute)   */
    public const FORMAT_DMM = 1;
    /** DMS format decimal (degree . tenth-of-degree)   */
    public const FORMAT_DDD = 2;

    /** order of streetname and house number */
    public const  __ORDER = 0;
    /** house number after streetname */
    public const STREET_NAME_NR = 0;
    /** house number preceding streetname */
    public const STREET_NR_NAME = 1;

    /** @var string street (including house number) */
    protected string $strStr = '';
    /** @var string city */
    protected string $strCity = '';
    /** @var string postcode */
    protected string $strPostcode = '';
    /** @var string country */
    protected string $strCountry = '';
    /** @var string region */
    protected string $strRegion = '';
    /** @var string additional information (sports_centre, attraction, stadium, retail, fast_food, hotel, restaurant, ...) */
    protected string $strInfo = '';
    /** @var string latitude */
    protected string $strLatitude = '';
    /** @var string longitude */
    protected string $strLongitude = '';
    /** @var string encoding */
    protected string $strEncoding = 'UTF-8';
    /** @var int street format */
    protected int $iStreetFormat = self::STREET_NAME_NR;
    /** @var string separator between street and house number */
    protected string $strStreetSep = '';
    /** @var string the language of the result */
    protected string $strLang = '';

    /**
     *  reset properties
     */
    public function reset() : void
    {
    }

    /**
     * @return bool
     */
    public function searchAddress() : bool
    {
        $bFound = false;
        // at least street and city or postcode should be specified
        if (strlen($this->strStr) > 0 && (strlen($this->strCity) > 0 || strlen($this->strPostcode) > 0 )) {

            $strQuery = $this->strStr . ',' . $this->strPostcode . ' ' . $this->strCity;
            if (strlen($this->strCountry) > 0 ) {
                $strQuery .= ',' . $this->strCountry;
            }
            $strQuery = 'q=' . urlencode($strQuery);

            $oItem = $this->apiCall(self::SEARCH, array($strQuery, 'format=json', 'addressdetails=1'));
            if ($oItem !== null) {
                $this->readItem($oItem);
                $bFound = true;
            }
        }
        return $bFound;
    }


    /**
     * @return bool
     */
    public function searchLocation() : bool
    {
        $bFound = false;
        if (strlen($this->strLatitude) > 0 && strlen($this->strLongitude)) {
            $oItem = $this->apiCall(self::REVERSE, array('lat=' . $this->strLatitude, 'lon=' . $this->strLongitude, 'format=json', 'addressdetails=1'));
            if ($oItem !== null) {
                $this->readItem($oItem);
                $bFound = true;
            }
        }
        return $bFound;
    }

    /**
     * Execute query on nominatim.openstreetmap.org.
     * @param string $strQuery
     * @param array<string> $aParams
     * @return ?\stdClass
     */
    protected function apiCall(string $strQuery, array $aParams) : ?\stdClass
    {
        $strURL = 'https://nominatim.openstreetmap.org/' . $strQuery;
        $strSep = '?';
        foreach ($aParams as $strParam) {
            $strURL .= $strSep . $strParam;
            $strSep = '&';
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $strURL);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  // nominatim will reject call without valid agent!
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (strlen($this->strLang) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Language: ' . $this->strLang]);
        }

        $result = curl_exec($curl);
        curl_close($curl);

        $json = null;
        if (is_string($result)) {
            $json = json_decode($result);
            // sometimes multiple items returned, e.g. some companies at same postal address, etc.
            if (is_array($json)) {
                // take first hit...
                $json = $json[0];
            }
        }
        return $json;
    }

    /**
     * @param \stdClass $oItem
     */
    protected function readItem(\stdClass $oItem) : void
    {
        // $strType = $this->getProperty($oItem, 'type');
        $strClass = $this->getProperty($oItem, 'class');
        if (property_exists($oItem, 'address')) {
            $oAddr = $oItem->address;
            if ($oAddr instanceof \stdClass) {
                if ($this->iStreetFormat == self::STREET_NAME_NR ) {
                    $this->strStr = $this->getProperty($oAddr, 'road') . $this->strStreetSep . ' ' . $this->getProperty($oAddr, 'house_number');
                } else {
                    $this->strStr = $this->getProperty($oAddr, 'house_number') . $this->strStreetSep . ' ' . $this->getProperty($oAddr, 'road');
                }
                $this->strPostcode = $this->getProperty($oAddr, 'postcode');
                $this->strCity = $this->getProperty($oAddr, 'city');
                if (property_exists($oAddr, 'city')) {
                    $this->strCity = $this->getProperty($oAddr, 'city');
                } else {
                    $this->strCity = $this->getProperty($oAddr, 'town');
                }
                $this->strCountry = $this->getProperty($oAddr, 'country');
                $this->strRegion = $this->getProperty($oAddr, 'state');

                // if (property_exists($oAddr, $strType)) {
                if (property_exists($oAddr, $strClass)) {
                    $this->strInfo = $this->getProperty($oAddr, $strClass);
                } else {
                    // look after some other infos...
                    $aInfo = [
                        'sports_centre', 'attraction', 'stadium', 'retail', 'hotel', 'restaurant',
                        'cafe', 'fast_food', 'kiosk', 'bank', 'school', 'hospital', 'tourism',
                    ];
                    foreach ($aInfo as $strInfo) {
                        $this->strInfo = $this->getProperty($oAddr, $strInfo);
                        if (strlen($this->strInfo) > 0) {
                            break;
                        }
                    }
                }
            }
        }

        $this->strLatitude = $this->getProperty($oItem, 'lat');
        $this->strLongitude = $this->getProperty($oItem, 'lon');
    }

    /**
     * Save get property of object.
     * checks if property exists
     *
     * @param \stdClass $obj        object containing the property
     * @param string    $strName    name of property
     * @return string               value or empty string, if property not exists
     */
    protected function getProperty(\stdClass $obj, string $strName) : string
    {
        $strValue = '';
        if (property_exists($obj, $strName)) {
            $strValue = $obj->$strName;
            if ($this->strEncoding != 'UTF-8') {
                $strValue = iconv('UTF-8', $this->strEncoding, $strValue);
            }
        }
        return $strValue;
    }

    /**
     * Format the coordinate value.
     * @param string $strValue  cordinate
     * @param int $iFormat
     * @param bool $bLong
     * @return string
     */
    public function formatCoordinate(string $strValue, int $iFormat, bool $bLong) : string
    {
        $strFormated = $strValue;
        if (strlen($strValue) > 0) {
            $strDir = $bLong ? 'E' : 'N';
            $dblDegree = floatval($strValue);
            if ($dblDegree < 0) {
                $dblDegree *= -1;
                $strDir = $bLong ? 'W' : 'S';
            }
            $iDegree = intval(floor($dblDegree));
            $dblMin = ($dblDegree - $iDegree) * 60;
            $iMin = intval(floor($dblMin));
            $dblSec = ($dblMin - $iMin) * 60;

            switch ($iFormat) {
                case self::FORMAT_DMS:  // sexagesimal (degree minute second.tenth-of-second)
                    $strFormated = sprintf('%2d° %2d\' %2.3f" ', $iDegree, $iMin, $dblSec);
                    $strFormated .= $strDir;
                    break;
                case self::FORMAT_DMM:  // format nautical / GPS (degree minute . tenth-of-minute)
                    $strFormated = sprintf('%2d° %2.5f\' ', $iDegree, $dblMin);
                    $strFormated .= $strDir;
                    break;
                case self::FORMAT_DDD:  // decimal (degree . tenth-of-degree)
                default:
                    $strFormated = $strValue;  // ... as it is
                    break;
            }
            if ($this->strEncoding != 'UTF-8') {
                $strFormated = iconv('UTF-8', $this->strEncoding, $strFormated);
                if ($strFormated === false) {
                    $strFormated = 'invalid';
                }
            }
        }
        return $strFormated;
    }

    /**
     * @param string $strStr
     */
    public function setStr(string $strStr) : void
    {
        $this->strStr = $strStr;
    }

    /**
     * @param string $strCity
     */
    public function setCity(string $strCity) : void
    {
        $this->strCity = $strCity;
    }

    /**
     * @param string $strPostcode
     */
    public function setPostcode(string $strPostcode) : void
    {
        $this->strPostcode = $strPostcode;
    }

    /**
     * @param string $strCountry
     */
    public function setCountry(string $strCountry) : void
    {
        $this->strCountry = $strCountry;
    }

    /**
     * @param string $strRegion
     */
    public function setRegion(string $strRegion) : void
    {
        $this->strRegion = $strRegion;
    }

    /**
     * @param string $strLatitude
     */
    public function setLatitude(string $strLatitude) : void
    {
        $this->strLatitude = $strLatitude;
    }

    /**
     * @param string $strLongitude
     */
    public function setLongitude(string $strLongitude) : void
    {
        $this->strLongitude = $strLongitude;
    }

    /**
     * set location.
     * so far only DDD Format is supported
     * @param string $strLocation
     */
    public function setLocation(string $strLocation) : void
    {
        $aLoc = explode(',', $strLocation);
        if (count($aLoc) == 2) {
            $this->strLatitude = trim($aLoc[0]);
            $this->strLongitude = trim($aLoc[1]);
        }
    }

    /**
     * @param string $strEncoding
     */
    public function setEncoding(string $strEncoding) : void
    {
        $this->strEncoding = $strEncoding;
    }

    /**
     * Set format of street and house number.
     * - in englisch and french regions: house number preceding streetname
     * - other regions vice versa
     * - romanesque regions sometimes uses separator
     *
     * @param int $iFormat      OSMapNominatim::STREET_NAME_NR or OSMapNominatim::STREET_NR_NAME
     * @param string $strSeparator  separator between name and number
     */
    public function setStreetFormat(int $iFormat, string $strSeparator = '') : void
    {
        $this->iStreetFormat = $iFormat;
        $this->strStreetSep = $strSeparator;
    }

    /**
     * Set the language for the result of a request.
     * Value is sent with the HTTP header to the API so it should be in
     * format RFC 1766 (q-Liste)
     * @param string $strLang
     */
    public function setLanguage(string $strLang) : void
    {
        $this->strLang = $strLang;
    }

    /**
     * @return string
     */
    public function getStr() : string
    {
        return $this->strStr;
    }

    /**
     * @return string
     */
    public function getCity() : string
    {
        return $this->strCity;
    }

    /**
     * @return string
     */
    public function getPostcode() : string
    {
        return $this->strPostcode;
    }

    /**
     * @return string
     */
    public function getCountry() : string
    {
        return $this->strCountry;
    }

    /**
     * @return string
     */
    public function getRegion() : string
    {
        return $this->strRegion;
    }

    /**
     * @return string
     */
    public function getInfo() : string
    {
        return $this->strInfo;
    }

    /**
     * @param int $iFormat  self::FORMAT_DMS, self::FORMAT_DMM or self::FORMAT_DDD (default)
     * @return string
     */
    public function getLatitude(int $iFormat = self::FORMAT_DDD) : string
    {
        return $this->formatCoordinate($this->strLatitude, $iFormat, FALSE);
    }

    /**
     * @param int $iFormat  self::FORMAT_DMS, self::FORMAT_DMM or self::FORMAT_DDD (default)
     * @return string
     */
    public function getLongitude(int $iFormat = self::FORMAT_DDD) : string
    {
        return $this->formatCoordinate($this->strLongitude, $iFormat, TRUE);
    }

    /**
     * @param int $iFormat  self::FORMAT_DMS, self::FORMAT_DMM or self::FORMAT_DDD (default)
     * @return string
     */
    public function getLocation(int $iFormat = self::FORMAT_DDD) : string
    {
        $strLocation = '';
        if (strlen($this->strLatitude) > 0 && strlen($this->strLongitude) > 0) {
            $strLocation  = $this->formatCoordinate($this->strLatitude, $iFormat, FALSE);
            $strLocation .= ', ';
            $strLocation .= $this->formatCoordinate($this->strLongitude, $iFormat, TRUE);
        }
        return $strLocation;
    }
}
