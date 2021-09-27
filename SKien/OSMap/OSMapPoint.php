<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class representing a geolocation  (WGS 1984).
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 */
class OSMapPoint
{
    /** @var float  latitude */
    public ?float $lat = null;
    /** @var float  longitude */
    public ?float $lon = null;

    /**
     * @param float $lat
     * @param float $lon
     */
    public function __construct(float $lat = null, float $lon = null)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    /**
     * check, if object is set
     * @return bool
     */
    public function isSet() : bool
    {
        return ($this->lat !== null && $this->lon !== null);
    }

    /**
     * Calculates distance of point from given point in the specified unit.
     * @param OSMapPoint $ptFrom
     * @return float   distance in km.
     */
    public function distanceFrom(OSMapPoint $ptFrom) : float
    {
        $fltDist = 0.0;
        if ($this->isSet() && $ptFrom->isSet()) {
            $fltDist = OSMapEmbedded::getDistance($this->lat, $this->lon, $ptFrom->lat, $ptFrom->lon);
        }
        return $fltDist;
    }
}