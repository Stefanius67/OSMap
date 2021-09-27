<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class representing a bound.
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapBounds
{
    /** @var OSMapPoint top left corner */
    public OSMapPoint $ptTopLeft;
    /** @var OSMapPoint bottom right corner */
    public OSMapPoint $ptBottomRight;

    /**
     * @param OSMapPoint $ptTopLeft
     * @param OSMapPoint $ptBottomRight
     */
    public function __construct(OSMapPoint $ptTopLeft = null, OSMapPoint $ptBottomRight = null)
    {
        $this->ptTopLeft = $ptTopLeft ?? new OSMapPoint();
        $this->ptBottomRight = $ptBottomRight ?? new OSMapPoint();
    }

    /**
     * Check, if object is set
     * @return bool
     */
    public function isSet() : bool
    {
        return $this->ptTopLeft->isSet() && $this->ptBottomRight->isSet();
    }

    /**
     * Calculates the width in km
     * @return float
     */
    public function width() : float
    {
        $fltWidth = 0.0;
        if ($this->isSet()) {
            $fltWidth = OSMapEmbedded::getDistance($this->ptTopLeft->lat, $this->ptTopLeft->lon, $this->ptTopLeft->lat, $this->ptBottomRight->lon);
        }
        return $fltWidth;
    }

    /**
     * Calculates the height in km
     * @return float
     */
    public function height() : float
    {
        $fltHeight = 0.0;
        if ($this->isSet()) {
            $fltHeight = OSMapEmbedded::getDistance($this->ptTopLeft->lat, $this->ptTopLeft->lon, $this->ptBottomRight->lat, $this->ptTopLeft->lon);
        }
        return $fltHeight;
    }

    /**
     * Merges the object and a second bound to a new bound.
     * Result is new bound surrounding both bounds
     * @param OSMapBounds $bdMerge
     * @return OSMapBounds
     */
    public function merge(OSMapBounds $bdMerge) : OSMapBounds
    {
        $bounds = null;
        if ($this->isSet() && $bdMerge->isSet()) {
            $ptTopLeft = new OSMapPoint(
                OSMapEmbedded::maxValue([$this->ptTopLeft->lat, $bdMerge->ptTopLeft->lat]),
                OSMapEmbedded::minValue([$this->ptTopLeft->lon, $bdMerge->ptTopLeft->lon])
            );
            $ptBottomRight = new OSMapPoint(
                OSMapEmbedded::minValue([$this->ptBottomRight->lat, $bdMerge->ptBottomRight->lat]),
                OSMapEmbedded::maxValue([$this->ptBottomRight->lon, $bdMerge->ptBottomRight->lon])
            );
            $bounds = new OSMapBounds($ptTopLeft, $ptBottomRight);
        } elseif ($this->isSet()) {
            $bounds = clone($this);
        } elseif ($bdMerge->isSet()) {
            $bounds = clone($bdMerge);
        } else {
            $bounds = new OSMapBounds();
        }
        return $bounds;
    }
}