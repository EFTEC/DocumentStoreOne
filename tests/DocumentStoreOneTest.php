<?php

namespace eftec\tests;


use eftec\DocumentStoreOne\DocumentStoreOne;
use PHPUnit\Framework\TestCase;

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

    public function __construct()
    {
	    parent::__construct();
	    $this->flatcon = new DocumentStoreOne(__DIR__ . "/base", '');
    }

    public function test_basic_redis() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('redis','127.0.0.1');
        $doc->autoSerialize(true,'php');
        $doc->delete('file1');
        $this->assertEquals(true,$doc->insert('file1',['a1'=>1,'a2'=>2]));
        $this->assertEquals(['a1'=>1,'a2'=>2],$doc->get('file1'));
    }
    public function test_basic_apcu() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('apcu');
        $doc->autoSerialize(true,'php_array');
        $doc->delete('file1');
        $this->assertEquals(true,$doc->insert('file1',['a1'=>1,'a2'=>2]));
        $this->assertEquals(['a1'=>1,'a2'=>2],$doc->get('file1'));
    }
    public function test_basic_folder() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->setStrategy('folder');
        $doc->autoSerialize(true,'json_array');
        $doc->delete('file1');
        $this->assertEquals(true,$doc->insert('file1',['a1'=>1,'a2'=>2]));
        $this->assertEquals(['a1'=>1,'a2'=>2],$doc->get('file1'));
    }
    public function test_basic_folder2() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->throwable=true;
        $doc->setObjectIndex('a1');
        $doc->setStrategy('none');
        $doc->autoSerialize(true,'csv');
        $doc->delete('1');
        $this->assertEquals(true,$doc->insertObject([['a1'=>1,'a2'=>2]]));

        $this->assertEquals([['a1'=>1,'a2'=>2]],$doc->get('1'));
    }

    public function test_csv_1() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
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
    public function test_others() {
        $doc=new DocumentStoreOne(__DIR__ . "/base",'','none','');
        $doc->delete('doc1');
        $doc->insert('doc1',"it is a simple document");
        $this->assertGreaterThan(1638986098,$doc->getTimeStamp('doc1'));
    }
    public function test_update() {
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

    public function test_csv_2() {
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
    public function testCast() {
        $sub=new Dummy2Class();
        $sub->field1='hello';
        $source=new DummyClass();
        $source->item='item1';
        $source->item2=new \DateTime();
        $source->item3=[new Dummy2Class(),new Dummy2Class(),];
        $final=new DummyClass();
        $subc=new \stdClass();
        $subc->field1='world';
        $final->item3=[$subc,$subc];
        DocumentStoreOne::fixCast($final,$source);
        $this->assertEquals(true,$final->item2 instanceof \DateTime);
        $this->assertEquals(true,$final->item3[0] instanceof Dummy2Class);

    }

    public function test_db()
    {
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("someid","dummy"),"insert or update must be true");

	    $this->assertEquals("dummy",$this->flatcon->get("someid"));
        try {
            $this->flatcon->get("someidxxx",-1,'dummy');
            $r=true;
        } catch (\Exception $ex) {
            $r=false;
        }
        $this->assertEquals(false,$r);

	    $seq1=$this->flatcon->getNextSequence("myseq");
        $this->assertEquals($seq1+1,$this->flatcon->getNextSequence("myseq"),"sequence must be +1");


        $s1=$this->flatcon->getSequencePHP();
        $s2=$this->flatcon->getSequencePHP();
        $this->assertEquals(false,$s1===$s2,"sequence must be differents");

        
    }
    public function test_db2()
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
