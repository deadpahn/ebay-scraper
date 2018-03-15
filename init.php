<?php
include("config.php");

parseJsonFiles();

function parseJsonFiles()
{
	$pathToFiles = "./searches/";
	$files = scandir($pathToFiles);
	unset($files[0]);
	unset($files[1]);
	
	foreach($files as $fileName) {	
		$file = $pathToFiles . $fileName;
		$json = file_get_contents($file, true);
		$arrResults = json_decode($json, true);
		
		$success = $arrResults["findItemsByKeywordsResponse"][0]["ack"][0];
		
		if ($success == "Success") {
			$resultCount = $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["@count"];
			if($resultCount > 0){
				$itemNumber = 0;			
				while($itemNumber < $resultCount){						
					$itemInfoForCSV = "";
					$itemInfoForCSV = array(
						'name' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["title"][0],
						'price' => (float) $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["sellingStatus"][0]["convertedCurrentPrice"][0]["__value__"],
						'shipping' => (float) $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["shippingInfo"][0]["shippingServiceCost"][0]["__value__"],
						'itemUrl' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["viewItemURL"][0],
						'picUrl' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["galleryURL"][0],
						'postalCode' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["postalCode"][0],
						'location' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["location"][0],
						'country' => $arrResults["findItemsByKeywordsResponse"][0]["searchResult"][0]["item"][$itemNumber]["country"][0],
					);
					
					$totalCost = $itemInfoForCSV['price'] + $itemInfoForCSV['shipping'];
					
					$itemInfoForCSV['totalCost'] = $totalCost;
					
					$itemNumber++;
					
					writeCsv($itemInfoForCSV);
					
				}
			}
		}
	}
}

function createJsonFiles()
{
	//5,000 API calls per day EBAY LIMIT
	$gameList = file_get_contents('listOfSearchTerms.txt', true);
	$arrGames = explode("\n", $gameList);

	foreach($arrGames as $gameToSearchFor){
		$searchSalt = SEARCH_SALT;
		$searchTerm = $gameToSearchFor . $searchSalt;
		$searchTerm = urlencode($searchTerm);//url encode
		sleep(2);
		callEbay($searchTerm);
	}	
}


function callEbay($searchTerm){
$appid 	= APPID;
$devid 	= DEVID;
$certid = CERTID;

$operationsName = "findItemsByKeywords";
$itemsLimit = 4;
$itemNumber = 1;
 
$ebayServiceUrl = "http://svcs.ebay.ca/services/search/FindingService/v1?OPERATION-NAME={$operationsName}";
$ebayServiceUrl .= "&SECURITY-APPNAME={$appid}";
$ebayServiceUrl .= "&RESPONSE-DATA-FORMAT=JSON";
$ebayServiceUrl .= "&REST-PAYLOAD";
$ebayServiceUrl .= "&GLOBAL-ID=EBAY-ENCA";
$ebayServiceUrl .= "&sortOrder=PricePlusShippingLowest";
$ebayServiceUrl .= "&buyItNowAvailable=true";
$ebayServiceUrl .= "&keywords={$searchTerm}";
$ebayServiceUrl .= "&paginationInput.entriesPerPage={$itemsLimit}";
$ebayServiceUrl = str_replace(PHP_EOL, "", $ebayServiceUrl);

$json = file_get_contents($ebayServiceUrl);

storeJsonToFile($json, $searchTerm);

}

function storeJsonToFile($json, $searchTerm){
	$file = "./info/{$searchTerm}";
	file_put_contents($file, $json);
}

function writeCsv($item) {
	$item = array('totalCost' => $item['totalCost']) + $item;
	$csvString = implode("|", $item);
	$csvString = $csvString . "\n";
	echo $csvString;	
}

?>
