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

    public function setUp()
    {
	    $this->flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", '');
    }

 

    public function test_db()
    {
        $this->assertEquals(true,$this->flatcon->insertOrUpdate("someid","dummy"),"insert or update must be true");

	    $this->assertEquals("dummy",$this->flatcon->get("someid"));
    }



  
}
