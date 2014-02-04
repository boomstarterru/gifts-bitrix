<?php

$store = new StoreJSON(STORE_FILE);
$gifts = $store->load();

$package = array(
    'gifts' => $gifts,
    '_metadata' => array(
        'total_count' => count($gifts)
    ),
);

echo(json_encode($package));
