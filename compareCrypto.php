<?php
$con = mysqli_connect("xxx", "xxx", "xxx", "xxx");

$startDate = "20180604";
$budget = 1000;
$topCoins = 100;
$onlyBinance = 1;
$CoinsToCheck = 1; //Number of coins to check
$dayInterval = 7;//7 or 1 or 2
$bestOrWorst = "worst";
$dayweek = 0;

//$blacklistedCoins = array("tether", "bitcoin-diamond", "bitcoin-private", "tezos");
$blacklistedCoins = array();

for ($date = $startDate; $date <= date('Ymd', time()); $date = date('Ymd', strtotime($date. ' + '.$dayInterval.' days')))
{
    if($dayInterval == 7)
    {
      if ($onlyBinance == 1)
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, pricesB.date, pricesB.value, pricesB.perfDay, pricesB.perfWeek FROM Currencies, pricesB WHERE Currencies.name = pricesB.nameCurrency AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' AND onBinance = 1 ORDER BY pricesB.perfWeek DESC';
      }
      else
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, prices.date, prices.value, prices.perfDay, prices.perfWeek FROM Currencies, prices WHERE Currencies.name = prices.nameCurrency AND DATE_FORMAT(prices.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' ORDER BY prices.perfWeek DESC';
      }

    }
    elseif($dayInterval == 1)
    {
      if ($onlyBinance == 1)
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, pricesB.date, pricesB.value, pricesB.perfDay, pricesB.perf2Day, pricesB.perf3Day, pricesB.perf4Day, pricesB.perf5Day, pricesB.perf6Day, pricesB.perfWeek FROM Currencies, pricesB WHERE Currencies.name = pricesB.nameCurrency AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' AND onBinance = 1 ORDER BY pricesB.perfDay DESC';
      }
      else
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, prices.date, prices.value, prices.perfDay, prices.perf2Day, prices.perf3Day, prices.perf4Day, prices.perf5Day, prices.perf6Day, prices.perfWeek FROM Currencies, prices WHERE Currencies.name = prices.nameCurrency AND DATE_FORMAT(prices.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' ORDER BY prices.perfDay DESC';
      }
    }
    elseif($dayInterval == 2)
    {
      if ($onlyBinance == 1)
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, pricesB.date, pricesB.value, pricesB.perfDay, pricesB.perf2Day, pricesB.perf3Day, pricesB.perf4Day, pricesB.perf5Day, pricesB.perf6Day, pricesB.perfWeek FROM Currencies, pricesB WHERE Currencies.name = pricesB.nameCurrency AND DATE_FORMAT(pricesB.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' AND onBinance = 1 ORDER BY pricesB.perf2Day DESC';
      }
      else
      {
        $sql = 'SELECT Currencies.rank, Currencies.name, Currencies.symbol, Currencies.onBinance, prices.date, prices.value, prices.perfDay, prices.perf2Day, prices.perf3Day, prices.perf4Day, prices.perf5Day, prices.perf6Day, prices.perfWeek FROM Currencies, pricesB WHERE Currencies.name = prices.nameCurrency AND DATE_FORMAT(prices.date, "%y-%m-%d") = DATE_FORMAT("'.$date.'", "%y-%m-%d") AND rank <= '.$topCoins.' ORDER BY prices.perf2Day DESC';
      }
    }

    $result = mysqli_query($con,$sql);
    while ($row = mysqli_fetch_array($result))
    {
        $resultRank[$dayweek][] = $row["rank"];
        $resultName[$dayweek][] = $row["name"];
        $resultDate[$dayweek][] = $row["date"];
        $resultValue[$dayweek][] = $row["value"];
        $resultperfDay[$dayweek][] = $row["perfDay"];
        $resultperf2Day[$dayweek][] = $row["perf2Day"];
        $resultperfWeek[$dayweek][] = $row["perfWeek"];
    }

    //We remove blacklisted coin
    foreach ($blacklistedCoins as $key => $coin)
    {
    	$key = array_search($coin, $resultName[$dayweek]);
      if($key != FALSE)
      {
        unset($resultRank[$dayweek][$key]);
        unset($resultName[$dayweek][$key]);
        unset($resultDate[$dayweek][$key]);
        unset($resultValue[$dayweek][$key]);
        unset($resultperfDay[$dayweek][$key]);
        unset($resultperf2Day[$dayweek][$key]);
        unset($resultperfWeek[$dayweek][$key]);
      }
    }
    $resultRank[$dayweek] = array_values($resultRank[$dayweek]);
    $resultName[$dayweek] = array_values($resultName[$dayweek]);
    $resultDate[$dayweek] = array_values($resultDate[$dayweek]);
    $resultValue[$dayweek] = array_values($resultValue[$dayweek]);
    $resultperfDay[$dayweek] = array_values($resultperfDay[$dayweek]);
    $resultperf2Day[$dayweek] = array_values($resultperf2Day[$dayweek]);
    $resultperfWeek[$dayweek] = array_values($resultperfWeek[$dayweek]);

    //Si c'est la 1er check, le check initial est moins complexe, on calcule rien
    if($dayweek == 0)
    {
        echo "\033[32m Nous sommes semaine ".$date." \033[0m\n";

        for ($i=0; $i < $CoinsToCheck; $i++)
        {
          $simuCoin["budget"][$dayweek][$i] = $budget/$CoinsToCheck;
        }

        if($bestOrWorst == "best")
        {
            for ($i=0; $i < $CoinsToCheck; $i++)
            {
              $simuCoin["coin"][$dayweek][$i] = $resultName[$dayweek][$i];
            }
        }
        else
        {
            for ($i=0; $i < $CoinsToCheck; $i++)
            {
              $simuCoin["coin"][$dayweek][$i] = $resultName[$dayweek][count($resultName[$dayweek])-($i+1)];
            }
        }

        for ($i=0; $i < $CoinsToCheck; $i++)
        {
          echo "Nous misons ".$simuCoin["budget"][$dayweek][$i]." sur ".$simuCoin["coin"][$dayweek][$i]."\n";
        }
    }
    else
    {
        echo "\033[32mNous sommes semaine ".$date." \033[0m\n";

        for ($i=0; $i < $CoinsToCheck; $i++)
        {
          $key = array_search($simuCoin["coin"][$dayweek-1][$i], $resultName[$dayweek]);

          if($dayInterval == 7)
          {
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek-1][$i] * ($resultperfWeek[$dayweek][$key]/100) + $simuCoin["budget"][$dayweek-1][$i];
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees for buy
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees for sell
              echo "La perf de notre coin ".$simuCoin["coin"][$dayweek-1][$i]." acheté la derniere fois est de ".$resultperfWeek[$dayweek][$key]."\n";
              echo "Notre budget passe de ".$simuCoin["budget"][$dayweek-1][$i]." à ".$simuCoin["budget"][$dayweek][$i]."\n";
          }
          elseif($dayInterval == 1)
          {
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek-1][$i] * ($resultperfDay[$dayweek][$key]/100) + $simuCoin["budget"][$dayweek-1][$i];
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees for sell
              echo "La perf de notre coin ".$simuCoin["coin"][$dayweek-1][$i]." acheté la derniere fois est de ".$resultperfDay[$dayweek][$key]."\n";
              echo "Notre budget passe de ".$simuCoin["budget"][$dayweek-1][$i]." à ".$simuCoin["budget"][$dayweek][$i]."\n";
          }
          elseif($dayInterval == 2)
          {
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek-1][$i] * ($resultperf2Day[$dayweek][$key]/100) + $simuCoin["budget"][$dayweek-1][$i];
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees
              $simuCoin["budget"][$dayweek][$i] = $simuCoin["budget"][$dayweek][$i] * 0.999; //Binance fees for sell
              echo "La perf de notre coin ".$simuCoin["coin"][$dayweek-1][$i]." acheté la derniere fois est de ".$resultperf2Day[$dayweek][$key]."\n";
              echo "Notre budget passe de ".$simuCoin["budget"][$dayweek-1][$i]." à ".$simuCoin["budget"][$dayweek][$i]."\n";
          }

          if($bestOrWorst == "best")
          {
              $simuCoin["coin"][$dayweek][$i] = $resultName[$dayweek][$i];
              if($dayInterval == 7)
              {
                  echo "La coin la plus performante de la semaine ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperfWeek[$dayweek][$i]."%\n";
              }
              elseif($dayInterval == 1)
              {
                  echo "La coin la plus performante du jour ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperfDay[$dayweek][$i]."%\n";
              }
              elseif($dayInterval == 2)
              {
                  echo "La coin la plus performante du jour ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperf2Day[$dayweek][$i]."%\n";
              }
          }
          else
          {
              $simuCoin["coin"][$dayweek][$i] = $resultName[$dayweek][count($resultName[$dayweek])-($i+1)];
              if($dayInterval == 7)
              {
                  echo "La coin la moins performante de la semaine ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperfWeek[$dayweek][count($resultName[$dayweek])-($i+1)]."%\n";
              }
              elseif($dayInterval == 1)
              {
                  echo "La coin la moins performante du jour ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperfDay[$dayweek][count($resultName[$dayweek])-($i+1)]."%\n";
              }
              elseif($dayInterval == 1)
              {
                  echo "La coin la moins performante du jour ".$date." est le ".$simuCoin["coin"][$dayweek][$i]." avec une perf de ".$resultperf2Day[$dayweek][count($resultName[$dayweek])-($i+1)]."%\n";
              }
          }
        }

        $newBudget = 0;
        foreach ($simuCoin["budget"][$dayweek] as $key => $value)
        {
          $newBudget = $newBudget + $value;
        }
        //var_dump($simuCoin);
        echo "Notre budget est maintenant de $newBudget\n";

    }

    $dayweek = $dayweek + 1;
}
?>
