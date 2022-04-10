<?php

namespace Helpers;

abstract class AbstractHelper
{
    const FILE_TO_TRANSLATE_PREFIX = 'to_translate%s.xlsx';
    const KEY_VALUE_PREFIX = '|KVAL%s|';
    const NEW_LINE_PREFIX = '|NL|';
    const TEXT_DECORATION_PREFIX = '|TRN%s|';
    const END_TEXT_DECORATION_PREFIX = '|ETRN|';
    const TEXT_VARIABLES_PREFIX = '|TVAR%s|';
    const DIR_TO_TRANSLATE = 'to_translate';
    const DIR_TRANSLATE = 'translate';

    protected $textDecorationTypes = ['Y', 'R', 'G', 'T'];

    protected $textKeys = [];

    protected $variablesFromText = [];

    protected $filesNames = [];

    protected function _readFile($fileDir)
    {
        if (!is_readable($fileDir)) {
            var_dump('Not readable: ' . $fileDir); die;
        }

        try {
            $file = fopen($fileDir, "r");
        } catch (\Exception $exception) {
            var_dump('Cant open: ' . $fileDir); die;
        }

        if( $file == false ) {
            echo ( "Error in opening file" );
            exit();
        }

        $filesize = filesize( $fileDir );
        $textArray = explode("\n", fread( $file, $filesize ));
        fclose( $file );
        return $textArray;
    }
}