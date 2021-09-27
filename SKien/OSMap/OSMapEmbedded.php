<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class to embed open street map into page.
 * - set multiple dynamic markers
 * - add popup to markers
 *
 *  !! Important !!
 *  take care of the OpenStreetMap ODbL
 *  @link https://www.openstreetmap.org/copyright
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapEmbedded
{
    /** show zoombar on left        */
    public const ZOOMBAR       = 0x01;
    /** show layerswitcher on top right */
    public const LAYERSWITCHER = 0x02;
    /** show attribution on bottom right    */
    public const ATTRIBUTION   = 0x04;
    /** show overview - button middle right */
    public const OVERVIEW      = 0x08;
    /** [+][-] zoom buttons on the top left (only, if ZOOMBAR not specified)    */
    public const ZOOM          = 0x10;
    /** nav toolbar to switch mouse mode between moving and selecting a region  */
    public const NAVTOOLBAR    = 0x20;
    /** display location of mouseposition on bottom right */
    public const MOUSELOCATION = 0x40;

    /** script providing the API        */
    protected const OL_SCRIPT_URL = "http://www.openlayers.org/api/OpenLayers.js";

    /** @var string ID of HTML div contaiing the Map */
    protected $strDivID = '';
    /** @var string language code */
    protected $strLanCode = '';
    /** @var int bitmask of controls to be shown in map (self::NAVIGATION, self::ZOOMBAR, self::LAYERSWITCHER, self::ATTRIBUTION)    */
    protected $iControls = self::ZOOMBAR;
    /** @var string CSS class to use for popup text */
    protected $strPopupClass = '';
    /** @var OSMapPoint centre point */
    protected   $ptCentre = null;
    /** @var OSMapBounds bounds    */
    protected   $bounds = null;
    /** @var int initial zoom faktor (value between 1 and 18)    */
    protected $iZoom = 0;
    /** @var array<OSMapMarker> array of all markers created  */
    protected $aMarkers = [];
    /** @var array<OSMapGPX> array of GPX - tracks/regions  */
    protected $aGPX = [];

    /**
     * Construct an embedded OSMap.
     * @param string $strID         id of the HTML - div the map have to placed in
     * @param string $strLanCode    language code (ISO-639-1-Codes)
     */
    public function __construct(string $strID = 'OSMap', string $strLanCode = 'de')
    {
        $this->strDivID = $strID;
        $this->strLanCode = $strLanCode;
    }

    /**
     * Select the controls to be shown in the map.
     * Any combination of self::NAVIGATION, self::ZOOMBAR, self::LAYERSWITCHER, self::ATTRIBUTION
     * @param int $iControls
     */
    public function showControls(int $iControls) : void
    {
        $this->iControls = $iControls;
    }

    /**
     * Set the centre and zoomfactor of the region to display.
     * @param OSMapPoint $ptCentre
     * @param int $iZoom  zoom factor (value between 1 and 18)
     */
    public function setView(OSMapPoint $ptCentre, int $iZoom = 15) : void
    {
        $this->ptCentre = clone $ptCentre;
        if ($iZoom > 0 && $iZoom < 19) {
            $this->iZoom = $iZoom;
        }
    }

    /**
     * Set the bounds to display.
     * Overrides the centre and zoom factor specified by setView()
     * @param OSMapBounds $bounds
     */
    public function setBounds(OSMapBounds $bounds) : void
    {
        $this->bounds = clone $bounds;
    }

    /**
     * Add a marker to the map.
     * @param OSMapPoint $ptPos
     * @return OSMapMarker
     */
    public function addMarker(OSMapPoint $ptPos) : OSMapMarker
    {
        $oMarker = new OSMapMarker($ptPos);
        $this->aMarkers[] = $oMarker;

        return $oMarker;
    }

    /**
     * Add a track/region to the map.
     * @param string $strFilename
     * @return OSMapGPX
     */
    public function addGPX(string $strFilename) : OSMapGPX
    {
        $oGPX = new OSMapGPX($strFilename);
        $this->aGPX[] = $oGPX;

        return $oGPX;
    }

    /**
     * Set the CSS class to use for popup text.
     * @param string $strClass
     */
    public function setPopupClass(string $strClass) : void
    {
        $this->strPopupClass = $strClass;
    }

    /**
     * Create all needed JS to inserted in the HTML - <head> of the page where the map is embedded.
     * @return string
     */
    public function createScript() : string
    {
        $strScript  = '<script src="' . self::OL_SCRIPT_URL . '"></script>' . PHP_EOL;

        $strScript .= '<script>' . PHP_EOL;
        $strScript .= 'var map;' . PHP_EOL;

        $strScript .= 'function drawOSMap() {' . PHP_EOL;

        $strScript .= '   OpenLayers.Lang.setCode("' . $this->strLanCode . '");' . PHP_EOL;
        $strControls = 'new OpenLayers.Control.Navigation()';
        if (($this->iControls & self::ZOOMBAR) != 0) {
            $strControls .= ',new OpenLayers.Control.PanZoomBar()';
        } elseif (($this->iControls & self::ZOOM) != 0) {
            $strControls .= ',new OpenLayers.Control.Zoom()';
        }
        if (($this->iControls & self::OVERVIEW) != 0) {
            $strControls .= ',new OpenLayers.Control.OverviewMap()';
        }
        if (($this->iControls & self::LAYERSWITCHER) != 0) {
            $strControls .= ',new OpenLayers.Control.LayerSwitcher()';
        }
        if (($this->iControls & self::ATTRIBUTION) != 0) {
            $strControls .= ',new OpenLayers.Control.Attribution()';
        }
        if (($this->iControls & self::NAVTOOLBAR) != 0) {
            $strControls .= ',new OpenLayers.Control.NavToolbar()';
        }
        if (($this->iControls & self::MOUSELOCATION) != 0) {
            $strControls .= ',new OpenLayers.Control.MousePosition({ displayProjection: "EPSG:4326" })';
        }
        $strScript .= '   var options = { controls: [' . $strControls . ']};' . PHP_EOL;
        $strScript .= '   map = new OpenLayers.Map("' . $this->strDivID . '", options);' . PHP_EOL;
        $strScript .= '   var layerMapnik = new OpenLayers.Layer.OSM("Stra&szlig;enkarte");' . PHP_EOL;
        $strScript .= '   map.addLayer(layerMapnik);' . PHP_EOL;

        if ($this->ptCentre !== null && $this->ptCentre->isSet()) {
            $strScript .= '   var position       = new OpenLayers.LonLat(' . $this->ptCentre->lon . ', ' . $this->ptCentre->lat . ').transform("EPSG:4326","EPSG:900913");' . PHP_EOL;
            $strScript .= '   map.setCenter(position, ' . $this->iZoom . ');' . PHP_EOL;
        }

        if ($this->bounds !== null && $this->bounds->isSet()) {
            $strScript .= '   var extent = new OpenLayers.Bounds(' . $this->bounds->ptTopLeft->lon . ', ' . $this->bounds->ptBottomRight->lat . ', ';
            $strScript .= $this->bounds->ptBottomRight->lon . ', ' . $this->bounds->ptTopLeft->lat . ').transform("EPSG:4326","EPSG:900913");' . PHP_EOL;
            $strScript .= '   map.zoomToExtent(extent);' . PHP_EOL;
        }

        $iGPX = 1;
        foreach ($this->aGPX as $oGPX) {
            $strScript .= $oGPX->createScript($iGPX++);
        }

        if (count($this->aMarkers) > 0) {
            $strScript .= '   var layerMarkers = new OpenLayers.Layer.Markers( "Markers" );' . PHP_EOL;
            $strScript .= '   map.addLayer(layerMarkers);' . PHP_EOL;
        }

        // add markers as last layer - popups won't work if any vector-layer is added after marker-layer
        $bHasPopup = false;
        foreach ($this->aMarkers as $oMarker) {
            if ($oMarker->hasPopup()) {
                $bHasPopup = true;
            }
            $strScript .= $oMarker->createScript();
        }

        $strScript .= '   var layerLabels = new OpenLayers.Layer.Vector("Labels");' . PHP_EOL;
        $strScript .= '   layerLabels.addFeatures(getLabels());' . PHP_EOL;
        $strScript .= '   map.addLayer(layerLabels);' . PHP_EOL;

        $strScript .= '}' . PHP_EOL;

        if ($bHasPopup) {
            // JS helper function to create marker with popup

            //  / minSize
            $strScript .= 'function createMarker(layer, lon, lat, text, image, width, height, imgwidth, imgheight, icon, popup) {' . PHP_EOL;
            $strScript .= '   var position = new OpenLayers.LonLat(lon, lat).transform("EPSG:4326","EPSG:900913");' . PHP_EOL;
            $strScript .= '   var marker = new OpenLayers.Marker(position, icon);' . PHP_EOL;
            $strScript .= '   if (height < (imgheight + 60) && imgheight > 0) { height = imgheight + 60; }' . PHP_EOL;
            $strScript .= '   ' . PHP_EOL;
            $strScript .= '   var feature = new OpenLayers.Feature(layer, position);' . PHP_EOL;
            $strScript .= '   ' . PHP_EOL;
            $strScript .= '   ' . PHP_EOL;
            $strScript .= '   feature.closeBox = true;' . PHP_EOL;
            $strScript .= '   feature.popupClass = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {contentSize: new OpenLayers.Size(width, height)} );' . PHP_EOL;
            $strScript .= '   feature.data.popupContentHTML = "<div class=\"' . $this->strPopupClass . '\">";' . PHP_EOL;
            $strScript .= '   if (image != "") {' . PHP_EOL;
            $strScript .= '      feature.data.popupContentHTML += "<img src=\"" + image + "\"";' . PHP_EOL;
            $strScript .= '      if (imgwidth > 0) { feature.data.popupContentHTML += " width=\"" + imgwidth + "\"";    }' . PHP_EOL;
            $strScript .= '      if (imgheight > 0) { feature.data.popupContentHTML += " height=\"" + imgheight + "\""; }' . PHP_EOL;
            $strScript .= '      feature.data.popupContentHTML += ">";' . PHP_EOL; // TODO: set left right
            $strScript .= '   };' . PHP_EOL;
            $strScript .= '   feature.data.popupContentHTML += text + "</div>";' . PHP_EOL;
            $strScript .= '   feature.data.overflow = "hidden";' . PHP_EOL;
            $strScript .= '   marker.feature = feature;' . PHP_EOL;
            $strScript .= '   var markerClick = function(evt) {' . PHP_EOL;
            $strScript .= '      if (this.popup == null) {' . PHP_EOL;
            $strScript .= '         this.popup = this.createPopup(this.closeBox);' . PHP_EOL;

            $strScript .= '         this.popup.contentSize = new OpenLayers.Size(width, height);' . PHP_EOL;
            $strScript .= '         this.popup.border = "1px solid black";' . PHP_EOL;
            // $strScript .= '         this.popup.border-radius = "10px";' . PHP_EOL;

            $strScript .= '         map.addPopup(this.popup);' . PHP_EOL;
            $strScript .= '         this.popup.show();' . PHP_EOL;
            $strScript .= '      } else {' . PHP_EOL;
            $strScript .= '         this.popup.toggle();' . PHP_EOL;
            $strScript .= '      }' . PHP_EOL;
            $strScript .= '      OpenLayers.Event.stop(evt);' . PHP_EOL;
            $strScript .= '   };' . PHP_EOL;
            $strScript .= '   marker.events.register("mousedown", feature, markerClick);' . PHP_EOL;
            $strScript .= '   layer.addMarker(marker);' . PHP_EOL;
            $strScript .= '   if (popup) {' . PHP_EOL;
            $strScript .= '      marker.feature.popup = marker.feature.createPopup(marker.feature.closeBox);' . PHP_EOL;
            $strScript .= '      map.addPopup(marker.feature.popup);' . PHP_EOL;
            $strScript .= '   }' . PHP_EOL;
            $strScript .= '   return marker;' . PHP_EOL;
            $strScript .= '}' . PHP_EOL;
        }

        $strScript .= 'function getLabels() {' . PHP_EOL;
        $strScript .= ' var pt1 = new OpenLayers.LonLat(7.123, 50.73).transform("EPSG:4326","EPSG:900913");' . PHP_EOL;
        $strScript .= ' var features = {' . PHP_EOL;
        $strScript .= '     "type": "FeatureCollection",' . PHP_EOL;
        $strScript .= '     "features": [' . PHP_EOL;
        $strScript .= '     { "type": "Feature", "geometry": {"type": "Point", "coordinates": [pt1.lon, pt1.lat]}},' . PHP_EOL;
        $strScript .= '     { "type": "Feature", "geometry": {"type": "Point", "coordinates": [790300, 6573900]}},' . PHP_EOL;
        $strScript .= '     { "type": "Feature", "geometry": {"type": "Point", "coordinates": [568600, 6817300]}}' . PHP_EOL;
        $strScript .= '     ]' . PHP_EOL;
        $strScript .= ' };' . PHP_EOL;
        $strScript .= ' var reader = new OpenLayers.Format.GeoJSON();' . PHP_EOL;
        $strScript .= ' return reader.read(features);' . PHP_EOL;
        $strScript .= '}' . PHP_EOL;

        $strScript .= '</script>' . PHP_EOL;

        return $strScript;
    }

    /**
     * Helper to find the max value from a variable count of values
     * @param array<float> $aValues
     * @return float
     */
    static public function maxValue(array $aValues) : float
    {
        $fltMax = null;
        $iCnt = count($aValues);
        for ($i = 0; $i < $iCnt; $i++) {
            if ($fltMax === null || $fltMax < $aValues[$i]) {
                $fltMax = $aValues[$i];
            }
        }
        return $fltMax ?? 0.0;
    }

    /**
     * Helper to find the min value from a variable count of values
     * @param array<float> $aValues
     * @return float
     */
    static public function minValue(array $aValues) : float
    {
        $fltMin = null;
        $iCnt = count($aValues);
        for ($i = 0; $i < $iCnt; $i++) {
            if ($fltMin === null || $fltMin > $aValues[$i]) {
                $fltMin = $aValues[$i];
            }
        }
        return $fltMin ?? 0.0;
    }

    /**
     * Get the distance between two point in km
     * @link https://www.kompf.de/gps/distcalc.html
     *
     * Since the points of a track are close to each other and a track can hold a
     * large number of points, we use the simplified method of distance measurement,
     * which requires less computing time  than the calculation method using
     * spherical trigonometry
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    static public function getDistance(float $lat1, float $lon1, float $lat2, float $lon2) : float
    {
        $lat = abs($lat1 + $lat2) / 2 * M_PI / 180;
        $dx = 111.3 * cos($lat) * abs($lon1 - $lon2);
        $dy = 111.3 * abs($lat1 - $lat2);
        return  sqrt($dx * $dx + $dy * $dy);
    }
}