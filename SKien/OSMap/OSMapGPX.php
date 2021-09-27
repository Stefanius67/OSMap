<?php
declare(strict_types=1);

namespace SKien\OSMap;

use SKien\ExtDOM\ExtDOMDocument;

/**
 * Class representing GPX track/region for display in a OSMap.
 * @link https://www.topografix.com/gpx.asp
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapGPX
{
    /** try to read title from GPX - file */
    public const TITLE_FROM_GPX = '_FROM_GPX_';

    /** @var string name of the GPX file */
    protected string $strFilename = '';
    /** @var string title of the track/region */
    protected string $strTitle = '';
    /** @var string description */
    protected string $strDescription = '';
    /** @var string stroke color: rgb-value in CSS format or valid CSS color name */
    protected string $strStrokeColor = '';
    /** @var int stroke width in pixels */
    protected int $iStrokeWidth = 5;
    /** @var float stroke opacity (value between 0.0 and 1.0) */
    protected float $fltStrokeOpacity = 1.0;

    /** @var OSMapPoint centre point */
    protected OSMapPoint $ptCentre;
    /** @var OSMapBounds bounds    */
    protected OSMapBounds $bounds;
    /** @var OSMapPoint startpoint */
    protected OSMapPoint $ptStart;
    /** @var OSMapPoint endpoint */
    protected OSMapPoint $ptEnd;
    /** @var float distance in km  */
    protected float $fltDistance = 0.0;
    /** @var bool to prevent multiple parsing   */
    private bool $bFileParsed = false;
    /** @var bool to prevent calculation of distance (may takes lot of time for large regions...)   */
    private bool $bSuppressDistance = false;

    /**
     * Creates a new GPX object.
     * @param string $strFilename
     */
    public function __construct(string $strFilename)
    {
        $this->strTitle = 'GPX';
        $this->strStrokeColor = '#0000FF';
        $this->iStrokeWidth = 5;
        $this->fltStrokeOpacity = 1.0;
        $this->ptCentre = new OSMapPoint();
        $this->bounds = new OSMapBounds();
        $this->ptStart = new OSMapPoint();
        $this->ptEnd = new OSMapPoint();
        if (file_exists($strFilename)) {
            $this->strFilename = $strFilename;
        } else {
            trigger_error( $strFilename . ' not found!', E_USER_WARNING);
        }
    }

    /**
     * Set the title of track/region.
     * If OSMapGPX::TITLE_FROM_GPX method try to read title from GPX-file
     * @param  string $strTitle
     */
    public function setTitle(string  $strTitle = self::TITLE_FROM_GPX) : void
    {
        if ($strTitle == self::TITLE_FROM_GPX) {
            $this->strTitle = htmlentities($this->readTitleFromGPX(), ENT_HTML5 | ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8');
        } else {
            $this->strTitle = htmlentities($strTitle, ENT_HTML5 | ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Set the style of line
     * @param string $strColor      rgb-value in CSS format or valid CSS color name
     * @param int $iWidth           width in pixels
     * @param float $fltOpacity     value between 0.0 and 1.0 (at least 0.0 is invisible!)
     */
    public function setStyle(string $strColor, int $iWidth = 5, float $fltOpacity = 1.0) : void
    {
        $this->strStrokeColor = $strColor;
        $this->iStrokeWidth = $iWidth;
        if ($fltOpacity >  0.0 && $fltOpacity <= 1.0) {
            $this->fltStrokeOpacity = $fltOpacity;
        }
    }

    /**
     * Create the JS script to insert the GPX.
     * Each Track will be created in an own layer. <br/>
     * Method is called by the embedding OSMEmbedded object
     * @param int $iNr
     * @return string
     */
    public function createScript(int $iNr) : string
    {
        $strScript = '';
        if (strlen($this->strFilename) > 0) {
            $strScript  = '   var layerGPX' . $iNr . ' = new OpenLayers.Layer.Vector("' . $this->strTitle . '", {' . PHP_EOL;
            $strScript .= '     strategies: [new OpenLayers.Strategy.Fixed()],' . PHP_EOL;
            $strScript .= '     protocol: new OpenLayers.Protocol.HTTP({' . PHP_EOL;
            $strScript .= '         url: "' . $this->strFilename . '",' . PHP_EOL;
            $strScript .= '         format: new OpenLayers.Format.GPX()' . PHP_EOL;
            $strScript .= '     }),' . PHP_EOL;
            $strScript .= '     style: {' . PHP_EOL;
            $strScript .= '        strokeColor: "' . $this->strStrokeColor . '", ' . PHP_EOL;
            $strScript .= '        strokeWidth: ' . $this->iStrokeWidth . ', ' . PHP_EOL;
            $strScript .= '        strokeOpacity: ' . $this->fltStrokeOpacity . '' . PHP_EOL;
            $strScript .= '     },' . PHP_EOL;
            $strScript .= '     projection: new OpenLayers.Projection("EPSG:4326")' . PHP_EOL;
            $strScript .= '   });' . PHP_EOL;
            $strScript .= '   map.addLayer(layerGPX' . $iNr . ');' . PHP_EOL;
        }
        return $strScript;
    }

    /** @return OSMapPoint   */
    public function getCentre() : OSMapPoint
    {
        $this->measureGPX();
        return $this->ptCentre;
    }

    /** @return OSMapBounds   */
    public function getBounds() : OSMapBounds
    {
        $this->measureGPX();
        return $this->bounds;
    }

    /** @return OSMapPoint   */
    public function getStart() : OSMapPoint
    {
        $this->measureGPX();
        return $this->ptStart;
    }

    /** @return OSMapPoint   */
    public function getEnd() : OSMapPoint
    {
        $this->measureGPX();
        return $this->ptEnd;
    }

    /** @return float    */
    public function getDistance() : float
    {
        $this->measureGPX();
        return $this->fltDistance;
    }

    /**
     * @param bool $bSuppressDistance
     */
    public function suppressDistanceCalc(bool $bSuppressDistance = true) : void
    {
        $this->bSuppressDistance = $bSuppressDistance;
    }

    /**
     * Try to get title from the GPX file.
     * @return string
     */
    public function readTitleFromGPX() : string
    {
        $strName = null;
        if (strlen($this->strFilename) > 0) {
            $strName = 'untitled';
            $oGPX = new ExtDOMDocument();
            if ($oGPX->load($this->strFilename)) {
                $strName = $oGPX->getNodeValue('/gpx/name');
                if ($strName === null || strlen($strName) == 0) {
                    $strName = $oGPX->getNodeValue('/gpx/trk/name');
                }
            }
        }
        return $strName ?? 'invalid';
    }

    /**
     * Try to get description from the GPX file.
     * @return string
     */
    public function readDescFromGPX() : string
    {
        $strDesc = null;
        if (strlen($this->strFilename) > 0) {
            $strDesc = '';
            $oGPX = new ExtDOMDocument();
            if ($oGPX->load($this->strFilename)) {
                $strDesc = $oGPX->getNodeValue('/gpx/desc');
                if ($strDesc === null || strlen($strDesc) == 0) {
                    $strDesc = $oGPX->getNodeValue('/gpx/trk/desc');
                }
            }
        }
        return $strDesc ?? 'invalid';
    }

    /* much to slow for GPX files > 1MB ...
     protected function measureGPX_slow() {
     // only read once
     if (!$this->bFileParsed) {
     $oGPX = new \DOMDocument();
     if ($oGPX->load($this->strFilename)) {
     // surprisingly XPath version takes up to 10 times longer the the 'plain' loop
     // maybe that's because PHP only supports XPath 1.0 so far (?)
     // ... or that with XPath four single calls have to be made that can be coded plain in one loop
     //
     // $oXPath = new \DOMXPath($oGPX);
     // $this->bounds->ptTopLeft->lat = floatval($this->xpathQueryValue($oXPath, '(//@lat[not(. < //@lat)])'));
     // $this->bounds->ptBottomRight->lat = floatval($this->xpathQueryValue($oXPath, '(//@lat[not(. > //@lat)])'));
     // $this->bounds->ptBottomRight->lon = floatval($this->xpathQueryValue($oXPath, '(//@lon[not(. < //@lon)])'));
     // $this->bounds->ptTopLeft->lon = floatval($this->xpathQueryValue($oXPath, '(//@lon[not(. > //@lon)])'));

     $oNodelist = $oGPX->getElementsByTagName('trkpt');
     for ($i = 0; $i < $oNodelist->length; $i++) {
     $lat = floatval($oNodelist->item($i)->getAttribute('lat'));
     $lon = floatval($oNodelist->item($i)->getAttribute('lon'));
     if ($this->bounds->ptTopLeft->lat == null || $this->bounds->ptTopLeft->lat < $lat) {
     $this->bounds->ptTopLeft->lat = $lat;
     }
     if ($this->bounds->ptBottomRight->lat == null || $this->bounds->ptBottomRight->lat > $lat) {
     $this->bounds->ptBottomRight->lat = $lat;
     }
     if ($this->bounds->ptBottomRight->lon == null || $this->bounds->ptBottomRight->lon < $lon) {
     $this->bounds->ptBottomRight->lon = $lon;
     }
     if ($this->bounds->ptTopLeft->lon == null || $this->bounds->ptTopLeft->lon > $lon) {
     $this->bounds->ptTopLeft->lon = $lon;
     }
     }

     $this->latCentre = ($this->bounds->ptTopLeft->lat + $this->bounds->ptBottomRight->lat) / 2;
     $this->lonCentre = ($this->bounds->ptBottomRight->lon + $this->bounds->ptTopLeft->lon) / 2;
     }
     $this->bFileParsed = true;
     }
     }
     */

    /**
     * Measures the track/region.
     * determines
     * - centre of track
     * - top-left and bottom-right points
     * - start and endpoint
     * from XML data.
     */
    protected function measureGPX() : void
    {
        // only read once
        if (strlen($this->strFilename) > 0 && !$this->bFileParsed) {
            /*
             * Since GPX files can contain large amounts of data (some MB's are not uncommon!),
             * we use the XMLReader, which works much faster (!) than a DOMDocument
             * DOMDocument / DOMXPath loads entire document into memory... XMLReader works on a stream!
             */
            $oReader = new \XMLReader();
            if ($oReader->open($this->strFilename)) {
                while ($oReader->read()) {
                    if ($oReader->nodeType == \XMLReader::ELEMENT && ($oReader->name == 'trkpt' || $oReader->name == 'rtept')) {
                        $lat = floatval($oReader->getAttribute('lat'));
                        $lon = floatval($oReader->getAttribute('lon'));
                        if ($this->bounds->ptTopLeft->lat == null || $this->bounds->ptTopLeft->lat < $lat) {
                            $this->bounds->ptTopLeft->lat = $lat;
                        }
                        if ($this->bounds->ptBottomRight->lat == null || $this->bounds->ptBottomRight->lat > $lat) {
                            $this->bounds->ptBottomRight->lat = $lat;
                        }
                        if ($this->bounds->ptBottomRight->lon == null || $this->bounds->ptBottomRight->lon < $lon) {
                            $this->bounds->ptBottomRight->lon = $lon;
                        }
                        if ($this->bounds->ptTopLeft->lon == null || $this->bounds->ptTopLeft->lon > $lon) {
                            $this->bounds->ptTopLeft->lon = $lon;
                        }
                        if (!$this->bSuppressDistance) {
                            if ($this->ptEnd->isSet() && ($this->ptEnd->lat != $lat || $this->ptEnd->lon != $lon)) {
                                $this->fltDistance += OSMapEmbedded::getDistance($lat, $lon, $this->ptEnd->lat, $this->ptEnd->lon);
                            }
                        }
                        if ($this->ptStart->isSet()) {
                            $this->ptStart->lat = $lat;
                            $this->ptStart->lon = $lon;
                        }
                        $this->ptEnd->lat = $lat;
                        $this->ptEnd->lon = $lon;
                    }
                }
                $this->ptCentre->lat = ($this->bounds->ptTopLeft->lat + $this->bounds->ptBottomRight->lat) / 2;
                $this->ptCentre->lon = ($this->bounds->ptBottomRight->lon + $this->bounds->ptTopLeft->lon) / 2;
            }
            $this->bFileParsed = true;
        }
    }

    /**
     * Converts JSON data to GPX.
     * @param string $strJsonFilename
     * @param string $strGPXFilename
     * @param string $strName
     */
    static public function convJsonToGPX(string $strJsonFilename, string $strGPXFilename, string $strName) : void
    {
        $strContent = file_get_contents($strJsonFilename);
        if ($strContent !== false) {
            $jsonData = json_decode($strContent, false);
            if ($jsonData) {
                $oDoc = new ExtDOMDocument();
                $oDoc->formatOutput = true;
                $oRoot = $oDoc->createElement('gpx');
                if ($oRoot !== false) {
                    $oRoot->setAttribute('version', '1.1');
                    $oRoot->setAttribute('creator', $_SERVER['SERVER_NAME']);
                    $oRoot->setAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
                    $oDoc->appendChild($oRoot);
                    $oDoc->addChild('name', $strName, $oRoot);
                    $oMeta = $oDoc->addChild('metadata', '', $oRoot);
                    if ($oMeta !== false) {
                        $oDoc->addChild('name', $strName, $oMeta);
                        $oDoc->addChild('link', $_SERVER['SERVER_NAME'], $oMeta);
                        $oDoc->addChild('time', date(DATE_ATOM), $oMeta);
                    }
                    $oTrk = $oDoc->addChild('trk', '', $oRoot);
                    if ($oTrk!== false) {
                        $oDoc->addChild('name', $strName, $oTrk);
                        $oTrkSeg = $oDoc->addChild('trkseg', '', $oTrk);
                        if ($oTrkSeg !== false) {
                            foreach ($jsonData as $oJsonPt) {
                                $oTrkPt = $oDoc->addChild('trkpt', '', $oTrkSeg);
                                if ($oTrkPt !== false) {
                                    $oTrkPt->setAttribute('lat', $oJsonPt->latitude);
                                    $oTrkPt->setAttribute('lon', $oJsonPt->longitude);
                                }
                                $oDoc->addChild('ele', $oJsonPt->altitude, $oTrkSeg);
                            }
                            $oDoc->save($strGPXFilename);
                        }
                    }
                }
            }
        }
    }
}