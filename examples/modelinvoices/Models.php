<?php
$drinks=array("Ambasa","Ameyal","Appletiser","Aquarius","Barq's","Beat","Beverly (discontinued in 2009)","Coca-Cola","Caffeine Free Coca-Cola","Coca-Cola Black Cherry Vanilla","Coca-Cola BlāK","Coca-Cola C2","Coca-Cola Clear","Coca-Cola Cherry","Coca-Cola Citra","Coca-Cola Life","Coca-Cola Light","Coca-Cola Light Sango","Coca-Cola Orange","Coca-Cola Raspberry","Coca-Cola Vanilla","Coca-Cola with Lemon","Coca-Cola with Lime","Coca-Cola Zero","Diet Coke","Diet Coke with Lemon","Diet Coke Lime","Diet Coke Plus","Diet Coke with Citrus Zest","New Coke (discontinued in 2002)","Dasani","Delaware Punch","Fanta","Fanta Citrus","Fantasy","Cream Soda","Grape","Strawberry","Tangerine","Wild Strawberry","Orange","Wild Cherry","Fresca","Frescolihi","Frescolita","Full Throttle","Grapetiser","Inca Kola","Diet Inca Kola","Leed (discontinued in 1984)","Lift","Lilt","Limca","Mello Yello","Mr. Pibb","Pibb Xtra","Peartiser","Sprite","Sprite Ice","Sprite Remix (discontinued in 2005)","Sprite Zero","Surge","Tab","Tab Clear","Tab Energy","Thums Up","Vault","Vault Red Blitz");
$names=array('Glendora Bowland','Magan Brungardt','Sarah Lamphere','Lavinia Shaughnessy','Glynda Bayless','Han Salaam','Loree Oloughlin','Mercedez Pretty','Rachell Gatts','Isaiah Quinto','Katharyn Cesar','Catrice Lopinto','Albertina Rady','Rafaela Zuniga','Bruna Steadman','Mira Nick','Reita Scheidler','Elvia Almada','Delaine Eastwood','Odis Havlik','Danial Stanford','Jacquelyn Lefebvre','Krystina Moritz','Gerard Petre','Zella Ciesla','Shelley Pitcock','Delinda Frizzell','Edelmira Christina','Scarlet Held','Cassaundra Kurt','Bella Harling','Carli Orrell','Catherine Flanigan','Klara Crompton','Ashely Cordoba','Jim Washam','Gertha Hereford','Tam Grenz','Erinn Bieker','Audrie Harrah','Sook Applewhite','Mayme Rosin','Eloy Hennessey','Nedra Fleener','Marta Ahlgren','Latosha Heim','Mckinley Bookout','Bonita Salisbury','Tabetha Blose','Jacinto Martens','Shelly Ponte','Letitia Abernathy','Blanca Brazan','Mari Maltese','Rhona Lesesne','Mammie Bjornson','Jonathan Warburton','Wynona Westlund','Terisa Hartsock','Delilah Kovacich','Li Issa','Ruth Parrish','Azucena Sprankle','Pamula Copas','Tonia Heatwole','Simonne Mash','Alonzo Mcmakin','Sandra Richey','Jon Skeens','Cherilyn Smalls','Millicent Lenton','Treena Wolken','Jamika Cardinal','Andrew Griffey','Tiana Corner','Linnea Perham','Rea Mullens','Lorita Clune','Trista Newland','Seth Kimler','Eda Gittens','Grace Stoval','Helaine Weidenbach','Irving Fuhr','Laureen Tankersley','Mohammed Castleman','Werner Azcona','Della Pollard','Bernie Lubin','Ara Gruner','Tonette Wurst','Betsey Whitworth','Adrianna Epps','Elvina Mattox','Ilene Bidwell','Louie Gladden','Jenice Desilets','Sharlene Woolverton','Nana Pettit','Yasmin Trace');

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
class Product {
    /** @var int  */
    var $idProduct;
    /** @var string  */
    var $name;

    /**
     * Product constructor.
     * @param int $idProduct
     * @param string $name
     */
    public function __construct($idProduct=0, $name="")
    {
        $this->idProduct = $idProduct;
        $this->name = $name;
    }
}

class InvoiceDetail {
    /** @var Product */
    var $product;
    var $unitPrice;
    var $amount;

    /**
     * InvoiceDetail constructor.
     * @param Product $product
     * @param float $unitPrice
     * @param int $amount
     */
    public function __construct($product, $unitPrice=0.5, $amount=0)
    {
        $this->product = $product;
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
