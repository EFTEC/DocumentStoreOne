<?php

namespace eftec\DocumentStoreOne;

use DateTime;
use Exception;
use Memcache;
use Redis;
use ReflectionObject;

/**
 * Class DocumentStoreOne
 *
 * @version 1.10 2019-03-16
 * @author  Jorge Castro Castillo jcastro@eftec.cl
 * @link    https://github.com/EFTEC/DocumentStoreOne
 * @license LGPLv3
 */
class DocumentStoreOne
{

    const DSO_AUTO = 'auto';
    const DSO_FOLDER = 'folder';
    const DSO_APCU = 'apcu';
    const DSO_MEMCACHE = 'memcached';
    const DSO_REDIS = 'redis';
    /** @var string root folder of the database */
    var $database;
    /** @var string collection (subfolder) of the database */
    var $collection;
    /** @var int Maximium duration of the lock (in seconds). By default it's 2 minutes */
    var $maxLockTime = 120;
    /** @var int Default number of retries. By default it tries 100x0.1sec=10 seconds */
    var $defaultNumRetry = 100;
    /** @var int Interval (in microseconds) between retries. 100000 means 0.1 seconds */
    var $intervalBetweenRetry = 100000;
    /** @var string Default extension (with dot) of the document */
    var $docExt = ".dson";
    /** @var string=['php','php_array','json_object','json_array','none'][$i] */
    var $serializeStrategy = 'php';
    /** @var bool if true then it will never lock or unlock the document. It is useful for a read only base */
    var $neverLock = false;
    /** @var int DocumentStoreOne::DSO_* */
    var $strategy = self::DSO_FOLDER;
    /** @var Redis */
    var $redis;
    /** @var string=['','md5','sha1','sha256','sha512'][$i] Indicates if the key is encrypted or not when it's stored (the file name). Empty means, no encryption. You could use md5,sha1,sha256,.. */
    var $keyEncryption = '';
    private $autoSerialize = false;
    /** @var Memcache */
    private $memcache;
    /**
     * @var int nodeId It is the identifier of the node.<br>
     * It must be between 0..1023<br>
     * If the value is -1, then it randomizes it's value each call.
     */
    var $nodeId = 1;

    /**
     * DocumentStoreOne constructor.
     *
     * @param string $database   root folder of the database
     * @param string $collection collection (subfolder) of the database. If the collection is empty then it uses the root folder.
     * @param string $strategy   =['auto','folder','apcu','memcached','redis'][$i] The strategy is only used to lock/unlock purposes.
     * @param string $server     Used for 'memcached' (localhost:11211) and 'redis' (localhost:6379)
     * @param bool   $autoSerialize If true then the value (inserted) is auto serialized
     * @param string $keyEncryption=['','md5','sha1','sha256','sha512'][$i] it uses to encrypt the name of the keys (filename)
     *
     * @throws Exception
     * @example $flatcon=new DocumentStoreOne(dirname(__FILE__)."/base",'collectionFolder');
     */
    public function __construct(
        $database,
        $collection = '',
        $strategy = 'auto',
        $server = "",
        $autoSerialize = false,
        $keyEncryption = ''
    ) {
        $this->database = $database;
        $this->collection = $collection;
        $this->autoSerialize = $autoSerialize;
        $this->keyEncryption = $keyEncryption;

        //$r=$memcache->connect(MEMCACHE_SERVER, MEMCACHE_PORT);
        $this->setStrategy($strategy, $server);

        if (!is_dir($this->getPath())) {
            throw new Exception("Tsk Tsk, the folder is incorrect or I'm not unable to read  it: " . $this->getPath().'. You could create the collection with createCollection()');
        }
    }

