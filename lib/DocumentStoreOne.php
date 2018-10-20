<?php
namespace eftec\DocumentStoreOne;

/**
 * Class DocumentStoreOne
 * @version 1.6 2018-10-19
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
    /** @var int Default number of retries. By default it tries 100x0.1sec=10 seconds */
    var $defaultNumRetry=100;
    /** @var int Interval (in microseconds) between retries. 100000 means 0.1 seconds */
    var $intervalBetweenRetry=100000;
    /** @var string Default extension (with dot) of the document */
    var $docExt=".dson";
    /** @var null|string[] Indicates if it's locked manually. By default, every operation locks the document */
    private $manualLock=null;
    /** @var int DocumentStoreOne::DSO_* */
    var $strategy=self::DSO_FOLDER;
    /** @var \Memcache */
    private $memcache;
    /** @var \Redis */
    var $redis;

    private $autoSerialize=false;

    const DSO_AUTO=0;
    const DSO_FOLDER=1;
    const DSO_APCU=2;
    const DSO_MEMCACHE=3;
    const DSO_REDIS=4;

    /**
     * DocumentStoreOne constructor.
     * @example $flatcon=new DocumentStoreOne(dirname(__FILE__)."/base",'collectionFolder');
     * @param string $database root folder of the database
     * @param string $collection collection (subfolder) of the database. If the collection is empty then it uses the root folder.
     * @param int $strategy DocumentStoreOne::DSO_*
     * @param string $server Used for DSO_MEMCACHE (localhost:11211) and DSO_REDIS (localhost:6379)
     * @param bool $autoSerialize
     * @throws \Exception
     */
    public function __construct($database, $collection='',$strategy=self::DSO_AUTO,$server="",$autoSerialize=false)
    {
        $this->database = $database;
        $this->collection = $collection;
        $this->autoSerialize=$autoSerialize;


        //$r=$memcache->connect(MEMCACHE_SERVER, MEMCACHE_PORT);
        $this->setStrategy($strategy,$server);


        if (!is_dir($this->getPath())) {
            throw new \Exception("Tsk Tsk, the folder is incorrect or I'm not unable to read  it: ".$this->getPath());
        }
    }

    /**
     * @param int $strategy DocumentStoreOne::DSO_*
     * @param string $server
     * @throws \Exception
     */
    public function setStrategy($strategy, $server="") {

        if($strategy==self::DSO_AUTO) {
            if (!function_exists("apcu_add")) {
                $this->strategy=self::DSO_APCU;
            } else {
                if (!class_exists("\Memcache")) {
                    $this->strategy=self::DSO_MEMCACHE;
                } else {
                    $this->strategy=self::DSO_FOLDER;
                }
            }
            $strategy=$this->strategy;
        } else {
            $this->strategy=$strategy;
        }
        switch ($strategy) {
            case self::DSO_FOLDER:
                break;
            case self::DSO_APCU:
                if (!function_exists("apcu_add")) throw new \Exception("APCU is not defined");
                break;
            case self::DSO_MEMCACHE:
                if (!class_exists("\Memcache")) throw new \Exception("Memcache is not defined");
                $this->memcache=new \Memcache();
                $host=explode(':',$server);
                $r=@$this->memcache->pconnect($host[0],$host[1]);
                if (!$r) {
                    throw new \Exception("Memcache is not open");
                }
                break;
            case self::DSO_REDIS:
                if (!class_exists("\Redis")) throw new \Exception("Redis is not defined");
                if (function_exists('cache')) {
                    $this->redis=cache();// inject using the cache function (if any).
                } else {
                    $this->redis=new \Redis();
                    $host=explode(':',$server);
                    $r=@$this->redis->pconnect($host[0],$host[1],30); // 30 seconds timeout
                    if (!$r) {
                        throw new \Exception("Redis is not open");
                    }
                }
                break;
            default:
                throw new\Exception("Strategy not defined");
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
     * It gets the next sequence. If the sequence doesn't exist, it generates a new one with 1.
     * You could peek a sequence with get('genseq_<name>')
     * If the sequence is corrupt then it's resetted.
     * @param string $name Name of the sequence.
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @param int $init The initial value of the sequence (if it's created)
     * @param int $interval The interval between each sequence. It could be negative.
     * @param int $reserveAdditional Reserve an additional number of sequence. It's useful when you want to generates many sequences at once.
     * @return bool|int It returns false if it fails to lock the sequence or if it's unable to read thr sequence. Otherwise it returns the sequence
     */
    public function getNextSequence($name="seq",$tries=-1,$init=1,$interval=1,$reserveAdditional=0) {
        $id="genseq_".$name;
        $file =$this->filename($id).".seq";
        if ($this->lock($file,$tries)) {
            if (file_exists($file)) {
                $read=@file_get_contents($file);
                if ($read===false) {
                    $this->unlock($file);
                    return false; // file exists but i am unable to read it.
                }
                $read=(is_numeric($read))?($read+$interval):$init; // if the value stored is numeric, then we add one, otherwise, it starts with 1
            } else {
                $read=$init;
            }
            $write = @file_put_contents($file, $read+$reserveAdditional, LOCK_EX);
            $this->unlock($file);
            return ($write===false)?false:$read;
        } else {
            return false; // unable to lock
        }
    }

    /**
     * It appends a value to an existing document.
     * @param string $name Name of the sequence.
     * @param string $addValue
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool It returns false if it fails to lock the document or if it's unable to read the document. Otherwise it returns true
     */
    public function appendValue($name,$addValue,$tries=-1) {

        $file =$this->filename($name);
        if ($this->lock($file,$tries)) {

            $fp=@fopen($file,'a');
            if ($fp===false) {
                $this->unlock($file);
                return false; // file exists but i am unable to open it.
            }
            $r=@fwrite($fp,$addValue);
            @fclose($fp);
            $this->unlock($file);
            return ($r!==false);
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
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool True if the information was added, otherwise false
     */
    public function insert($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            if (!file_exists($file)) {
                if ($this->autoSerialize) {
                    $write = @file_put_contents($file,serialize($document), LOCK_EX);
                } else {
                    $write = @file_put_contents($file, $document, LOCK_EX);
                }
            } else {
                $write=false;
            }
            $this->unlock($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Update a document
     * @param string $id Id of the document.
     * @param string $document The document
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool True if the information was added, otherwise false
     */
    public function update($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            if (file_exists($file)) {
                if ($this->autoSerialize) {
                    $write = @file_put_contents($file,serialize($document), LOCK_EX);
                } else {
                    $write = @file_put_contents($file, $document, LOCK_EX);
                }
            } else {
                $write=false;
            }
            $this->unlock($file);
            return ($write!==false);
        } else {
            return false;
        }
    }

    /**
     * Add or update a document.
     * @param string $id Id of the document.
     * @param string $document The document
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool True if the information was added, otherwise false
     */
    public function insertOrUpdate($id,$document,$tries=-1)
    {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            if ($this->autoSerialize) {
                $write = @file_put_contents($file,serialize($document), LOCK_EX);
            } else {
                $write = @file_put_contents($file, $document, LOCK_EX);
            }
            $this->unlock($file);
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
     * Set the current collection. It also could create the collection.
     * @param $collection
     * @param bool $createIfNotExist if true then it checks if the collection (folder) exists, if not then it's created
     * @return DocumentStoreOne
     */
    public function collection($collection,$createIfNotExist=false) {
        $this->collection=$collection;
        if ($createIfNotExist) {
            if (!$this->isCollection($collection)) {
                $this->createCollection($collection);
            }
        }
        return $this;
    }

    public function autoSerialize($value=true) {
        $this->autoSerialize=$value;
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
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return mixed True if the information was read, otherwise false.
     */
    public function get($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            $json=@file_get_contents($file);
            $this->unlock($file);
            if ($this->autoSerialize) return unserialize($json);
            return $json;
        } else {
            return false;
        }
    }

    /**
     * Return if the document exists. It doesn't check until the document is fully unlocked.
     * @param string $id Id of the document.
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return string|bool True if the information was read, otherwise false.
     */
    public function ifExist($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            $exist=file_exists($file);
            $this->unlock($file);
            return $exist;
        } else {
            return false;
        }
    }
    /**
     * Delete document.
     * @param string $id Id of the document
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool if it's unable to unlock or the document doesn't exist.
     */
    public function delete($id,$tries=-1) {
        $file =$this->filename($id);
        if ($this->lock($file,$tries)) {
            $r=@unlink($file);
            $this->unlock($file);
            return $r;
        } else {
            return false;
        }
    }

    /**
     * Copy a document. If the destination exists, it's replaced.
     * @param string $idOrigin
     * @param string $idDestination
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool true if the operation is correct, otherwise it returns false (unable to lock / unable to copy)
     */
    public function copy($idOrigin,$idDestination,$tries=-1) {
        $fileOrigin =$this->filename($idOrigin);
        $fileDestination =$this->filename($idDestination);
        if ($this->lock($fileOrigin,$tries)) {
            if ($this->lock($fileDestination,$tries)) {
                $r=@copy($fileOrigin,$fileDestination);
                $this->unlock($fileOrigin);
                $this->unlock($fileDestination);
                return $r;
            } else {
                $this->unlock($fileOrigin);
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * Rename a document. If the destination exists, it's not renamed
     * @param string $idOrigin
     * @param string $idDestination
     * @param int $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @return bool true if the operation is correct, otherwise it returns false (unable to lock / unable to rename)
     */
    public function rename($idOrigin,$idDestination,$tries=-1) {
        $fileOrigin =$this->filename($idOrigin);
        $fileDestination =$this->filename($idDestination);
        if ($this->lock($fileOrigin,$tries)) {
            if ($this->lock($fileDestination,$tries)) {
                $r=@rename($fileOrigin,$fileDestination);
                $this->unlock($fileOrigin);
                $this->unlock($fileDestination);
                return $r;
            } else {
                $this->unlock($fileOrigin);
                return false;
            }
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
    private function lock($filepath, $maxRetry=-1){
        if ($this->manualLock!=null) return true; //it's already locked manually.
        $maxRetry = ($maxRetry == -1) ? $this->defaultNumRetry : $maxRetry;
        if ($this->strategy==self::DSO_APCU) {
            $try=0;
            while (@apcu_add("documentstoreone." . $filepath,1, $this->maxLockTime)===false && $try<$maxRetry) {
                $try++;

                usleep($this->intervalBetweenRetry);
            }
            return ($try<$maxRetry);
        }
        if ($this->strategy==self::DSO_MEMCACHE) {
            $try=0;
            while (@$this->memcache->add("documentstoreone.".$filepath,1,0, $this->maxLockTime)===false && $try<$maxRetry) {
                $try++;
                usleep($this->intervalBetweenRetry);
            }
            return ($try<$maxRetry);
        }
        if ($this->strategy==self::DSO_REDIS) {
            $try=0;
            while (@$this->redis->set("documentstoreone.".$filepath,1,['NX', 'EX' => $this->maxLockTime])!==true && $try<$maxRetry) {
                $try++;
                usleep($this->intervalBetweenRetry);
            }
            return ($try<$maxRetry);
        }
        if ($this->strategy==self::DSO_FOLDER) {
            clearstatcache();

            $lockname = $filepath . ".lock";
            $life = @filectime($lockname);
            $try = 0;
            while (!@mkdir($lockname) && $try < $maxRetry) {
                $try++;
                if ($life) {
                    if ((time() - $life) > $this->maxLockTime) {
                        rmdir($lockname);
                        $life = false;
                    }
                }
                usleep($this->intervalBetweenRetry);
            }
            return ($try < $maxRetry);
        }
        return false;
    }

    /**
     * Unlocks a document
     * @param $filepath
     * @param bool $forced
     * @return bool
     */
    private function unlock($filepath, $forced=false){
        if ($this->manualLock!=null && !$forced) return true; // it's locked manually it must be unlocked manually.
        if ($this->strategy==self::DSO_APCU) {
            return apcu_delete("documentstoreone." . $filepath);
        }
        if ($this->strategy==self::DSO_MEMCACHE) {
            return $this->memcache->delete("documentstoreone." . $filepath);
        }
        if ($this->strategy==self::DSO_REDIS) {
            return ($this->redis->del("documentstoreone." . $filepath)>0);
        }
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

