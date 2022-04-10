<?php

namespace Helpers;

use XLSXWriter;

require_once 'AbstractHelper.php';

class PrepareFilesToTranslateHelper extends AbstractHelper
{
    protected $sheetName;

    protected $charCount = 0;

    protected $fileCharCount = 0;

    protected $fileCount = 1;

    protected $sheetCount = 1;

    protected XLSXWriter $writer;

    public function __construct()
    {
        if (!file_exists(self::DIR_TO_TRANSLATE)) {
            mkdir(self::DIR_TO_TRANSLATE);
        }
        if (!file_exists(self::DIR_TRANSLATE)) {
            mkdir(self::DIR_TRANSLATE);
        }

        $this->writer = new XLSXWriter();
    }

    public function toTranslate($fileDir)
    {
        $this->sheetName = 'F' . $this->sheetCount;

        $textArray = $this->_readFile($fileDir);
        $textToTranslate = $this->_getTextToTranslate($textArray);

        $this->filesNames[$this->sheetName] = basename($fileDir);

        foreach ($textToTranslate as $row) {
            $this->writer->writeSheetRow($this->sheetName, $row);
        }
        ++$this->sheetCount;
    }

    public function generate()
    {
        $this->generateFileToTranslate();
        $this->generateFilesWithInformationToTranslate();
    }

    private function _getTextToTranslate(array $textArray)
    {
        $toTranslate = [];
        foreach ($textArray as $row) {
            $row = trim(str_replace("\r", '', $row));
            if (in_array(strpos($row, '#'), [1, 2]) || $row == '') {
                continue;
            }

            $toTranslate[] = $this->_prepareTextRow($row);
        }

        $toTranslate = array_filter($toTranslate);

        if ($this->charCount + $this->fileCharCount > 3500000) {
            $this->generateFileToTranslate();
            $this->writer = new XLSXWriter();
            $this->charCount = 0;
            ++$this->fileCount;
        }

        $this->charCount += $this->fileCharCount;
        $this->fileCharCount = 0;

        return $toTranslate;
    }

    private function _prepareTextRow($row)
    {
        $array = explode(':0 ', $row);
        $array[0] .= ':0';

        if (count($array) == 1) {
            $array = explode(':1 ', $row);
            $array[0] .= ':1';
        }

        if (count($array) == 1) {
            $array = explode(': ', $row);
            $array[0] .= ':';
        }

        if (!isset($array[1]) || $array[1] == '""') {
            return;
        }

        $textKey = trim($array[0]);
        $textKeySubstitute = sprintf(
            self::KEY_VALUE_PREFIX,
            (
                isset($this->textKeys[$this->sheetName])
                    ? count($this->textKeys[$this->sheetName])
                    : 0
            )
        );
        $this->textKeys[$this->sheetName][$textKeySubstitute] = $textKey;

        $text = $array[1];

        if (count($array) > 2) {
            unset($array[0]);
            $text = implode(': ', $array);
        }

        $text = substr(str_replace("\r", '', $text), 1, -1);
        $this->_replaceCodeFromText($text);

        $this->fileCharCount += strlen($text);

        return [$textKeySubstitute, $text];
    }

    private function _replaceCodeFromText(&$text)
    {
        //variables like $VAL$
        $regex = '/\$.*?\$/m';
        preg_match_all($regex, $text, $matches);
        if ($matches) {
            foreach ($matches[0] as $match) {
                $this->_replaceVariables($text, $match);
            }
        }

        //variables like [Root.GetName]
        $regex = '/\[.*?\]/m';
        preg_match_all($regex, $text, $matches);
        if ($matches) {
            foreach ($matches[0] as $match) {
                $this->_replaceVariables($text, $match);
            }
        }

        //variable like £GFX_GNSS_satellite_texticon
        $regex = '/£\w+/m';
        preg_match_all($regex, $text, $matches);
        if ($matches) {
            foreach ($matches[0] as $match) {
                $this->_replaceVariables($text, $match);
            }
        }

        // \n
        $text = str_replace('\n', self::NEW_LINE_PREFIX, $text);

        //text decoration like §R..§, §G...§, §Y..§
        $regex = '/\§(G{1}|Y{1}|R{1}|T{1}).*?\§/m';
        preg_match_all($regex, $text, $matches);
        if (count($matches[0]) > 0) {
            foreach ($matches[0] as $match) {
                $type = substr($match, 2,1);
                $replacedText = sprintf(self::TEXT_DECORATION_PREFIX, $type) . ' ' . substr($match,3,-2)  . ' ' . self::END_TEXT_DECORATION_PREFIX . ' ' ;

                $text = str_replace($match, $replacedText, $text);
            }
        }
    }

    private function _replaceVariables(&$text, $match)
    {
        $textVariablesSubstitute = isset($this->variablesFromText[$this->sheetName])
            ? array_search($match, $this->variablesFromText[$this->sheetName])
            : false;
        if (!$textVariablesSubstitute) {
            $textVariablesSubstitute = sprintf(
                self::TEXT_VARIABLES_PREFIX,
                (
                isset($this->variablesFromText[$this->sheetName])
                    ? count($this->variablesFromText[$this->sheetName])
                    : 0
                )
            );
            $this->variablesFromText[$this->sheetName][$textVariablesSubstitute] = $match;
        }
        $text = str_replace($match, $textVariablesSubstitute, $text);
    }

    private function generateFileToTranslate()
    {
        $this->writer->writeToFile(self::DIR_TO_TRANSLATE . '/' . sprintf(self::FILE_TO_TRANSLATE_PREFIX,  $this->fileCount));
    }

    private function generateFilesWithInformationToTranslate()
    {
        file_put_contents(self::DIR_TRANSLATE . '/filesNames.json', json_encode($this->filesNames));
        file_put_contents(self::DIR_TRANSLATE . '/textKeys.json', json_encode($this->textKeys));
        file_put_contents(self::DIR_TRANSLATE . '/variablesFromText.json', json_encode($this->variablesFromText));
    }
}