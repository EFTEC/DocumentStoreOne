<?php

namespace eftec\tests;


use eftec\DocumentStoreOne\DocumentStoreOne;
use Exception;
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
	    $this->flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", '');
    }

 

    public function test_db()
    {
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("someid","dummy"),"insert or update must be true");

	    $this->assertEquals("dummy",$this->flatcon->get("someid"));

	    $seq1=$this->flatcon->getNextSequence("myseq");
        $this->assertEquals($seq1+1,$this->flatcon->getNextSequence("myseq"),"sequence must be +1");


        $s1=$this->flatcon->getSequencePHP();
        $s2=$this->flatcon->getSequencePHP();
        $this->assertEquals(false,$s1===$s2,"sequence must be differents");
    }



  
}