    /**
     * It sets the strategy to lock and unlock the folders
     *
     * @param string $strategy =['auto','folder','apcu','memcached','redis'][$i]
     * @param string $server
     *
     * @throws Exception
     */
    public function setStrategy($strategy, $server = "")
    {
        if ($strategy == self::DSO_AUTO) {
            if (function_exists("apcu_add")) {
                $this->strategy = self::DSO_APCU;
            } else {
                if (class_exists("\Memcache")) {
                    $this->strategy = self::DSO_MEMCACHE;
                } else {
                    $this->strategy = self::DSO_FOLDER;
                }
            }
            $strategy = $this->strategy;
        } else {
            $this->strategy = $strategy;
        }
        switch ($strategy) {
            case self::DSO_FOLDER:
                break;
            case self::DSO_APCU:
                if (!function_exists("apcu_add")) {
                    throw new Exception("APCU is not defined");
                }
                break;
            case self::DSO_MEMCACHE:
                if (!class_exists("\Memcache")) {
                    throw new Exception("Memcache is not defined");
                }
                $this->memcache = new Memcache();
                $host = explode(':', $server);
                $r = @$this->memcache->pconnect($host[0], $host[1]);
                if (!$r) {
                    throw new Exception("Memcache is not open");
                }
                break;
            case self::DSO_REDIS:
                if (!class_exists("\Redis")) {
                    throw new Exception("Redis is not defined");
                }
                if (function_exists('cache')) {
                    $this->redis = cache();// inject using the cache function (if any).
                } else {
                    $this->redis = new Redis();
                    $host = explode(':', $server);
                    $r = @$this->redis->pconnect($host[0], $host[1], 30); // 30 seconds timeout
                    if (!$r) {
                        throw new Exception("Redis is not open");
                    }
                }
                break;
            default:
                throw new Exception("Strategy not defined");
        }
    }

    /**
     * It gets the current path.
     *
     * @return string
     */
    private function getPath()
    {
        return $this->database . "/" . $this->collection;
    }

    /**
     * Util function to fix the cast of an object.
     * Usage utilCache::fixCast($objectRightButEmpty,$objectBadCast);
     *
     * @param object|array $destination Object may be empty with the right cast.
     * @param object|array $source      Object with the wrong cast.
     *
     * @return void
     */
    public static function fixCast(&$destination, $source)
    {
        if (is_array($source)) {
            $getClass = get_class($destination[0]);
            $array = array();
            foreach ($source as $sourceItem) {
                $obj = new $getClass();
                self::fixCast($obj, $sourceItem);
                $array[] = $obj;
            }
            $destination = $array;
        } else {
            $sourceReflection = new ReflectionObject($source);
            $sourceProperties = $sourceReflection->getProperties();
            foreach ($sourceProperties as $sourceProperty) {
                $name = $sourceProperty->getName();
                if (is_object(@$destination->{$name})) {
                    if (get_class(@$destination->{$name}) == "DateTime") {
                        // source->name is a stdclass, not a DateTime, so we could read the value with the field date
                        try {
                            $destination->{$name} = new DateTime($source->$name->date);
                        } catch (Exception $e) {
                            $destination->{$name} = null;
                        }
                    } else {
                        self::fixCast($destination->{$name}, $source->$name);
                    }
                } else {
                    $destination->{$name} = $source->$name;
                }
            }
        }
    }

    /**
     * Set if we need to lock/unlock every time we want to read/write a value
     *
     * @param bool $neverLock if its true the the register is never locked. It is fast but it's not concurrency-safe
     */
    public function setNeverLock($neverLock=true)
    {
        $this->neverLock = $neverLock;
    }

    /**
     * It gets the next sequence. If the sequence doesn't exist, it generates a new one with 1.<br>
     * You could peek a sequence with get('genseq_*name*')<br>
     * If the sequence is corrupt then it's resetted.<br>
     *
     * @param string $name              Name of the sequence.
     * @param int    $tries             number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     * @param int    $init              The initial value of the sequence (if it's created)
     * @param int    $interval          The interval between each sequence. It could be negative.
     * @param int    $reserveAdditional Reserve an additional number of sequence. It's useful when you want to generates many sequences at once.
     *
     * @return bool|int It returns false if it fails to lock the sequence or if it's unable to read thr sequence. Otherwise it returns the sequence
     */
    public function getNextSequence($name = "seq", $tries = -1, $init = 1, $interval = 1, $reserveAdditional = 0)
    {
        $id = "genseq_" . $name;
        $file = $this->filename($id) . ".seq";
        if ($this->lock($file, $tries)) {
            if (file_exists($file)) {
                $read = @file_get_contents($file);
                if ($read === false) {
                    $this->unlock($file);
                    return false; // file exists but i am unable to read it.
                }
                $read = (is_numeric($read)) ? ($read + $interval)
                    : $init; // if the value stored is numeric, then we add one, otherwise, it starts with 1
            } else {
                $read = $init;
            }
            $write = @file_put_contents($file, $read + $reserveAdditional, LOCK_EX);
            $this->unlock($file);
            return ($write === false) ? false : $read;
        } else {
            return false; // unable to lock
        }
    }

