<?php
declare(strict_types=1);

require_once 'autoloader.php';

use SKien\OSMap\OSMapEmbedded;
use SKien\OSMap\OSMapGPX;
use SKien\OSMap\OSMapMarker;

    // create map object, set view and specify CSS class for marker popups
$oMap = new OSMapEmbedded('OSMap', 'fr');
	$oMap->setPopupClass('mypopup');
	$oMap->showControls(OSMapEmbedded::ZOOMBAR | OSMapEmbedded::OVERVIEW | OSMapEmbedded::MOUSELOCATION | OSMapEmbedded::LAYERSWITCHER);

	/*
	$oMap->setView(46.57638889, 8.89263889, 18);

	$oMarker = $oMap->addMarker(46.57608333, 8.89241667);
	$oMarker->setIcon('images/blue-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);

	$oTrack = $oMap->addGPX('sample.gpx');
	$oTrack->setStyle('#990099');
	*/

	/*
	$oMap->setView(48.59132, 8.19219, 12);

	$oMarker = $oMap->addMarker(48.573832, 8.161735);
	$oMarker->setIcon('images/blue-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);

	$oTrack = $oMap->addGPX('sample2.gpx');
	$oTrack->setStyle('#990099');
	$oTrack->setTitle(OSMapGPX::TITLE_FROM_GPX);
	*/

	/*
	OSMapGPX::convJsonToGPX('test/OSMap/testdata/route1.json', 'test/OSMap/testdata/Brandenkopf.gpx', 'Brandenkopf');
	*/
/*
	$oTrack = $oMap->addGPX('test/OSMap/testdata/570fc97ff454baf65d0ac66c.gpx');
	$oTrack->setStyle('#990099', 4, 0.5);
	$oTrack->setTitle(OSMapGPX::TITLE_FROM_GPX);

	$oMarker = $oMap->addMarker($oTrack->getEnd());
	$oMarker->setIcon('images/checkered-flag.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::LEFT);
	$oMarker->setText('<b>You got it... (distance: ' . sprintf('%.2f', $oTrack->getDistance()) . ' km)</b>', 150, 20 );

	$oMap->setBounds($oTrack->getTopLeft(), $oTrack->getBottomRight());
*/

	$oTrack1 = $oMap->addGPX('SampleData/Schleswig-Holstein.gpx');
	$oTrack1->setStyle('#009999', 4, 0.5);
	$oTrack1->setTitle(OSMapGPX::TITLE_FROM_GPX);
	$oTrack1->suppressDistanceCalc();

	$oMarker = $oMap->addMarker($oTrack1->getCentre());
	$oMarker->setIcon('SampleData/red-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);

	$oTrack2 = $oMap->addGPX('SampleData/Bayern.gpx');
	$oTrack2->setStyle('#990099', 4, 0.5);
	$oTrack2->setTitle(OSMapGPX::TITLE_FROM_GPX);
	$oTrack2->suppressDistanceCalc();

	$oMarker = $oMap->addMarker($oTrack2->getCentre());
	$oMarker->setIcon('SampleData/blue-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);
	$oMarker->setText("<b>center of region...</b>", 150, 20 );

	$bounds = $oTrack1->getBounds();
	$bounds = $bounds->merge($oTrack2->getBounds());

	$oMap->setBounds($bounds);

	/*
	$oMap->setView(57.433, 11.922310, 15);

	// create some markers
	// standard marker with popup containing text and image
	$oMarker = $oMap->addMarker(57.431627, 11.922310);
	$oMarker->setText('<b>Halvar von Flake</b><br>H&auml;uptling<br>Gro&szlig;e H&uuml;tte<br>Am Thingplatz');
	$oMarker->setImage('images/sample3.bmp', 60);

	// marker with custom icon an popup containing text and image - popup will be initialy displayed
	$oMarker = $oMap->addMarker(57.432401, 11.920856);
	$oMarker->setIcon('images/red-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);
	$oMarker->setText("<b>Wicki von Flake</b><br>2'te H&uuml;tte<br>am See");
	$oMarker->setImage('images/sample1.jpg', 60);
	$oMarker->setInitialPopup();

	// marker with custom icon an popup without image
	$oMarker = $oMap->addMarker(57.430901, 11.922310);
	$oMarker->setIcon('images/green-pin.png', 24, 24, OSMapMarker::BOTTOM | OSMapMarker::RIGHT);
	$oMarker->setText("<b>Ylvi von Flake</b><br>... unterwegs", 150, 20 );

	// marker with custom icon without any popup
	$oMarker = $oMap->addMarker(57.430901, 11.920856);
	$oMarker->setIcon('images/blue-marker.png', 21, 25);
	*/
?>
<!DOCTYPE html>
<html style="height: 100%; width: 100%;">
<head>
<title>OpenStreetMap | Beispiel</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="de" />

<link rel="stylesheet" href="http://dev.openlayers.org/theme/default/style.css" type="text/css">
<link rel="stylesheet" href="http://dev.openlayers.org/examples/style.css" type="text/css">

<!-- now just create any needed javascript -->
<!--  <script src="OSMtest.js"></script>  -->
<?php echo $oMap->createScript(); ?>

<style>
/* some custom styles for popup... */
.mypopup
{
	font-family: Verdana, Arial, sans-serif;
	font-size: 0.6em;
}
.mypopup img
{
	float: right;
	margin-right: 20px;
}
</style>

</head>

<!-- drawOSMap must be called in the onload event -->
<body onload="drawOSMap();" style="height: 100%; width: 100%;">
	<div id="container" style="height: 80%; width: 95%; padding: 0; margin: 0 auto;">
		<!-- div will contain the map -->
		<div id="OSMap" style="height: 100%; width: 100%;"></div>
		<!-- copyright and helpers must be displayed -->
		<div id="osmCpyRight" style="width: 100%; text-align: right; font-size: 0.7em; font-style: italic;">
			&copy; <a href="http://www.openstreetmap.org">OpenStreetMap</a>
			 und <a href="http://www.openstreetmap.org/copyright">Mitwirkende</a>,
     		<a href="http://creativecommons.org/licenses/by-sa/2.0/deed.de">CC-BY-SA</a>
		</div>
	</div>
</body>
</html>