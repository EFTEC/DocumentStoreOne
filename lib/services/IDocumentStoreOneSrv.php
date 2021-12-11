<?php

namespace eftec\DocumentStoreOne\services;

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * Interface IDocumentStoreOneSrv
 *
 * @version 1.00 2021/12/08
 * @author  Jorge Castro Castillo jcastro@eftec.cl
 * @link    https://github.com/EFTEC/DocumentStoreOne
 * @license LGPLv3 or commercial
 */
interface IDocumentStoreOneSrv
{
    /**
     * @param DocumentStoreOne $parent
     */
    public function __construct(DocumentStoreOne $parent);

    public function defaultTabular();

    public function appendValue($filePath, $id, $addValue, $tries = -1);

    public function insert($id, $document, $tries = -1);

    public function serialize($document, $special = false);

    public function convertTypeBack($input, $type);

    public function convertType($input, $type);

    public function insertOrUpdate($id, $document, $tries = -1);

    public function deserialize($document);
}