<?php
namespace eftec\DocumentStoreOne;

/**
 * Class DocumentStoreOne
 * @version 1.1 2018-08-12
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */
class DocumentStoreOne {

    /** @var string root folder of the database */
    var $database;
    /** @var string collection (subfolder) of the database */
    var $collection;
    /** @var int Maximium duration of the lock (in seconds). By default it's 2 minutes */
    var $maxLockTime=120;
    /** @var int Default number of retries. By default it tries 300x0.1sec=30 seconds */
    var $defaultNumRetry=300;
    /** @var int Interval (in microseconds) between retries. 100000 means 0.1 seconds */
    var $intervalBetweenRetry=100000;
    /** @var string Default extension (with dot) of the document */
    var $docExt=".dson";
    /** @var null|string[] Indicates if it's locked manually. By default, every operation locks the document */
    private $manualLock=null;

    /**
     * DocumentStoreOne constructor.
     * @example $flatcon=new DocumentStoreOne(dirname(__FILE__)."/base",'collectionFolder');
     * @param string $database root folder of the database
     * @param string $collection collection (subfolder) of the database. If the collection is empty then it uses the root folder.
     * @throws \Exception
     */
    public function __construct($database, $collection='')
    {
        $this->database = $database;
        $this->collection = $collection;
        if (!is_dir($this->getPath())) {
            throw new \Exception("Incorrect folder");
        }
    }

    /**
     * Convert Id to a full filename
     * @param string $id
     * @return string full filename
     */
    private function filename($id) {

        //$file =base64_encode($id); //it's unsable on windows because windows is not case sensitive.
        $file =$id;
        return $this->getPath()."/".$file.$this->docExt;
    }

