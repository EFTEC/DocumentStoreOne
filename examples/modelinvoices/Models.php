<?php


class Invoice {
    /** @var integer */
    var $idInvoice;
    /** @var Customer */
    var $customer;
    /** @var string */
    var $date;
    /** @var InvoiceDetail[] */
    var $details;

    /**
     * Invoice constructor.
     * @param int $idInvoice
     * @param string $date
     */
    public function __construct(int $idInvoice=0, string $date="20180811")
    {
        $this->idInvoice = $idInvoice;
        $this->date = $date;
        $this->customer=new Customer();
        $this->details=array();

    }
}

class InvoiceDetail {
    var $idProduct;
    var $unitPrice;
    var $amount;

    /**
     * InvoiceDetail constructor.
     * @param string $idProduct
     * @param float $unitPrice
     * @param int $amount
     */
    public function __construct($idProduct="", $unitPrice=0.5, $amount=0)
    {
        $this->idProduct = $idProduct;
        $this->unitPrice = $unitPrice;
        $this->amount = $amount;
    }
}

class Customer {
    var $name;
    var $address;
    var $phone;

    /**
     * Customer constructor.
     * @param string $name
     * @param string $address
     * @param string $phone
     */
    public function __construct($name="", $address="", $phone="")
    {
        $this->name = $name;
        $this->address = $address;
        $this->phone = $phone;
    }
}