    /**
     * <p>This function returns an unique sequence<p>
     * It ensures a collision free number only if we don't do more than one operation
     * per 0.0001 second However,it also adds a pseudo random number (0-4095)
     * so the chances of collision is 1/4095 (per two operations executed every 0.0001 second).<br>
     * It is based on Twitter's Snowflake number.
     *
     * @return float (it returns a 64bit integer).
     */
    public function getSequencePHP()
    {
        $ms = microtime(true); // we use this number as a random number generator (we use the decimals)
        //$ms=1000;
        $timestamp = (double)round($ms * 1000);
        $rand = (fmod($ms, 1) * 1000000) % 4096; // 4096= 2^12 It is the millionth of seconds
        if ($this->nodeId === -1) {
            $number = rand(0, 1023); // a 10bit number.
            $calc = (($timestamp - 1459440000000) << 22) + ($number << 12) + $rand;
        } else {
            $calc = (($timestamp - 1459440000000) << 22) + ($this->nodeId << 12) + $rand;
        }
        usleep(1);
        return '' . $calc;
    }

    /**
     * Convert Id to a full filename. If keyencryption then the name is encrypted.
     *
     * @param string $id
     *
     * @return string full filename
     */
    private function filename($id)
    {
        $file = $this->keyEncryption ? hash($this->keyEncryption, $id) : $id;
        return $this->getPath() . "/" . $file . $this->docExt;
    }

