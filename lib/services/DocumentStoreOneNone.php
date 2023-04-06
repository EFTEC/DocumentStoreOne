<?php

namespace eftec\DocumentStoreOne\services;

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * Class DocumentStoreOneNone
 *
 * @version 1.00 2021/12/08
 * @author  Jorge Castro Castillo jcastro@eftec.cl
 * @link    https://github.com/EFTEC/DocumentStoreOne
 * @license LGPLv3 or commercial
 */
class DocumentStoreOneNone implements IDocumentStoreOneSrv
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
        return $this->parent->appendValueRaw($filePath, $addValue);
    }
    public function insert($id, $document, $tries = -1) {
    }
    public function serialize($document, $special = false) {
        return $document;
    }
    public function convertTypeBack($input, $type) {
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
        return $document;
    }



}
