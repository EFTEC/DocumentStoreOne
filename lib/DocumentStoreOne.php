<?php

/**
 * Class DocumentStoreOne
 * @version 1.0 2018-08-11
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */
class DocumentStoreOne {

    /** @var string root folder of the database */
    var $database;
    /** @var string schema (subfolder) of the database */
    var $schema;

    /**
     * DocumentStoreOne constructor.
     * @example $flatcon=new DocumentStoreOne(dirname(__FILE__)."/base",'schemaFolder');
     * @param string $database root folder of the database
     * @param string $schema schema (subfolder) of the database. If the schema is empty then it uses the root folder.
     * @throws Exception
     */
    public function __construct($database, $schema='')
    {
        $this->database = $database;
        $this->schema = $schema;
        if (!is_dir($this->getPath())) {
            throw new Exception("Incorrect folder");
        }
    }

    /**
     * Convert Id to a full filename
     * @param $id
     * @return string full filename
     */
    private function filename($id) {

        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $id);
        $file = mb_ereg_replace("([\.]{2,})", '', $file);

        return $this->getPath()."/".$file.".json";
    }

    /**
     * It gets the current path.
     * @return string
     */
    private function getPath() {
        return $this->database."/".$this->schema;
    }

    /**
     * Add or update a field.
     * @param string $id Id of the document.
     * @param string $json The document
     * @return bool True if the information was added, otherwise false
     */
    public function add($id,$json)
    {
        $file =$this->filename($id);
        if ($this->lockFolder($file)) {
            $write=file_put_contents($file, $json, LOCK_EX);
            $this->unlockFolder($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Set the current schema
     * @param $schema
     * @return bool If not schema
     */
    public function setSchema($schema) {
        $this->schema=$schema;
        return is_dir($this->getPath());
    }

    /**
     * Creates a schema
     * @param $schema
     * @return bool true if the operation is right, false if it fails.
     */
    public function createSchema($schema) {
        $oldSchema=$this->schema;
        $this->schema=$schema;
        $r=@mkdir($this->getPath());
        $this->schema=$oldSchema;
        return $r;
    }

    /**
     * List all the Ids in a schema.
     * @return array|false
     */
    public function list() {
        $list = glob($this->database."/".$this->schema."/*.json");
        foreach ($list as &$file) {
            $file=basename($file,'.json');
        }
        return $list;
    }

    /**
     * Read document
     * @param string $id  Id of the document.
     * @return string|bool True if the information was read, otherwise false.
     */
    public function read($id) {
        $file =$this->filename($id);
        if ($this->lockFolder($file)) {
            $json=@file_get_contents($file);
            $this->unlockFolder($file);
            return $json;
        } else {
            return false;
        }
    }

    /**
     * Delete document.
     * @param string $id Id of the document
     * @return bool
     */
    public function delete($id) {
        $file =$this->filename($id);
        if ($this->lockFolder($file)) {
            unlink($file);
            $this->unlockFolder($file);
            return true;
        } else {
            return false;
        }
    }

    /**
     * It locks a file
     * @param $filepath
     * @param int $maxRetry
     * @return bool
     */
    private function lockFolder($filepath,$maxRetry=20){
        clearstatcache();
        $lockname=$filepath.".lock";
        $life=@filectime($lockname);
        $try=0;
        while (!@mkdir($lockname) && $try<$maxRetry){
            $try++;
            if ($life) {
                if ((time() - $life) > 120) {
                    rmdir($lockname); //auto unlock every 2 minutes.
                    $life = false;
                }
            }
            usleep(rand(100000,200000));// 100ms to 200ms (around 5-10 tries per second, or 10 to 20 seconds max.
        }
        return ($try<$maxRetry);
    }

    /**
     * Unlocks a filename
     * @param $filepath
     * @return bool
     */
    public function unlockFolder($filepath){
        $unlockname= $filepath.".lock";
        return @rmdir($unlockname);
    }
}