    /**
     * It locks a file
     *
     * @param     $filepath
     * @param int $maxRetry
     *
     * @return bool
     */
    private function lock($filepath, $maxRetry = -1)
    {
        if ($this->neverLock) {
            return true;
        }
        $maxRetry = ($maxRetry == -1) ? $this->defaultNumRetry : $maxRetry;
        if ($this->strategy == self::DSO_APCU) {
            $try = 0;
            while (@apcu_add("documentstoreone." . $filepath, 1, $this->maxLockTime) === false && $try < $maxRetry) {
                $try++;

                usleep($this->intervalBetweenRetry);
            }
            return ($try < $maxRetry);
        }
        if ($this->strategy == self::DSO_MEMCACHE) {
            $try = 0;
            while (@$this->memcache->add("documentstoreone." . $filepath, 1, 0, $this->maxLockTime) === false
                && $try < $maxRetry) {
                $try++;
                usleep($this->intervalBetweenRetry);
            }
            return ($try < $maxRetry);
        }
        if ($this->strategy == self::DSO_REDIS) {
            $try = 0;
            while (@$this->redis->set("documentstoreone." . $filepath, 1, ['NX', 'EX' => $this->maxLockTime]) !== true
                && $try < $maxRetry) {
                $try++;
                usleep($this->intervalBetweenRetry);
            }
            return ($try < $maxRetry);
        }
        if ($this->strategy == self::DSO_FOLDER) {
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
     *
     * @param string $filepath full file path/key of the document to unlock.
     * @param bool   $forced   Use future
     *
     * @return bool
     */
    private function unlock($filepath, $forced = false)
    {
        if ($this->neverLock) {
            return true;
        }
        switch ($this->strategy) {
            case self::DSO_APCU:
                return apcu_delete("documentstoreone." . $filepath);
                break;
            case self::DSO_MEMCACHE:
                return $this->memcache->delete("documentstoreone." . $filepath);
                break;
            case self::DSO_REDIS:
                return ($this->redis->del("documentstoreone." . $filepath) > 0);
                break;
        }
        $unlockname = $filepath . ".lock";
        $try = 0;
        // retry to delete the unlockname folder. If fails then it tries it again.
        while (!@rmdir($unlockname) && $try < $this->defaultNumRetry) {
            $try++;
            usleep($this->intervalBetweenRetry);
        }
        return ($try < $this->defaultNumRetry);
    }

    /**
     * It appends a value to an existing document.
     *
     * @param string $name  Name of the sequence.
     * @param string $addValue
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool It returns false if it fails to lock the document or if it's unable to read the document. Otherwise it returns true
     */
    public function appendValue($name, $addValue, $tries = -1)
    {

        $file = $this->filename($name);
        if ($this->lock($file, $tries)) {

            $fp = @fopen($file, 'a');
            if ($fp === false) {
                $this->unlock($file);
                return false; // file exists but i am unable to open it.
            }
            $r = @fwrite($fp, $addValue);
            @fclose($fp);
            $this->unlock($file);
            return ($r !== false);
        } else {
            return false; // unable to lock
        }
    }

    /**
     * Add a document.
     *
     * @param string       $id       Id of the document.
     * @param string|array $document The document
     * @param int          $tries    number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool True if the information was added, otherwise false
     */
    public function insert($id, $document, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            if (!file_exists($file)) {
                if ($this->autoSerialize) {
                    $write = @file_put_contents($file, $this->serialize($document), LOCK_EX);
                } else {
                    $write = @file_put_contents($file, $document, LOCK_EX);
                }
            } else {
                $write = false;
            }
            $this->unlock($file);
            return ($write !== false);
        } else {
            return false;
        }
    }

    private function serialize($document)
    {
        switch ($this->serializeStrategy) {
            case 'php_array':
                return DocumentStoreOne::serialize_php_array($document);
            case 'php':
                return serialize($document);
                break;
            case 'json_object':
            case 'json_array':
                return json_encode($document);
                break;
            case 'none':
                return $document;
                break;
            default:
                return $document;
        }
    }

    private static function serialize_php_array($document)
    {
        $r = "<?php /** @generated */\nreturn " . var_export($document, true) . ';';
        return $r;
    }

    /**
     * Update a document
     *
     * @param string       $id       Id of the document.
     * @param string|array $document The document
     * @param int          $tries    number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool True if the information was added, otherwise false
     */
    public function update($id, $document, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            if (file_exists($file)) {
                if ($this->autoSerialize) {
                    $write = @file_put_contents($file, $this->serialize($document), LOCK_EX);
                } else {
                    $write = @file_put_contents($file, $document, LOCK_EX);
                }
            } else {
                $write = false;
            }
            $this->unlock($file);
            return ($write !== false);
        } else {
            return false;
        }
    }

