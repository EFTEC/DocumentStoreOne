<?php

namespace eftec\DocumentStoreOne\services;

use DateTime;
use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * Class DocumentStoreOneCsv
 *
 * @version 1.00 2021/12/08
 * @author  Jorge Castro Castillo jcastro@eftec.cl
 * @link    https://github.com/EFTEC/DocumentStoreOne
 * @license LGPLv3 or commercial
 */
class DocumentStoreOneCsv implements IDocumentStoreOneSrv
{
    /** @var DocumentStoreOne */
    public $parent;
    public $csvSeparator = ',';
    public $csvText = '"';
    public $csvEscape = '\\';
    public $csvHeader = true;
    public $csvLineEnd = "\n";
    /**
     * @var string If the column is missing, then it creates a name of a column, otherwise the name of the columns is
     *             the number of the column
     */
    public $csvPrefixColumn = '';

    /**
     * @param DocumentStoreOne $parent
     */
    public function __construct(DocumentStoreOne $parent)
    {
        $this->parent = $parent;
    }
    public function defaultTabular():bool {
        return true;
    }
    public function appendValue($filePath, $id, $addValue, $tries = -1)
    {
        $fp=$this->parent->appendValueDecorator($filePath,$id,$addValue,$tries);
        if(!is_resource($fp)) {
            return $fp;
        }
        fseek($fp, 0, SEEK_END);
        $addValue = $this->parent->serialize($addValue, true); // true no add header
        $r = @fwrite($fp, $addValue);
        @fclose($fp);
        $this->parent->unlock($filePath);
        if($r===false) {
            $this->parent->throwError(error_get_last());
        }
        return ($r !== false);
    }
    public function insert($id, $document, $tries = -1) {
        $this->getHeaderCSV($document);
        $this->determineTypes($document[0]);
    }
    public function serialize($document, $special = false) {
        return $this->serializeCSV($document, $special);
    }

    public function serializeCSV($table, $noheader = false)
    {
        $result = '';
        if (!$noheader && $this->csvHeader) {
            // had header
            $line = [];
            foreach ($this->parent->schemas[$this->parent->currentId] as $colname => $type) {
                $line[] = $this->parent->convertTypeBack($colname, 'string');
            }
            $result = implode($this->csvSeparator, $line) . $this->csvLineEnd;
        }
        $line = [];
        if (!$this->parent->isTable($table)) {
            // it just a row, so we convert into a row.
            $table = [$table];
        }
        foreach ($table as $kr => $row) {
            foreach ($row as $kcol => $v) {
                $line[$kr][$kcol] = $this->parent->convertTypeBack($v, $this->parent->schemas[$this->parent->currentId][$kcol]);
            }
            $result .= implode($this->csvSeparator, $line[$kr]) . $this->csvLineEnd;
        }
        return $result;
    }


