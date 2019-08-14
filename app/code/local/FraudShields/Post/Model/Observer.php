
<?php
  
class FraudShields_Post_Model_Observer {
  public $cardnum;
  public $exp;

  public function postSomething1(Varien_Event_Observer $observer) {
    $order = $observer->getEvent()->getOrder();
    $this->cardnum = $order->getPayment()->getCcNumber();
    $this->exp = str_pad($order->getPayment()->getCcExpMonth(),2,'0',STR_PAD_LEFT) . substr($order->getPayment()->getCcExpYear(), -2);
  }

  public function postSomething(Varien_Event_Observer $observer) {
    $parms=Mage::app()->getFrontController()->getRequest()->getParams();
  	//Get Payment Detail
  	extract($parms);
  	$payment;
    $order = $observer->getEvent()->getOrder();
    $cardsStorage = Mage::getModel('paygate/authorizenet_cards')->setPayment($order->getPayment());
    foreach ($cardsStorage->getCards() as $card) {
      $lastTransId = $card->getLastTransId();
    }
//Get Shipping Detail
    $shipping=array();
    $_shippingAddress = $order->getShippingAddress();
    $_billingAddress = $order->getBillingAddress();
    $items = $order->getAllItems();
    $itemcount=count($items);
    $name=array();
    $unitPrice=array();
    $sku=array();
    $ids=array();
    $qty=array();
    foreach ($items as $itemId => $item)
    {
       $name[] = $item->getName();
       $unitPrice[]=$item->getPrice();
       $sku[]=$item->getSku();
       $ids[]=$item->getProductId();
       $qty[]=$item->getQtyToInvoice();
    }
    $shipping_street = $_shippingAddress->getStreet();
    $billing_street = $_billingAddress->getStreet();
    $message = Mage::getModel('giftmessage/message');
    $gift_message_id = $order->getGiftMessageId();
    $message->load((int)$gift_message_id);
    $postdata=array(
      "order_id" => $order->getIncrementId(),
      "customername" => $order->getCustomerName(),  // Customer Name
      "customeremail" => $order->getCustomerEmail(),// Customer Email
      "shippingmethod" => $order->getShippingMethod(), // Shipping Method
      "shippingamount" => $order->getShippingAmount(),  // ShippingAmount
      "grandtotal" => $order->getGrandTotal(), // Grand Total
      "domain" => Mage::getBaseUrl(), // Domain nname
      "shipping_firstname" => $_shippingAddress->getFirstname(),
      "shipping_lastname" => $_shippingAddress->getLastname(),
      "shipping_company" => $_shippingAddress->getCompany(),
      "shipping_street" => $shipping_street[0],
      "shipping_region" => $_shippingAddress->getRegion(),
      "shipping_city" => $_shippingAddress->getCity(),
      "shipping_postcode" => $_shippingAddress->getPostcode(),
      "shipping_telephone" => $_shippingAddress->getTelephone(),
      "shipping_country_id" => $_shippingAddress->getCountry(),
      "billing_firstname" => $_billingAddress->getFirstname(),
      "billing_lastname" => $_billingAddress->getLastname(),
      "billing_company" => $_billingAddress->getCompany(),
      "billing_street" => $billing_street[0],
      "billing_region" => $_billingAddress->getRegion(),
      "billing_city" => $_billingAddress->getCity(),
      "billing_postcode" => $_billingAddress->getPostcode(),
      "billing_telephone" => $_billingAddress->getTelephone(),
      "billing_country_id" => $_billingAddress->getCountry(),
      "ip" => $_SERVER["REMOTE_ADDR"],
      "cardnum" => $this->cardnum,
      "exp" => $this->exp,
      "key" => "8bfe220e83ec40c5ab7c5f21ca3e8643ItsHot",
      "gift_message" => $message->getData('message'),
      "version" => "1.2",
      "GatewayRefNum" => $lastTransId, // TODO: Add
      "GatewayResult" => "", // TODO: Add
      "GatewayError" => "", // TODO: Add
      "GatewayAVS" => "", // TODO: Add
      "GatewayCVV" => "", // TODO: Add
      "OrderItems" => "" // TODO: Add
    );

    $json = json_encode($postdata);

    // Server url where json data send
    $curl = curl_init('https://x1.fidelipay.com/fs');
    curl_setopt($curl, CURLOPT_FAILONERROR, true); 
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($curl, CURLOPT_POSTFIELDS,$json);
    $result = curl_exec($curl);
  }
}