    /**
     * Add or update a document.
     *
     * @param string       $id       Id of the document.
     * @param string|array $document The document
     * @param int          $tries    number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool True if the information was added, otherwise false
     */
    public function insertOrUpdate($id, $document, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            if ($this->autoSerialize) {
                $write = @file_put_contents($file, $this->serialize($document), LOCK_EX);
            } else {
                $write = @file_put_contents($file, $document, LOCK_EX);
            }
            $this->unlock($file);
            return ($write !== false);
        } else {
            return false;
        }
    }

    /**
     * Set the current collection. It also could create the collection.
     *
     * @param      $collection
     * @param bool $createIfNotExist if true then it checks if the collection (folder) exists, if not then it's created
     *
     * @return DocumentStoreOne
     */
    public function collection($collection, $createIfNotExist = false)
    {
        $this->collection = $collection;
        if ($createIfNotExist) {
            if (!$this->isCollection($collection)) {
                $this->createCollection($collection);
            }
        }
        return $this;
    }

    /**
     * Check a collection
     *
     * @param $collection
     *
     * @return bool It returns false if it's not a collection (a valid folder)
     */
    public function isCollection($collection)
    {
        $this->collection = $collection;
        return is_dir($this->getPath());
    }

    /**
     * Creates a collection
     *
     * @param $collection
     *
     * @return bool true if the operation is right, false if it fails.
     */
    public function createCollection($collection)
    {
        $oldCollection = $this->collection;
        $this->collection = $collection;
        $r = @mkdir($this->getPath());
        $this->collection = $oldCollection;
        return $r;
    }

    /**
     * It sets if we want to auto serialize the information and we set how it is serialized
     *      php = it serializes using serialize() function
     *      php_array = it serializes using include()/var_export() function. The result could be cached on OpCache
     *      json_object = it is serialized using json (as object)
     *      json_array = it is serialized using json (as array)
     *      none = it is not serialized. Information must be serialized/de-serialized manually
     *      php_array = it is serialized as a php_array
     *
     * @param bool   $value
     * @param string $strategy =['php','php_array','json_object','json_array','none'][$i]
     */
    public function autoSerialize($value = true, $strategy = 'php')
    {
        $this->autoSerialize = $value;
        $this->serializeStrategy = $strategy;
    }

    /**
     * List all the Ids in a collection.
     *
     * @param string $mask see http://php.net/manual/en/function.glob.php
     *
     * @return array|false
     */
    public function select($mask = "*")
    {
        $list = glob($this->database . "/" . $this->collection . "/" . $mask . $this->docExt);
        foreach ($list as &$file) {
            $file = basename($file, $this->docExt);
        }
        return $list;
    }

    /**
     * Get a document
     *
     * @param string $id    Id of the document.
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return mixed True if the information was read, otherwise false.
     */
    public function get($id, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            if ($this->serializeStrategy == 'php_array') {
                $json = @include $file;
            } else {
                $json = @file_get_contents($file);
                $this->unlock($file);
                if ($this->autoSerialize) {
                    switch ($this->serializeStrategy) {
                        case 'php':
                            return unserialize($json);
                            break;
                        case 'json_object':
                            return json_decode($json);
                            break;
                        case 'json_array':
                            return json_decode($json, true);
                            break;
                        case 'none':
                            return $json;
                            break;
                    }
                }
            }
            return $json;
        } else {
            return false;
        }
    }

    /**
     * Return if the document exists. It doesn't check until the document is fully unlocked.
     *
     * @param string $id    Id of the document.
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return string|bool True if the information was read, otherwise false.
     */
    public function ifExist($id, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            $exist = file_exists($file);
            $this->unlock($file);
            return $exist;
        } else {
            return false;
        }
    }

    /**
     * Delete document.
     *
     * @param string $id    Id of the document
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool if it's unable to unlock or the document doesn't exist.
     */
    public function delete($id, $tries = -1)
    {
        $file = $this->filename($id);
        if ($this->lock($file, $tries)) {
            $r = @unlink($file);
            $this->unlock($file);
            return $r;
        } else {
            return false;
        }
    }

    /**
     * Copy a document. If the destination exists, it's replaced.
     *
     * @param string $idOrigin
     * @param string $idDestination
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool true if the operation is correct, otherwise it returns false (unable to lock / unable to copy)
     */
    public function copy($idOrigin, $idDestination, $tries = -1)
    {
        $fileOrigin = $this->filename($idOrigin);
        $fileDestination = $this->filename($idDestination);
        if ($this->lock($fileOrigin, $tries)) {
            if ($this->lock($fileDestination, $tries)) {
                $r = @copy($fileOrigin, $fileDestination);
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
     *
     * @param string $idOrigin
     * @param string $idDestination
     * @param int    $tries number of tries. The default value is -1 (it uses the default value $defaultNumRetry)
     *
     * @return bool true if the operation is correct, otherwise it returns false (unable to lock / unable to rename)
     */
    public function rename($idOrigin, $idDestination, $tries = -1)
    {
        $fileOrigin = $this->filename($idOrigin);
        $fileDestination = $this->filename($idDestination);
        if ($this->lock($fileOrigin, $tries)) {
            if ($this->lock($fileDestination, $tries)) {
                $r = @rename($fileOrigin, $fileDestination);
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

}

