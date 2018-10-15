<?php
$con = mysqli_connect("xxx", "xxx", "xxx", "xxx");
//On recupere toutes les coins de coinmarketcap en json
$currenciesJson = file_get_contents("https://s2.coinmarketcap.com/generated/search/quick_search.json");
//On convertit le json en array php
$currenciesParsedJson = json_decode($currenciesJson,1);
//Pour chaque coins
foreach ($currenciesParsedJson as $currency)
{
	if($currency["slug"] != "bitcoin")
	{
		$pricesJson = file_get_contents("./prices/".$currency["slug"]."/prices.json");
		$pricesParsedJson = json_decode($pricesJson,1);
		if (file_exists("./prices/".$currency["slug"]."/calcPrices.json"))
		{
			unlink("./prices/".$currency["slug"]."/calcPrices.json");
		}

		foreach ($pricesParsedJson["price_usd"] as $key => $thisDay)
		{
			$date = date('Ymd', substr($thisDay[0], 0, -3));
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
			//On creer la ligne du CSV de la coin
			//echo $key.";".$date.";".$thisDay[1].";".$percentage1j.";".$percentage7j."\n";

			if (file_exists("./prices/".$currency["slug"]."/calcPrices.json"))
			{
				$file = "./prices/".$currency["slug"]."/calcPrices.json";
				$data = file($file);
				$line = $data[count($data)-1];
				$lastDate = explode(";",$line);
				$lastDate = $lastDate[1];
				//echo $lastDate."\n";
				//echo $date."\n";
			}
			else { $lastDate = "0"; }
			if($lastDate != $date)
			{
				file_put_contents("./prices/".$currency["slug"]."/calcPrices.json", $key.";".$date.";".$thisDay[1].";".$percentage1j.";".$percentage7j."\n", FILE_APPEND);

				$sql = 'SELECT * FROM prices WHERE nameCurrency = "'.$currency["slug"].'" AND date = "'.$date.'"';
				$result = mysqli_query($con,$sql);
				if (mysqli_num_rows($result)==0)
				{
					$sql2 = 'INSERT INTO prices (nameCurrency, date, value, perfDay, perfWeek) VALUES ("'.$currency["slug"].'", "'.$date.'", "'.$thisDay[1].'", "'.$percentage1j.'", "'.$percentage7j.'")';
					$result2 = mysqli_query($con,$sql2);
					echo $$keyency['slug']." ".$key."\n";
					var_dump($result2);
				}
			}
		}
		echo $currency["slug"]." CSV done\n";
	}
}
?>
