<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class representing marker inside a OSMap
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapMarker
{
    /** hotspot of the marker ist on top */
    public const TOP     = 0x01;
    /** hotspot of the marker ist in the middle */
    public const MIDDLE  = 0x02;
    /** hotspot of the marker ist on bottom */
    public const BOTTOM  = 0x03;
    /** hotspot of the marker ist left */
    public const LEFT    = 0x10;
    /** hotspot of the marker ist centered */
    public const CENTER  = 0x20;
    /** hotspot of the marker ist right */
    public const RIGHT   = 0x30;

    /** @var OSMapPoint position of the marker */
    protected OSMapPoint $ptPos;
    /** @var string popup text */
    protected string $strText = '';
    /** @var int width of the popup in pixels */
    protected int $iPopupWidth = 300;
    /** @var int height of the popup in pixels */
    protected int $iPopupHeight = 120;
    /** @var string image included in popup */
    protected string $strImage = '';
    /** @var int width of the image */
    protected int $iImageWidth = -1;
    /** @var int height of the image */
    protected int $iImageHeight = -1;
    /** @var string custom icon for the marker */
    protected string $strIcon = '';
    /** @var int width of icon */
    protected int $iIconWidth = -1;
    /** @var int height of icon */
    protected int $iIconHeight = -1;
    /** @var int hotspot of the image. A combination of self::TOP, self::MIDDLE or self::BOTTOM with self::LEFT, self::CENTER or self::RIGHT */
    protected int $iIconHotSpot = -1;
    /** @var bool popup of the marker will be show on display of the map */
    protected bool $bInitialPopup = false;

    /**
     * Construct marker on given location.
     * @param OSMapPoint $ptPos
     */
    public function __construct(OSMapPoint $ptPos)
    {
        $this->ptPos = clone $ptPos;
    }

    /**
     * Sset text and size of popup.
     * @param string $strText
     * @param int $iWidth   width in pixel (default 300px)
     * @param int $iHeight  height in pixel (default 120px)
     */
    public function setText(string $strText, int $iWidth = -1, int $iHeight = -1) : void
    {
        $this->strText = $strText;
        if ($iWidth > 0) {
            $this->iPopupWidth = $iWidth;
        }
        if ($iHeight > 0) {
            $this->iPopupHeight = $iHeight;
        }
    }

    /**
     * Set image for the marker.
     * @param string $strImage
     * @param int $iWidth   width in pixel, if not set origin size from image is used
     * @param int $iHeight  height in pixel, if not set origin size from image is used
     */
    public function setImage(string $strImage, int $iWidth = -1, int $iHeight = -1) : void
    {
        $this->strImage = $strImage;
        if ($iWidth > 0) {
            $this->iImageWidth = $iWidth;
        }
        if ($iHeight > 0) {
            $this->iImageHeight = $iHeight;
        }
    }

    /**
     * Set custom icon for the marker
     * @param string $strIcon
     * @param int $iWidth      width in pixel
     * @param int $iHeight     height in pixel
     * @param int $iHotSpot    A combination of self::TOP, self::MIDDLE or self::BOTTOM with self::LEFT, self::CENTER or self::RIGHT
     */
    public function setIcon(string $strIcon, int $iWidth, int $iHeight, int $iHotSpot = 0) : void
    {
        $this->strIcon = $strIcon;
        $this->iIconHotSpot = ($iHotSpot == 0) ? self::BOTTOM | self::CENTER : $iHotSpot;
        $this->iIconWidth = $iWidth;
        $this->iIconHeight = $iHeight;
    }

    /**
     * The popup of the marker will be show automatic when the map is displayed if set to true.
     * @param bool $bInitialPopup
     */
    public function setInitialPopup(bool $bInitialPopup = true) : void
    {
        $this->bInitialPopup = $bInitialPopup;
    }

    /**
     * Create the JS script to insert the marker.
     * This method is called by the embedding OSMEmbedded object.
     * @return string
     */
    public function createScript() : string
    {
        $strScript = '';
        $strIcon = 'null';
        if (strlen($this->strIcon) > 0 ) {
            $aHorz = array(self::LEFT => '0', self::CENTER => '-(size.w/2)', self::RIGHT => '-size.w');
            $aVert = array(self::TOP => '0', self::MIDDLE => '-(size.h/2)', self::BOTTOM => '-size.h');
            $strIcon = 'icon';

            $strHorz = isset($aHorz[$this->iIconHotSpot & 0xF0]) ? $aHorz[$this->iIconHotSpot & 0xF0] : '-(size.w/2)';
            $strVert = isset($aVert[$this->iIconHotSpot & 0x0F]) ? $aVert[$this->iIconHotSpot & 0x0F] : '-(size.h/2)';

            $strScript .= '   var size = new OpenLayers.Size(' . $this->iIconWidth . ', ' . $this->iIconHeight . ');' . PHP_EOL;
            $strScript .= '   var offset = new OpenLayers.Pixel(' . $strHorz . ', ' . $strVert . ');' . PHP_EOL;
            $strScript .= '   var icon = new OpenLayers.Icon("' . $this->strIcon . '", size, offset);' . PHP_EOL;
        }
        if ($this->hasPopup()) {
            $strScript .= '   createMarker(layerMarkers, ' . $this->ptPos->lon . ', ' . $this->ptPos->lat . ', "' . $this->strText . '", "' . $this->strImage . '", ';
            $strScript .= $this->iPopupWidth . ', ' . $this->iPopupHeight . ', ' . $this->iImageWidth . ', ' . $this->iImageHeight . ', ' . $strIcon . ', ';
            $strScript .= $this->bInitialPopup ? 'true' : 'false';
            $strScript .= ');' . PHP_EOL;
        } else {
            $strScript .= '   layerMarkers.addMarker(new OpenLayers.Marker(new OpenLayers.LonLat(' . $this->ptPos->lon . ', ' . $this->ptPos->lat . ').transform("EPSG:4326","EPSG:900913"), ' . $strIcon . '));' . PHP_EOL;
        }
        return $strScript;
    }

    /**
     * @return bool true, if marker has popup
     */
    public function hasPopup() : bool
    {
        return (strlen($this->strText) > 0 || strlen($this->strImage) > 0);
    }
}