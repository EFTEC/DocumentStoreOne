<?php
namespace eftec\DocumentStoreOne;

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
    /** @var int Maximium duration of the lock (in seconds). By default it's 2 minutes */
    var $maxLockTime=120;
    /** @var int Default number of retries. By default it tries 300x0.1sec=30 seconds */
    var $defaultNumRetry=300;
    /** @var int Interval (in microseconds) between retries. 100000 means 0.1 seconds */
    var $intervalBetweenRetry=100000;

    /**
     * DocumentStoreOne constructor.
     * @example $flatcon=new DocumentStoreOne(dirname(__FILE__)."/base",'schemaFolder');
     * @param string $database root folder of the database
     * @param string $schema schema (subfolder) of the database. If the schema is empty then it uses the root folder.
     * @throws \Exception
     */
    public function __construct($database, $schema='')
    {
        $this->database = $database;
        $this->schema = $schema;
        if (!is_dir($this->getPath())) {
            throw new \Exception("Incorrect folder");
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
     * @param string $document The document
     * @param int $tries
     * @return bool True if the information was added, otherwise false
     */
    public function add($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            $write=file_put_contents($file, $document, LOCK_EX);
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
     * @param string $id Id of the document.
     * @param int $tries
     * @return string|bool True if the information was read, otherwise false.
     */
    public function read($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            $json=@file_get_contents($file);
            $this->unlockFolder($file);
            return $json;
        } else {
            return false;
        }
    }

    /**
     * Return if the document exists. It doesn't check until the document is fully unlocked.
     * @param string $id Id of the document.
     * @param int $tries
     * @return string|bool True if the information was read, otherwise false.
     */
    public function ifExist($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            return file_exists($file);
        } else {
            return false;
        }
    }
    /**
     * Delete document.
     * @param string $id Id of the document
     * @param int $tries
     * @return bool
     */
    public function delete($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
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
    private function lockFolder($filepath,$maxRetry=-1){
        clearstatcache();
        $maxRetry=($maxRetry==-1)?$this->defaultNumRetry:$maxRetry;
        $lockname=$filepath.".lock";
        $life=@filectime($lockname);
        $try=0;
        while (!@mkdir($lockname) && $try<$maxRetry){
            $try++;
            if ($life) {
                if ((time() - $life) > $this->maxLockTime) {
                    rmdir($lockname);
                    $life = false;
                }
            }
            usleep($this->intervalBetweenRetry);
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

