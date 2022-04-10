<?php

require __DIR__ . '/vendor/autoload.php';

require_once 'helpers\PrepareFilesToTranslateHelper.php';

$helper = new Helpers\PrepareFilesToTranslateHelper();

$files = array_diff(scandir('origin_files'), array('.', '..'));
foreach ($files as $file) {
    $helper->toTranslate('origin_files/' . $file);
}
$helper->generate();


// $ for our old aircraft carrier seems to be too much expensive for what the Brazilian plans to do. They ask for a new price of 30M$"