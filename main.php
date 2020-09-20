<?php

include('oauth1ClientBuilder.php');

$clientBuilder = new OAuth1ClientBuilder();
$client = $clientBuilder->buildClient();

$meters = $client->getMeters();
$meterId = $meters[0]->meterId;
$meterSerialnumber = $meters[0]->fullSerialNumber;

echo "Fetching data for meter with ID ${meterId} (full serial number: ${meterSerialnumber})\n";

// Use these fields if you want to know your meter's count using the getReadings method:
//$fieldMeterEnergyFromGrid = 'energy';
//$fieldMeterEnergyProduced = 'energyOut';

// This field denotes your power consumption (positive values) or production (negative values)
$fieldPower = 'power';

$latestPowerValue = json_decode($client->getLastReading($meterId, $fieldPower))->values->power;
$energyProduced = $client->getReadings($meterId, $fieldPower, 1603551620000, 1603552320000, 'raw');
$statistics = $client->getStatistics($meterId, $fieldPower,1601121600000, 1603552320000);

echo "Energy produced/from grid: $energyProduced\n";
echo "latest measured value: ${latestPowerValue}\n";
//echo "Statistics: $statistics\n";
//var_dump($statistics);
