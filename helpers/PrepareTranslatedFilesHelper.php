<?php

namespace Helpers;

use Aspera\Spreadsheet\XLSX\Reader;
use Aspera\Spreadsheet\XLSX\Worksheet;

require_once 'AbstractHelper.php';

class PrepareTranslatedFilesHelper extends AbstractHelper
{
    const DIR_TRANSLATED = 'translated';

    protected $translatedArray = [];

    public function __construct()
    {
        $this->filesNames = json_decode(file_get_contents(self::DIR_TRANSLATE . '/filesNames.json'), true);
        $this->textKeys = json_decode(file_get_contents(self::DIR_TRANSLATE . '/textKeys.json'), true);
        $this->variablesFromText = json_decode(file_get_contents(self::DIR_TRANSLATE . '/variablesFromText.json'), true);
        $this->_loadTranslatedText();

        if (!file_exists(self::DIR_TRANSLATED)) {
            mkdir(self::DIR_TRANSLATED);
        }
    }

    public function prepareFiles($dirToTranslateFiles)
    {
        foreach ($this->filesNames as $sheetName => $filesName) {
            $newFileContentArray = [];
            $fileContentArray = $this->_readFile($dirToTranslateFiles . $filesName);

            foreach ($fileContentArray as $row) {
                if (in_array(strpos($row, '#'), [1, 2]) || $row == '') {
                    $newFileContentArray[] = $row;
                    continue;
                }
                $newFileContentArray[] = $this->_prepareTextRow($sheetName, $row);
            }
            file_put_contents(self::DIR_TRANSLATED . '/' . $filesName, implode("\n", $newFileContentArray));
        }

    }

    private function _loadTranslatedText()
    {
        $prefix = '/' . str_replace('%s', '[0-9]+', self::FILE_TO_TRANSLATE_PREFIX) . '/m';
        $files = array_diff(scandir(self::DIR_TRANSLATE), array('.', '..'));

        foreach ($files as $file) {
            if (!preg_match($prefix, $file)) {
                continue;
            }

            $reader = new Reader();
            $reader->open(self::DIR_TRANSLATE . '/' . $file);
            $sheets = $reader->getSheets();

            /** @var Worksheet $sheet_data */
            foreach ($sheets as $index => $sheet_data) {
                $reader->changeSheet($index);

                $sheetName = str_replace(' ', '', $sheet_data->getName());
                foreach ($reader as $row) {
                    $this->translatedArray[$sheetName][trim($row[0])] = $row[1];
                }
            }
            $reader->close();
        }

    }

    private function _prepareTextRow($sheetName, $row)
    {
        $row = str_replace("\r", '', $row);

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

        if (!isset($array[1]) || trim($array[1]) == '""' || !trim($array[1])) {
            return $row;
        }

        $textKey = trim($array[0]);
        $textKeySubstitute = array_search($textKey, $this->textKeys[$sheetName]);
        $translatedText = trim($this->translatedArray[$sheetName][$textKeySubstitute]);

        $this->_replaceTextVariablesSubstitute($sheetName, $translatedText);
        $this->_replaceTextDecoration($translatedText);

        return $array[0] . ' "' . $translatedText . '"';
    }

    private function _replaceTextVariablesSubstitute($sheetName, string &$translatedText)
    {
        if (!isset($this->variablesFromText[$sheetName])) {
            return;
        }
        foreach ($this->variablesFromText[$sheetName] as $textVariablesSubstitute => $variable) {
            $translatedText = str_replace($textVariablesSubstitute, $variable, $translatedText);
        }
    }

    private function _replaceTextDecoration(&$translatedText)
    {
        foreach ($this->textDecorationTypes as $type) {
            $translatedText = str_replace(sprintf(self::TEXT_DECORATION_PREFIX, $type) . ' ', '§' . $type, $translatedText);
        }
        $translatedText = str_replace(' ' . self::END_TEXT_DECORATION_PREFIX . ' ', '§', $translatedText);
        $translatedText = str_replace(self::NEW_LINE_PREFIX, '\n', $translatedText);
    }


}
