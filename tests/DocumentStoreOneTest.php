<?php /** @noinspection PhpDynamicFieldDeclarationInspection */

/** @noinspection ForgottenDebugOutputInspection */

namespace eftec\tests;


use DateTime;
use eftec\DocumentStoreOne\DocumentStoreOne;
use Exception;
use PHPUnit\Framework\TestCase;
use RedisException;
use stdClass;

class DummyClass {

}
class Dummy2Class {

}


class DocumentStoreOneTest extends TestCase
{
	/**
	 * @var DocumentStoreOne
	 */
    protected $flatcon;

    /**
     * @throws Exception
     */
    public function __construct()
    {
	    parent::__construct();
	    $this->flatcon = new DocumentStoreOne(__DIR__ . "/base", '');
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function test_basic_redis(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('redis','127.0.0.1');
        $doc->autoSerialize(true,'php');
        $doc->delete('file1_php');
        $input=[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']];
        $output=$input;
        $this->assertEquals(true,$doc->insert('file1_php',$input));
        $this->assertEquals($output,$doc->get('file1_php'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_apcu_php_array(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('apcu');
        $doc->autoSerialize(true,'php_array');
        $doc->delete('file1_php_array');
        $input=[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']];
        $output=$input;
        $this->assertEquals(true,$doc->insert('file1_php_array',$input));
        $this->assertEquals($output,$doc->get('file1_php_array'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_time():void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('none');
        $doc->autoSerialize(false,'none');
        $doc->delete('file1_none');
        $this->assertEquals(true,$doc->insert('file1_none',"hello"));
        $this->assertEquals(true,$doc->setTimeStamp('file1_none',1500000));
        $this->assertGreaterThanOrEqual(1500000,$doc->getTimeStamp('file1_none'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_none(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('none');
        $doc->autoSerialize(false,'none');
        $doc->delete('file1_none');
        $this->assertEquals(true,$doc->insert('file1_none',"hello"));
        $this->assertEquals(true,$doc->appendValue('file1_none',"world"));
        $this->assertEquals('helloworld',$doc->get('file1_none'));
        $this->assertEquals(false,$doc->noThrowOnError()->get('file1_none2')); // file does not exist
        $this->assertStringContainsString('No such file or directory',$doc->lastError());
        $doc->resetError();
        $this->assertStringContainsString('',$doc->lastError());
        $this->assertEquals(true,$doc->throwable); // testing that throw is returned to the default value.
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_folder_json_array(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('folder');
        $doc->autoSerialize(true,'json_array');
        $doc->delete('file1_json_array');
        $input=[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']];
        $output=$input;
        $this->assertEquals(true,$doc->insert('file1_json_array',$input));
        $this->assertEquals($output,$doc->get('file1_json_array'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_folder_msgpack(): void
    {

        if(function_exists('msgpack_pack')) {
            $doc = new DocumentStoreOne(__DIR__ . "/base", '', 'none', '');
            $doc->setStrategy('folder');
            $doc->autoSerialize(true, 'msgpack');
            $doc->delete('file1_msgpack');
            $input = [['a1' => 1, 'a2' => 'a'], ['a1' => 2, 'a2' => 'b']];
            $newRow=['a1' => 2, 'a2' => 'b'];
            $output = $input;
            $output[]=$newRow;
            $this->assertEquals(true, $doc->insert('file1_msgpack', $input));
            $this->assertEquals(true, $doc->appendValue('file1_msgpack', $newRow));
            $this->assertEquals($output, $doc->get('file1_msgpack'));
        } else {
            var_dump('msgpack not tested');
            $this->assertEquals(true,true); // skipped
        }
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_folder_igbinary(): void
    {
        if(function_exists('igbinary_serialize')) {
            $doc = new DocumentStoreOne(__DIR__ . "/base", '', 'none', '');
            $doc->setStrategy('folder');
            $doc->autoSerialize(true, 'igbinary');
            $doc->delete('file1_igbinary');
            $input = [['a1' => 1, 'a2' => 'a'], ['a1' => 2, 'a2' => 'b']];
            $newRow=['a1' => 2, 'a2' => 'b'];
            $output = $input;
            $output[]=$newRow;
            $this->assertEquals(true, $doc->insert('file1_igbinary', $input));
            $this->assertEquals(true, $doc->appendValue('file1_igbinary', $newRow));
            $this->assertEquals($output, $doc->get('file1_igbinary'));
        } else {
            var_dump('igbinary not tested');
            $this->assertEquals(true,true); // skipped
        }
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_folderObj(): void
    {
        $doc=new DocumentStoreOne(__DIR__."/base",'','none','');
        $doc->setStrategy('folder');
        $doc->autoSerialize(true,'json_object');
        $doc->delete('file1_json_object');
        $input=[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']];
        $output=[(object)['a1'=>1,'a2'=>'a'],(object)['a1'=>2,'a2'=>'b']];
        $this->assertEquals(true,$doc->insert('file1_json_object',$input));
        $this->assertEquals($output,$doc->get('file1_json_object'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function test_basic_folder2(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->throwable=true;
        $doc->setObjectIndex('a1');
        $doc->setStrategy('none');
        $doc->autoSerialize(true,'csv');
        $doc->delete('1');
        $this->assertEquals(true,$doc->insertObject([['a1'=>1,'a2'=>2]]));

        $this->assertEquals([['a1'=>1,'a2'=>2]],$doc->get('1'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_csv_1(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $this->assertNotEmpty($doc::VERSION);
        $doc->docExt='.csv';
        $doc->autoSerialize(true,'csv');
        $doc->csvPrefixColumn='col_';
        $doc->csvStyle();
        $doc->regionalStyle();
        $values=[
            ['name'=>'john1"','age'=>22],
        ];
        $doc->delete('csv1');
        $this->assertTrue($doc->isTabular());
        $this->assertTrue($doc->insert('csv1',$values));
        $this->assertTrue($doc->appendValue('csv1',['name'=>'john2','age'=>33]));
        $this->assertEquals([['name'=>'john1"','age'=>22],['name'=>'john2','age'=>33]],$doc->get('csv1'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function test_others(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->delete('doc1');
        $doc->insert('doc1',"it is a simple document");
        $this->assertGreaterThan(1638986098,$doc->getTimeStamp('doc1'));
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function test_update(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base2",'','none','');
        $doc->delete('doc1');
        $doc->delete('doc2');
        $doc->insert('doc1',"it is a simple document");
        $doc->insert('doc2',"it is a simple document");
        $this->assertEquals(true,$doc->update('doc1','other info'));
        $this->assertEquals('other info',$doc->get('doc1'));
        $this->assertEquals(true,$doc->copy('doc1','doc1copy'));
        $this->assertEquals('other info',$doc->get('doc1copy'));
        $this->assertEquals(true,$doc->rename('doc1copy','doc1'));
        $this->assertEquals(['doc1','doc2'],$doc->select());
        $doc->deleteCollection('colect');
        $doc->createCollection('colect');
        $this->assertEquals(true,$doc->isCollection('colect'));
        $doc->collection='colect';
        $this->assertEquals([],$doc->select());
    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_csv_2(): void
    {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->docExt='.csv';
        $doc->autoSerialize(true,'csv');
        $doc->csvPrefixColumn='';
        $doc->csvStyle();
        $doc->regionalStyle();
        $values=[
            ['john1',22],
        ];
        $doc->delete('csv1');
        $this->assertTrue($doc->insert('csv1',$values));
        $this->assertTrue($doc->appendValue('csv1',['john2',33]));
        $this->assertEquals([['john1',22],['john2',33]],$doc->get('csv1'));
    }
    public function testRelative():void
    {
        $this->assertEquals(true,DocumentStoreOne::isRelativePath(''));
        $this->assertEquals(false,DocumentStoreOne::isRelativePath('/'));
        $this->assertEquals(false,DocumentStoreOne::isRelativePath('/hello'));
        $this->assertEquals(true,DocumentStoreOne::isRelativePath('hello'));
        $this->assertEquals(false,DocumentStoreOne::isRelativePath('c:\\'));
        $this->assertEquals(false,DocumentStoreOne::isRelativePath('c:\\hello'));
        $this->assertEquals(true,DocumentStoreOne::isRelativePath('hello\\hello2'));
    }
    public function testCast(): void
    {
        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $sub=new Dummy2Class();
        $sub->field1='hello';
        $source=new DummyClass();
        $source->item='item1';
        $source->item2=new DateTime();
        $source->item3=[new Dummy2Class(),new Dummy2Class(),];
        $final=new DummyClass();
        $subc=new stdClass();
        $subc->field1='world';
        $final->item3=[$subc,$subc];
        DocumentStoreOne::fixCast($final,$source);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(true,$final->item2 instanceof DateTime);
        $this->assertEquals(true,$final->item3[0] instanceof Dummy2Class);

    }

    /**
     * @throws RedisException
     * @throws Exception
     * @throws Exception
     */
    public function test_db(): void
    {
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("someid","dummy"),"insert or update must be true");

	    $this->assertEquals("dummy",$this->flatcon->get("someid"));
        try {
            $this->flatcon->get("someidxxx",-1,'dummy');
            $r=true;
        } catch (Exception $ex) {
            $r=false;
        }
        $this->assertEquals(false,$r);

	    $seq1=$this->flatcon->getNextSequence("myseq");
        $this->assertEquals($seq1+1,$this->flatcon->getNextSequence("myseq"),"sequence must be +1");


        $s1=$this->flatcon->getSequencePHP();
        $s2=$this->flatcon->getSequencePHP();
        $this->assertEquals(false,$s1===$s2,"sequence must be differents");


    }

    /**
     * @throws RedisException
     */
    public function test_db2(): void
    {
        $this->flatcon->autoSerialize(true,'php');
        $dataOriginal=[
            ['id'=>1,'cat'=>'vip']
            ,['id'=>2,'cat'=>'vip']
            ,['id'=>3,'cat'=>'normal']];
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("datas",$dataOriginal)
            ,"insert or update must be true");
        $this->assertEquals([
            ['id'=>1,'cat'=>'vip']
            ,['id'=>2,'cat'=>'vip']
            ,['id'=>3,'cat'=>'normal']],$this->flatcon->get("datas"));
        $this->assertEquals([['id'=>3,'cat'=>'normal']]
            ,$this->flatcon->getFiltered("datas",-1,false,['cat'=>'normal']));
        $this->assertEquals([2=>['id'=>3,'cat'=>'normal']]
            ,$this->flatcon->getFiltered("datas",-1,false,['cat'=>'normal'],false));
        $this->flatcon->autoSerialize(false,'php');
    }



}
