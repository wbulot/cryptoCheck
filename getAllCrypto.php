<?php
$con = mysqli_connect("xxx", "xxx", "xxx", "xxx");

include "ccxt/ccxt.php";
$binance = new \ccxt\binance ();
$markets = $binance->loadMarkets();

//On recupere toutes les coins de coinmarketcap en json
$currenciesJson = file_get_contents("https://s2.coinmarketcap.com/generated/search/quick_search.json");
//On convertit le json en array php
$currenciesParsedJson = json_decode($currenciesJson,1);
//Pour chaque coins
foreach ($currenciesParsedJson as $currency)
{

	foreach (array_keys($markets) as $key => $value)
	{
		if ($currency["symbol"] == "MIOTA")
		{
			$currency["symbol"] = "IOTA";
		}
		elseif ($currency["symbol"] == "BCH")
		{
			$currency["symbol"] = "BCC";
		}
		if ($value == $currency["symbol"]."/BTC")
		{
			//var_dump($markets[$value]);
			echo "ONBINANCE\n";
			$sqlB = 'UPDATE Currencies SET onBinance  = 1 WHERE name = "'.$currency["slug"].'"';
			$resultB = mysqli_query($con,$sqlB);
		}
	}

	//on insere le nom de currency en BDD
	$sql = 'SELECT name FROM Currencies WHERE name = "'.$currency["slug"].'"';
	$result = mysqli_query($con,$sql);
	if (mysqli_num_rows($result)==0)
	{
		$sql2 = 'INSERT INTO Currencies (rank, name, symbol) VALUES ("'.$currency["rank"].'", "'.$currency["slug"].'", "'.$currency["symbol"].'")';
		$result2 = mysqli_query($con,$sql2);
		echo $currency["rank"]." ".$currency["slug"]."\n";
		var_dump($result2);
	}
	else
	{
		$sql2 = 'UPDATE Currencies SET rank  = "'.$currency["rank"].'", symbol  = "'.$currency["symbol"].'" WHERE name = "'.$currency["slug"].'"';
		$result2 = mysqli_query($con,$sql2);
		echo $currency["rank"]." ".$currency["slug"]."\n";
		var_dump($result2);
	}

	// Binance part
	$sqlB = 'SELECT onBinance FROM Currencies WHERE name = "'.$currency["slug"].'"';
	$resultB = mysqli_query($con,$sqlB);
	$row = mysqli_fetch_array($resultB);

	if ($row["onBinance"] == 1)
	{
		$execResult = [];
		exec('python binance_get_historical_klines.py '.$currency["symbol"].'BTC', $execResult);
		//var_dump($execResult);
		unset($execResult[0]);
		$execResult = array_values($execResult);
		foreach ($execResult as $key => $line)
		{
			$values = explode(";",$line);
			$timestamp = $values[0];
			$price = $values[1];
			$date = date('Y-m-d H:i:s', substr($timestamp, 0, -3));
			echo "currency : ".$currency["slug"]."\n";
			echo "date : ".$date."\n";
			echo "price : ".$price."\n";

			$sqlB = 'SELECT value FROM prices WHERE nameCurrency = "bitcoin" AND DATE_FORMAT(prices.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d")';
			$resultB = mysqli_query($con,$sqlB);
			$row = mysqli_fetch_array($resultB);
			if (mysqli_num_rows($resultB) == 0) //If no result, then we have no bitcoin price for this day (because of summertime or something). So we take the price of the day before
			{
				//We need a price here
				$newDate = date('Y-m-d H:i:s', strtotime($date.'-1 day'));
				$sqlB2 = 'SELECT value FROM prices WHERE nameCurrency = "bitcoin" AND DATE_FORMAT(prices.date, "%y-%m-%d") = DATE_FORMAT("'.$newDate.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$bitcoinPrice = $row2["value"];
				echo "bitcoin price for same date : ".$bitcoinPrice."\n";
				$priceUSD = $row2["value"]*$price;
			}
			else
			{
				$bitcoinPrice = $row["value"];
				echo "bitcoin price for same date : ".$bitcoinPrice."\n";
				$priceUSD = $row["value"]*$price;
			}

			echo "price de la currency checker : ".$priceUSD."\n";

			if ($key > 0)
			{
				$date1j = date('Y-m-d H:i:s', strtotime($date.'-1 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date1j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD1D = $row2["value"];
				$percentage1jB = round(($priceUSD-$priceUSD1D)/$priceUSD1D*100,2);
				echo "percentage1j : $percentage1jB\n";
			}
			else
			{
				$percentage1jB = 0;
			}

			if ($key > 1)
			{
				$date2j = date('Y-m-d H:i:s', strtotime($date.'-2 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date2j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD2D = $row2["value"];
				$percentage2jB = round(($priceUSD-$priceUSD2D)/$priceUSD2D*100,2);
				echo "percentage2j : $percentage2jB\n";
			}
			else
			{
				$percentage2jB = 0;
			}

			if ($key > 2)
			{
				$date3j = date('Y-m-d H:i:s', strtotime($date.'-3 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date3j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD3D = $row2["value"];
				$percentage3jB = round(($priceUSD-$priceUSD3D)/$priceUSD3D*100,2);
				echo "percentage3j : $percentage3jB\n";
			}
			else
			{
				$percentage3jB = 0;
			}

			if ($key > 3)
			{
				$date4j = date('Y-m-d H:i:s', strtotime($date.'-4 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date4j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD4D = $row2["value"];
				$percentage4jB = round(($priceUSD-$priceUSD4D)/$priceUSD4D*100,2);
				echo "percentage4j : $percentage4jB\n";
			}
			else
			{
				$percentage4jB = 0;
			}

			if ($key > 4)
			{
				$date5j = date('Y-m-d H:i:s', strtotime($date.'-5 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date5j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD5D = $row2["value"];
				$percentage5jB = round(($priceUSD-$priceUSD5D)/$priceUSD5D*100,2);
				echo "percentage5j : $percentage5jB\n";
			}
			else
			{
				$percentage5jB = 0;
			}

			if ($key > 5)
			{
				$date6j = date('Y-m-d H:i:s', strtotime($date.'-6 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date6j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD6D = $row2["value"];
				$percentage6jB = round(($priceUSD-$priceUSD6D)/$priceUSD6D*100,2);
				echo "percentage6j : $percentage6jB\n";
			}
			else
			{
				$percentage6jB = 0;
			}

			if ($key > 6)
			{
				$date7j = date('Y-m-d H:i:s', strtotime($date.'-7 day'));
				$sqlB2 = 'SELECT value FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date7j.'", "%y-%m-%d")';
				$resultB2 = mysqli_query($con,$sqlB2);
				$row2 = mysqli_fetch_array($resultB2);

				$priceUSD7D = $row2["value"];
				$percentage7jB = round(($priceUSD-$priceUSD7D)/$priceUSD7D*100,2);
				echo "percentage7j : $percentage7jB\n";
			}
			else
			{
				$percentage7jB = 0;
			}

			//We check if the line already exist in BDD, if not, we insert
			$sqlB2 = 'SELECT * FROM pricesB WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d")';
			$resultB2 = mysqli_query($con,$sqlB2);
			if (mysqli_num_rows($resultB2)==0)
			{
				$sqlB = 'INSERT INTO pricesB (nameCurrency, date, value, perfDay, perf2Day, perf3Day, perf4Day, perf5Day, perf6Day, perfWeek) VALUES ("'.$currency["slug"].'", "'.$date.'", "'.$priceUSD.'", "'.$percentage1jB.'", "'.$percentage2jB.'", "'.$percentage3jB.'", "'.$percentage4jB.'", "'.$percentage5jB.'", "'.$percentage6jB.'", "'.$percentage7jB.'")';
				$resultB = mysqli_query($con,$sqlB);
			}
		}
	}
	//End of Binance part

	$sql2 = 'SELECT * FROM prices WHERE nameCurrency = "'.$currency["slug"].'"';
	$result2 = mysqli_query($con,$sql2);
	if (mysqli_num_rows($result2)<=2)
	{
		//Pas de data, on insert tout sans optimisation
		$pricesJson = file_get_contents("https://graphs2.coinmarketcap.com/currencies/".$currency["slug"]."/");
		$pricesParsedJson = json_decode($pricesJson,1);

		foreach ($pricesParsedJson["price_usd"] as $key => $thisDay)
		{
			$date = date('Y-m-d H:i:s', substr($thisDay[0], 0, -3));
			//Calcule du pourcentage pour -1j
			if($key > 0)
			{
				$percentage1j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-1][1])/$pricesParsedJson["price_usd"][$key-1][1];
				$percentage1j = $percentage1j*100;
				$percentage1j = round($percentage1j,2);
			}
			else
			{
				$percentage1j = 0;
			}
			//Calcule du pourcentage pour -2j
			if($key > 1)
			{
				$percentage2j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-2][1])/$pricesParsedJson["price_usd"][$key-2][1];
				$percentage2j = $percentage2j*100;
				$percentage2j = round($percentage2j,2);
			}
			else
			{
				$percentage2j = 0;
			}
			//Calcule du pourcentage pour -3j
			if($key > 2)
			{
				$percentage3j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-3][1])/$pricesParsedJson["price_usd"][$key-3][1];
				$percentage3j = $percentage3j*100;
				$percentage3j = round($percentage3j,2);
			}
			else
			{
				$percentage3j = 0;
			}
			//Calcule du pourcentage pour -4j
			if($key > 3)
			{
				$percentage4j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-4][1])/$pricesParsedJson["price_usd"][$key-4][1];
				$percentage4j = $percentage4j*100;
				$percentage4j = round($percentage4j,2);
			}
			else
			{
				$percentage4j = 0;
			}
			//Calcule du pourcentage pour -5j
			if($key > 4)
			{
				$percentage5j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-5][1])/$pricesParsedJson["price_usd"][$key-5][1];
				$percentage5j = $percentage5j*100;
				$percentage5j = round($percentage5j,2);
			}
			else
			{
				$percentage5j = 0;
			}
			//Calcule du pourcentage pour -6j
			if($key > 5)
			{
				$percentage6j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-6][1])/$pricesParsedJson["price_usd"][$key-6][1];
				$percentage6j = $percentage6j*100;
				$percentage6j = round($percentage6j,2);
			}
			else
			{
				$percentage6j = 0;
			}
			//Calcule du pourcentage pour -7j
			if($key > 6)
			{
				$percentage7j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-7][1])/$pricesParsedJson["price_usd"][$key-7][1];
				$percentage7j = $percentage7j*100;
				$percentage7j = round($percentage7j,2);
			}
			else
			{
				$percentage7j = 0;
			}

			$sql3 = 'SELECT * FROM prices WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d")';
			$result3 = mysqli_query($con,$sql3);
			if (mysqli_num_rows($result3)==0)
			{
				$sql4 = 'INSERT INTO prices (nameCurrency, date, value, perfDay, perf2Day, perf3Day, perf4Day, perf5Day, perf6Day, perfWeek) VALUES ("'.$currency["slug"].'", "'.$date.'", "'.$thisDay[1].'", "'.$percentage1j.'", "'.$percentage2j.'", "'.$percentage3j.'", "'.$percentage4j.'", "'.$percentage5j.'", "'.$percentage6j.'", "'.$percentage7j.'")';
				$result4 = mysqli_query($con,$sql4);
				echo $currency['slug']." ".$key."\n";
				var_dump($result4);
			}
		}
	}
	else
	{
		//on a trouvé la coin avec deja des datas en BDD, on traite uniquement les nouvelles entrées
		while ($row = mysqli_fetch_array($result2))
		{
			$currency["nameCurrency"][] = $row["nameCurrency"];
			$currency["date"][] = $row["date"];
			$currency["value"][] = $row["value"];
			$currency["perfDay"][] = $row["perfDay"];
			$currency["perfWeek"][] = $row["perfWeek"];
		}
		//Si la derniere date trouvé est inferieur à la date d'aujourd'hui
		if(date('Ymd', strtotime($currency["date"][count($currency["date"])-1])) < date('Ymd', time()))
		{
			echo "DATE INFERIEUR detectée !!!!\n";
			$pricesJson = file_get_contents("https://graphs2.coinmarketcap.com/currencies/".$currency["slug"]."/");
			$pricesParsedJson = json_decode($pricesJson,1);

			foreach ($pricesParsedJson["price_usd"] as $key => $thisDay)
			{
				$date = date('Y-m-d H:i:s', substr($thisDay[0], 0, -3));
				//Calcule du pourcentage pour -1j
				if($key > 0)
				{
					$percentage1j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-1][1])/$pricesParsedJson["price_usd"][$key-1][1];
					$percentage1j = $percentage1j*100;
					$percentage1j = round($percentage1j,2);
				}
				else
				{
					$percentage1j = 0;
				}
				//Calcule du pourcentage pour -2j
				if($key > 1)
				{
					$percentage2j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-2][1])/$pricesParsedJson["price_usd"][$key-2][1];
					$percentage2j = $percentage2j*100;
					$percentage2j = round($percentage2j,2);
				}
				else
				{
					$percentage2j = 0;
				}
				//Calcule du pourcentage pour -3j
				if($key > 2)
				{
					$percentage3j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-3][1])/$pricesParsedJson["price_usd"][$key-3][1];
					$percentage3j = $percentage3j*100;
					$percentage3j = round($percentage3j,2);
				}
				else
				{
					$percentage3j = 0;
				}
				//Calcule du pourcentage pour -4j
				if($key > 3)
				{
					$percentage4j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-4][1])/$pricesParsedJson["price_usd"][$key-4][1];
					$percentage4j = $percentage4j*100;
					$percentage4j = round($percentage4j,2);
				}
				else
				{
					$percentage4j = 0;
				}
				//Calcule du pourcentage pour -5j
				if($key > 4)
				{
					$percentage5j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-5][1])/$pricesParsedJson["price_usd"][$key-5][1];
					$percentage5j = $percentage5j*100;
					$percentage5j = round($percentage5j,2);
				}
				else
				{
					$percentage5j = 0;
				}
				//Calcule du pourcentage pour -6j
				if($key > 5)
				{
					$percentage6j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-6][1])/$pricesParsedJson["price_usd"][$key-6][1];
					$percentage6j = $percentage6j*100;
					$percentage6j = round($percentage6j,2);
				}
				else
				{
					$percentage6j = 0;
				}
				//Calcule du pourcentage pour -7j
				if($key > 6)
				{
					$percentage7j = ($pricesParsedJson["price_usd"][$key][1]-$pricesParsedJson["price_usd"][$key-7][1])/$pricesParsedJson["price_usd"][$key-7][1];
					$percentage7j = $percentage7j*100;
					$percentage7j = round($percentage7j,2);
				}
				else
				{
					$percentage7j = 0;
				}

				if(date('Ymd', strtotime($date)) >= date('Ymd', strtotime($currency["date"][count($currency["date"])-1])))
				{
					$sql3 = 'SELECT * FROM prices WHERE nameCurrency = "'.$currency["slug"].'" AND DATE_FORMAT(date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d")';
					$result3 = mysqli_query($con,$sql3);
					if (mysqli_num_rows($result3)==0)
					{
						$sql4 = 'INSERT INTO prices (nameCurrency, date, value, perfDay, perf2Day, perf3Day, perf4Day, perf5Day, perf6Day, perfWeek) VALUES ("'.$currency["slug"].'", "'.$date.'", "'.$thisDay[1].'", "'.$percentage1j.'", "'.$percentage2j.'", "'.$percentage3j.'", "'.$percentage4j.'", "'.$percentage5j.'", "'.$percentage6j.'", "'.$percentage7j.'")';
						$result4 = mysqli_query($con,$sql4);
						echo $currency['slug']." ".$key."\n";
						var_dump($result4);
					}
				}
			}
		}
	}

	// //Si le dossier du coin existe pas, on le créer
	// if(is_dir("./prices/".$currency["slug"]))
	// {
	// 	echo "Folder ".$currency["slug"]." exist\n";
	// }
	// else
	// {
	// 	echo "New coin added : ".$currency["slug"]."\n";
	// 	mkdir("./prices/".$currency["slug"]);
	// }

	// if (file_exists("./prices/".$currency["slug"]."/prices.json"))
	// {
	// 	//Si les prix de la currency existe dans la dossier
	// 	if(filemtime("./prices/".$currency["slug"]."/prices.json") < (time()-86400))
	// 	{
	// 		//Si le fichier date de plus de 24h, on update le fichier
	// 		echo "Prices too old, we update it\n";
	// 		unlink("./prices/".$currency["slug"]."/prices.json");
	// 		file_put_contents("./prices/".$currency["slug"]."/prices.json", file_get_contents("https://graphs2.coinmarketcap.com/currencies/".$currency["slug"]."/"));
	// 		sleep(2);
	// 	}
	// }
	// else
	// {
	// 	//Si pas de prix dans le dossier, on telecharge les prix
	// 	echo "Prices doesnt exist. We donwload the data\n";
	// 	file_put_contents("./prices/".$currency["slug"]."/prices.json", file_get_contents("https://graphs2.coinmarketcap.com/currencies/".$currency["slug"]."/"));
	// 	sleep(2);
	// }
}

// $pricesJson = file_get_contents("https://graphs2.coinmarketcap.com/currencies/bitcoin/");
// $pricesParsedJson = json_decode($pricesJson,1);
// $pricesUsdParsedJson = $pricesParsedJson["price_usd"];
// var_dump($pricesUsdParsedJson);


?>