    /**
     * @param string|string[] $Ids
     * @param int $tries
     * @return bool
     */
    public function lock($Ids, $tries=-1) {
        if ($this->manualLock!=null) return false; // we can't lock because it's already locked. Unlock first.
        if (is_array($Ids)) {
            $v=-10;
            $numItem=count($Ids);
            for($i=0;$i<$numItem;$i++) {
                if (!$this->lockFolder($Ids[$i], $tries)) {
                    $v = $i-1; // it failed to lock the file.
                    break;
                }
            }
            if ($v!=-10) {
                //something failed, rollback
                for($i=0;$i<$v;$i++) {
                    $this->unlockFolder($Ids[$i],true);
                }
                return false;
            } else {
                // the entire array is locked.
                $this->manualLock=$Ids;
                return true;
            }
        } else {
            // we lock a single id.
            if ($this->lockFolder($Ids,$tries)) {
                $this->manualLock=array($Ids);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Manually unlock the document
     * @return bool
     */
    public function unlock() {

        if (is_array($this->manualLock)) {
            $v=true;
            var_dump($this->manualLock);
            foreach($this->manualLock as $id) {
                var_dump($id);
                $v=$v && $this->unlockFolder($id,true);
            }
        } else {
            $v=false; // is not locked.
        }
        $this->manualLock=null;
        return $v;
    }

    /**
     * It gets the next sequence. If the sequence doesn't exist, it generates a new one with 1.
     * You could peek a sequence with get('genseq_<name>')
     * If the sequence is corrupt then it's resetted.
     * @param string $name Name of the sequence.
     * @param int $tries
     * @param int $init The initial value of the sequence (if it's created)
     * @param int $interval The interval between each sequence. It could be negative.
     * @param int $reserveAdditional Reserve an additional number of sequence. It's useful when you want to generates many sequences at once.
     * @return bool|int It returns false if it fails to lock the sequence or if it's unable to read thr sequence. Otherwise it returns the sequence
     */
    public function getNextSequence($name="seq",$tries=-1,$init=1,$interval=1,$reserveAdditional=0) {
        $id="genseq_".$name;
        $file =$this->filename($id).".seq";
        if ($this->lockFolder($file,$tries)) {
            if (file_exists($file)) {
                $read=@file_get_contents($file);
                if ($read===false) {
                    $this->unlockFolder($file);
                    return false; // file exists but i am unable to read it.
                }
                $read=(is_numeric($read))?($read+$interval):$init; // if the value stored is numeric, then we add one, otherwise, it starts with 1
            } else {
                $read=$init;
            }
            $write = @file_put_contents($file, $read+$reserveAdditional, LOCK_EX);
            $this->unlockFolder($file);
            return ($write===false)?false:$read;
        } else {
            return false; // unable to lock
        }
    }

    /**
     * It gets the current path.
     * @return string
     */
    private function getPath() {
        return $this->database."/".$this->collection;
    }

    /**
     * Add a document.
     * @param string $id Id of the document.
     * @param string $document The document
     * @param int $tries
     * @return bool True if the information was added, otherwise false
     */
    public function insert($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            if (!file_exists($file)) {
                $write = @file_put_contents($file, $document, LOCK_EX);
            } else {
                $write=false;
            }
            $this->unlockFolder($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Update a document
     * @param string $id Id of the document.
     * @param string $document The document
     * @param int $tries
     * @return bool True if the information was added, otherwise false
     */
    public function update($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            if (file_exists($file)) {
                $write = @file_put_contents($file, $document, LOCK_EX);
            } else {
                $write=false;
            }
            $this->unlockFolder($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Add or update a document.
     * @param string $id Id of the document.
     * @param string $document The document
     * @param int $tries
     * @return bool True if the information was added, otherwise false
     */
    public function insertOrUpdate($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            $write = @file_put_contents($file, $document, LOCK_EX);
            $this->unlockFolder($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Check a collection
     * @param $collection
     * @return bool It returns false if it's not a collection (a valid folder)
     */
    public function isCollection($collection) {
        $this->collection=$collection;
        return is_dir($this->getPath());
    }

    /**
     * Set the current collection
     * @param $collection
     * @return DocumentStoreOne
     */
    public function collection($collection) {
        $this->collection=$collection;
        return $this;
    }

    /**
     * Creates a collection
     * @param $collection
     * @return bool true if the operation is right, false if it fails.
     */
    public function createCollection($collection) {
        $oldCollection=$this->collection;
        $this->collection=$collection;
        $r=@mkdir($this->getPath());
        $this->collection=$oldCollection;
        return $r;
    }

    /**
     * List all the Ids in a collection.
     * @param string $mask see http://php.net/manual/en/function.glob.php
     * @return array|false
     */
    public function select($mask="*") {
        $list = glob($this->database."/".$this->collection."/".$mask.$this->docExt);
        foreach ($list as &$file) {
            $file=basename($file,$this->docExt);
        }
        return $list;
    }

    /**
     * Get a document
     * @param string $id Id of the document.
     * @param int $tries
     * @return string|bool True if the information was read, otherwise false.
     */
    public function get($id,$tries=-1) {
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
     * @return bool if it's unable to unlock or the document doesn't exist.
     */
    public function delete($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lockFolder($file,$tries)) {
            $r=@unlink($file);
            $this->unlockFolder($file);
            return $r;
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
        if ($this->manualLock!=null) return true; //it's already locked manually.
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
     * Unlocks a document
     * @param $filepath
     * @param bool $forced
     * @return bool
     */
    private function unlockFolder($filepath,$forced=false){
        if ($this->manualLock!=null && !$forced) return true; // it's locked manually it must be unlocked manually.
        $unlockname= $filepath.".lock";
        return @rmdir($unlockname);
    }


    /**
     * Util function to fix the cast of an object.
     * Usage utilCache::fixCast($objectRightButEmpty,$objectBadCast);
     * @param object|array $destination Object may be empty with the right cast.
     * @param object|array $source  Object with the wrong cast.
     * @return void
     */
    public static function fixCast(&$destination,$source)
    {
        if (is_array($source)) {
            $getClass=get_class($destination[0]);
            $array=array();
            foreach($source as $sourceItem) {
                $obj = new $getClass();
                self::fixCast($obj,$sourceItem);
                $array[]=$obj;
            }
            $destination=$array;
        } else {
            $sourceReflection = new \ReflectionObject($source);
            $sourceProperties = $sourceReflection->getProperties();
            foreach ($sourceProperties as $sourceProperty) {
                $name = $sourceProperty->getName();
                if (is_object(@$destination->{$name})) {
                    if (get_class(@$destination->{$name})=="DateTime") {
                        // source->name is a stdclass, not a DateTime, so we could read the value with the field date
                        $destination->{$name}=new \DateTime($source->$name->date);
                    } else {
                        self::fixCast($destination->{$name}, $source->$name);
                    }
                } else {
                    $destination->{$name} = $source->$name;
                }
            }
        }
    }

    public function debugFile($file,$txt) {
        $fz=@filesize($file);
        if ($fz>100000) {
            // mas de 100kb = reducirlo a cero.
            $fp = fopen($file, 'w');
        } else {
            $fp = fopen($file, 'a');
        }
        fwrite($fp, $txt."\n");
        fclose($fp);
    }
}

