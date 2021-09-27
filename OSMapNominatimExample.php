<?php
declare(strict_types=1);

require_once 'autoloader.php';

use SKien\OSMap\OSMapNominatim;

echo '<!DOCTYPE html>' . PHP_EOL;
echo '<html>' . PHP_EOL;
echo '<head>' . PHP_EOL;
echo '<title>OSMapNominatim example</title>' . PHP_EOL;
echo '</head>' . PHP_EOL;
echo '<body>' . PHP_EOL;

/**
 * similar code may executed in a ajax call from a form to complete address/contact information
 * NOTE, call it by onclick() on button or somthing else and NOT in a onchange() handler
 * for auto complete
 * See Nominatim usage policy
 * @link https://operations.osmfoundation.org/policies/nominatim
 */

$oOSMap = new OSMapNominatim();
$oOSMap->setLanguage('de-DE,de');

$oOSMap->setStr('10 Downing Street');
$oOSMap->setCity('London');

$oOSMap->setStreetFormat(OSMapNominatim::STREET_NR_NAME);

if ($oOSMap->searchAddress()) {
	echo '<h2>Result</h2>' . PHP_EOL;
	echo '<h3>Full Address:</h3>' . PHP_EOL;
	echo $oOSMap->getStr() . '<br/>' . PHP_EOL;
	echo $oOSMap->getPostcode() . ' ' . $oOSMap->getCity() . '<br/>' . PHP_EOL;
	echo $oOSMap->getCountry() . ' (' . $oOSMap->getRegion() . ')<br/>' . PHP_EOL;
	$strInfo = $oOSMap->getInfo();
	if (strlen($strInfo) > 0) {
		echo '<h3>Information:</h3>' . PHP_EOL;
		echo $strInfo . '<br/>' . PHP_EOL;
	}
	echo '<h3>Location:</h3>' . PHP_EOL;
	echo 'latitude: ' . $oOSMap->getLatitude() . '<br/>' . PHP_EOL;
	echo 'longitude: ' . $oOSMap->getLongitude() . '<br/>' . PHP_EOL;
	echo 'location (DDD): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DDD) . '<br/>' . PHP_EOL;
	echo 'location (DMM): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DMM) . '<br/>' . PHP_EOL;
	echo 'location (DMS): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DMS) . '<br/>' . PHP_EOL;
}

$oOSMap->reset();
$oOSMap->setLatitude('40.4522186');
$oOSMap->setLongitude('-3.6889077');
// same effect:   $oOSMap->setLocation('40.4522186, -3.6889077');
$oOSMap->setStreetFormat(OSMapNominatim::STREET_NAME_NR, ',');

if ($oOSMap->searchLocation()) {
	echo '<h2>Result</h2>' . PHP_EOL;
	echo '<h3>Full Address:</h3>' . PHP_EOL;
	echo $oOSMap->getStr() . '<br/>' . PHP_EOL;
	echo $oOSMap->getPostcode() . ' ' . $oOSMap->getCity() . '<br/>' . PHP_EOL;
	echo $oOSMap->getCountry() . ' (' . $oOSMap->getRegion() . ')<br/>' . PHP_EOL;
	$strInfo = $oOSMap->getInfo();
	if (strlen($strInfo) > 0) {
		echo '<h3>Location:</h3>' . PHP_EOL;
		echo $strInfo . '<br/>' . PHP_EOL;
	}
	echo '<h3>Location:</h3>' . PHP_EOL;
	echo 'latitude: ' . $oOSMap->getLatitude() . '<br/>' . PHP_EOL;
	echo 'longitude: ' . $oOSMap->getLongitude() . '<br/>' . PHP_EOL;
	echo 'location (DDD): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DDD) . '<br/>' . PHP_EOL;
	echo 'location (DMM): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DMM) . '<br/>' . PHP_EOL;
	echo 'location (DMS): ' . $oOSMap->getLocation(OSMapNominatim::FORMAT_DMS) . '<br/>' . PHP_EOL;
}

echo '</body>' . PHP_EOL;
echo '</html>' . PHP_EOL;
