<?php

namespace eftec\tests;


use eftec\DocumentStoreOne\DocumentStoreOne;
use PHPUnit\Framework\TestCase;


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

 

    public function test_db()
    {
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("someid","dummy"),"insert or update must be true");

	    $this->assertEquals("dummy",$this->flatcon->get("someid"));

        $this->assertEquals("dummy",$this->flatcon->get("someidxxx",-1,'dummy'));

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
