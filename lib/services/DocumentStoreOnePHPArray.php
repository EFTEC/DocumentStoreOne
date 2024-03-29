<?php

namespace eftec\DocumentStoreOne\services;

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * Class DocumentStoreOnePHPArray
 *
 * @version 1.00 2021/12/08
 * @author  Jorge Castro Castillo jcastro@eftec.cl
 * @link    https://github.com/EFTEC/DocumentStoreOne
 * @license LGPLv3 or commercial
 */
class DocumentStoreOnePHPArray implements IDocumentStoreOneSrv
{
    /** @var DocumentStoreOne */
    public $parent;

    /**
     * @param DocumentStoreOne $parent
     */
    public function __construct(DocumentStoreOne $parent)
    {
        $this->parent = $parent;
    }
    public function defaultTabular():bool {
        return false;
    }

    public function appendValue($filePath, $id, $addValue, $tries = -1)
    {
        $fp=$this->parent->appendValueDecorator($filePath,$id,$addValue,$tries);
        if(!is_resource($fp)) {
            return $fp;
        }

        fseek($fp, -4, SEEK_END);
        $addValue = $this->parent->serialize($addValue, true);
        $r = @fwrite($fp, ',' . $addValue . ");\n "); //note: ");\n " it must be exactly 4 characters.

        @fclose($fp);
        $this->parent->unlock($filePath);
        if($r===false) {
            $this->parent->throwError(error_get_last());
        }
        return ($r !== false);
    }
    public function insert($id, $document, $tries = -1) {
    }
    public function serialize($document, $special = false) {
        return DocumentStoreOne::serialize_php_array($document, $special);
    }
    public function convertTypeBack($input, $type) {
        switch ($type) {
            case 'decimal':
            case 'string':
            case 'int':
                return $input;
            case 'date':
                return $input->format($this->parent->regionDate);
            case 'datetime':
                return $input->format($this->parent->regionDateTime);
        }
        return $input;
    }
    public function convertType($input, $type) {
        return $input;
    }

    public function insertOrUpdate($id, $document, $tries = -1)
    {

    }
    public function deserialize($document)
    {
        //php_array should be included.
        return $document;
    }



}