    /**
     * @param array $row is an associative array with the values to determine
     * @return void
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function determineTypes(&$row)
    {
        $cols = isset($this->parent->schemas[$this->parent->currentId]) ? $this->parent->schemas[$this->parent->currentId] : null;
        // $this->parent->schemas[$this->parent->currentId] = [];
        if ($cols === null) {
            // there is no columns defined, so we define columns using this row
            foreach ($row as $kcol => $item) {
                if (is_numeric($kcol)) {
                    $kcol = $this->csvPrefixColumn . $kcol;
                }
                $this->parent->schemas[$this->parent->currentId][$kcol] = $this->parent->getType($item);
            }
        } else {
            $c = -1;
            foreach ($cols as $kcol => $v) {
                if (isset($row[0])) {
                    $c++;
                    $this->parent->schemas[$this->parent->currentId][$kcol]
                        = $this->parent->getType($row[$c]);
                } else {
                    $this->parent->schemas[$this->parent->currentId][$kcol]
                        = $this->parent->getType($row[$kcol]);
                }
            }
        }
    }


    public function convertTypeBack($input, $type) {
        switch ($type) {
            case 'int':
                return $input;
            case 'decimal':
                if ($this->parent->regionDecimal !== '.') {
                    $inputD = str_replace('.', $this->parent->regionDecimal, $input);
                } else {
                    $inputD = $input;
                }
                return $inputD;
            case 'date':
                return $this->csvText . $input->format($this->parent->regionDate) . $this->csvText;
            case 'string':
                if($this->csvText && strpos($input,$this->csvText)) {
                    return $this->csvText .str_replace($this->csvText,$this->csvEscape.$this->csvText,$input) . $this->csvText;
                }
                return $this->csvText .$input . $this->csvText;
            case 'datetime':
                return $this->csvText . $input->format($this->parent->regionDateTime) . $this->csvText;
        }
        return $input;
    }

    public function convertType($input, $type)
    {
        switch ($type) {
            case 'int':
                return (int)$input;
            case 'decimal':
                if ($this->parent->regionDecimal !== '.') {
                    $inputD = str_replace($this->parent->regionDecimal, '.', $input);
                } else {
                    $inputD = $input;
                }
                return (float)$inputD;
            case 'string':
                if(!$this->csvEscape || strpos($input,$this->csvEscape)===false) {
                    return (string)$input;
                }
                return (string)str_replace($this->csvEscape,'',$input);
            case 'date':
                return DateTime::createFromFormat($this->parent->regionDate, $input);
            case 'datetime':
                return DateTime::createFromFormat($this->parent->regionDateTime, $input);
        }
        return $input;
    }


    public function insertOrUpdate($id, $document, $tries = -1)
    {

    }
    public function deserialize($document)
    {
        return $this->unserializeCSV($document);
    }

    /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
    public function unserializeCSV(&$document)
    {
        $lines = explode($this->csvLineEnd, $document);
        $result = [];
        $numLines = count($lines);
        if ($numLines > 0) {
            if ($this->csvHeader) {
                // it has header
                $header = $this->splitLine($lines[0], false);
                foreach ($header as $namecol) {
                    $this->parent->schemas[$this->parent->currentId][$namecol] = 'string';
                }
                $first = 1;
            } else {
                // no header
                $this->parent->schemas[$this->parent->currentId] = null;
                $first = 0;
            }
            if (isset($lines[$first])) {
                // asume types
                $firstLine = $this->splitLine($lines[$first], true);
                //$numcols=count($firstLine);
                $this->determineTypes($firstLine);
                for ($i = $first; $i < $numLines; $i++) {
                    $tmp = $this->splitLine($lines[$i], true);
                    foreach ($tmp as $namecol => $item) {
                        if (!isset($this->parent->schemas[$this->parent->currentId][$namecol])) {
                            $this->parent->throwError('incorrect column found in csv line ' . $i);
                            return null;
                        }
                        $tmp[$namecol] = $this->parent->convertType($item, $this->parent->schemas[$this->parent->currentId][$namecol]);
                    }
                    if (count($tmp) > 0) {
                        // we avoid inserting an empty line
                        $result[] = $tmp;
                    }
                }
            }
        }
        return $result;
    }
    private function splitLine($lineTxt, $useColumn)
    {
        if ($lineTxt === null || $lineTxt === '') {
            return [];
        }
        $arr = str_getcsv($lineTxt, $this->csvSeparator, $this->csvText,$this->csvEscape);
        $result = [];
        if ($useColumn === true) {
            $colnames = isset($this->parent->schemas[$this->parent->currentId]) ? array_keys($this->parent->schemas[$this->parent->currentId]) : [];
            foreach ($arr as $numCol => $v) {
                if (!isset($colnames[$numCol])) {
                    $colnames[$numCol] = $this->csvPrefixColumn . $numCol;
                    $this->parent->schemas[$this->parent->currentId][$colnames[$numCol]] = $this->parent->getType($v); // column is missing so we create a column name
                }
                $result[$colnames[$numCol]] = trim($v);

            }
        } else {
            foreach ($arr as $k => $v) {
                $result[$k] = trim($v);
            }
        }
        return $result;
    }
    /**
     * It gets the header of a csv only if the id of the document doesn't have it.
     *
     * @param array $table
     * @return void
     */
    public function getHeaderCSV($table)
    {
        if (!isset($this->parent->schemas[$this->parent->currentId])) {
            if (!$this->parent->isTable($table)) {
                // it just a row, so we convert into a row.
                $table = [$table];
            }
            $this->parent->schemas[$this->parent->currentId] = [];
            foreach ($table[0] as $kcol => $v) {
                if (is_numeric($kcol)) {
                    $kcol = $this->csvPrefixColumn . $kcol;
                }
                $this->parent->schemas[$this->parent->currentId][$kcol] = 'string'; // default value is string
            }
        }
    }




}
