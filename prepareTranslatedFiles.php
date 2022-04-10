<?php

require __DIR__ . '/vendor/autoload.php';

require_once 'helpers\PrepareTranslatedFilesHelper.php';

$helper = new Helpers\PrepareTranslatedFilesHelper();

$helper->prepareFiles('origin_files/');