<?php
include "ccxt/ccxt.php";
#var_dump (\ccxt\Exchange::$exchanges); // print a list of all available exchange classes

$binance = new \ccxt\binance ();

//var_dump ($binance->fetch_order_book ("ETH/BTC"));

//var_dump ($binance->loadMarkets());

$markets = $binance->loadMarkets();

foreach (array_keys($markets) as $key => $value) {
  echo "$value\n";
  if ($value == "ETH/BTC")
  {
    var_dump($markets[$value]);
  }
}

#$symbols = $binance->symbols;
#var_dump ($symbols);
?>
