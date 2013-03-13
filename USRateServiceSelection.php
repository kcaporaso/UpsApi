<?php
/**
 * Handles interfacing with UPS's Rates and Service Selection online tool.
 * 
 * @author Kevin Caporaso (capnetconsulting@gmail.com)
 * @package php_ups_api
 * @todo Finish Implementation.  The basic pieces are in place
 * and work from the tests/rate_test.php sample file.
 */

class UpsAPI_USRateServiceSelection extends UpsAPI {
	/**
	 * Shipper (where the package is being shipped FROM)
	 * 
	 * @access protected
	 * @param array
	 */
	protected $shipper;

	/**
	 * ShipTo (where the package is being shipped TO)
	 * 
	 * @access protected
	 * @param array
	 */
	protected $ship_to;

	/**
	 * PickupType '01' (daily pickup), '03' (customer counter),
    *            '06' (one time pickup) for more see UPS RateDeveloperGuide.pdf
    *             on the UPS OnLineTools website.
	 * 
	 * @access protected
	 * @param string
	 */
	protected $pickup_type;
	
	/**
	 * Package (Details about the packaging that's being shipped)
	 * 
	 * @access protected
	 * @param array ['code'], ['weight']
	 */
	protected $package;

	/**
	 * Service (2 digit code telling which UPS Service to use)
	 * 
	 * @access protected
	 * @param string (.e.g 03 = UPS Ground)
	 */
	protected $service;
	
	/**
	 * Constructor for the Object
	 * 
	 * @access public
	 * @param array $shipper array of address parts of shipper.
	 * @param array $ship_to array of address parts of ship to.
	 * @param array $pickup_type string of UPS pickup type.
	 * @param array $package array of details about the packaging, including weight.
	 * @param array $service string describing which UPS service to use (UPS Ground, Air, etc)
	 */
	public function __construct($shipper, $ship_to, $pickup_type, $package, $service) {
		parent::__construct();
		
		// set object properties
		$this->server = $GLOBALS['ups_api']['server'].'/ups.app/xml/Rate';
		$this->shipper = $shipper;
		$this->ship_to = $ship_to;
		$this->pickup_type = $pickup_type;
		$this->package = $package;
		$this->service = $service;

	} // end function __construct()
	
	/**
	 * Gets the current ship_to city on the object
	 *	
	 * @access public
	 * @return string the current city
	 */
	public function getShipToCity()
	{
		return $this->ship_to['city'];
	} // end function getShipToCity()
	
	/**
	 * Sets the ship_to city on the object
	 * 
	 * @access public
	 * @param string $city city to set on the object
	 */
	public function setShipToCity($city)
	{
		$this->ship_to['city'] = $city;
		
		return true;
	} // end function setShipToCity()
	
	/**
	 * Gets the current full address on the object
	 * 
	 * @access public
	 * @return array the current ship_to address
	 */
	public function getFullShipToAddress()
	{
		return $this->ship_to;
	} // end function getFullShipToAddress()
	
	/**
	 * Sets the full ship_to address on the object
	 * 
	 * @access public
	 * @param array $address address to set on the object
	 */
	public function setFullShipToAddress($address)
	{
		$this->ship_to = $address;
		return true;
	} // end function setFullAddress()
	
	/**
	 * Gets the current ship_to state on the object
	 * 
	 * @access public
	 * @return string the current ship_to state
	 */
	public function getShipToState()
	{
		return $this->ship_to['state'];
	} // end function getShipToState()
	
	/**
	 * Sets the ship_to state on the object
	 * 
	 * @access public
	 * @param string $state ship_to state to set on the object
	 */
	public function setShipToState($state)
	{
		$this->ship_to['state'] = $state;
		
		return true;
	} // end function setShipToState()
	
	/**
	 * Gets the current ship_to zip code on the object
	 * 
	 * @access public
	 * @return integer the curret ship_to zip code
	 */
	public function getShipToZipCode()
	{
		return $this->ship_to['zip_code'];
	} // end function getShipToZipCode()
	
	/**
	 * Sets the ship_to zip code on the object
	 * 
	 * @access public
	 * @param integer $zip_code ship_to zip code to set on the object
	 */
	public function setShipToZipCode($zip_code)
	{
		$this->ship_to['zip_code'] = $zip_code;
	} // end function setZipCode()
	
	/**
	 * Builds the XML used to make the request
	 * 
	 * If $customer_context is an array it should be in the format:
	 * $customer_context = array('Element' => 'Value');
	 * 
	 * @access public
	 * @param array|string $cutomer_context customer data
	 * @return string $return_value request XML
	 */
	public function buildRequest($customer_context = null)
	{
		/** create DOMDocument objects **/
		$access_dom = new DOMDocument('1.0');
		$rate_dom = new DOMDocument('1.0');
		
		/** create the AccessRequest element **/
		$access_element = $access_dom->appendChild(
			new DOMElement('AccessRequest'));
		$access_element->setAttributeNode(new DOMAttr('xml:lang', 'en-US'));
		
		// creat the child elements
		$access_element->appendChild(
			new DOMElement('AccessLicenseNumber', $this->access_key));
		$access_element->appendChild(
			new DOMElement('UserId', $this->username));
		$access_element->appendChild(
			new DOMElement('Password', $this->password));
		
		
		/** create the RatingServiceSelectionRequest element **/
		$rate_element = $rate_dom->appendChild(
			new DOMElement('RatingServiceSelectionRequest'));
		$rate_element->setAttributeNode(new DOMAttr('xml:lang', 'en-US'));
			
		// create the child elements
		$request_element = $rate_element->appendChild(
			new DOMElement('Request'));
		//$rate_element = $rate_element->appendChild(
	   //		new DOMElement('Address'));
		
		// create the children of the Request element
		$transaction_element = $request_element->appendChild(
			new DOMElement('TransactionReference'));
		$request_element->appendChild(
			new DOMElement('RequestAction', 'Rate'));
		$request_element->appendChild(
			new DOMElement('RequestOption', 'Rate'));

		// create the children of the TransactionReference element
		$transaction_element->appendChild(
			new DOMElement('XpciVersion', '1.0'));
		
		// check if we have customer data to include
		if (!empty($customer_context))
		{
			if (is_array($customer_context))
			{
				$customer_element = $transaction_element->appendChild(
					new DOMElement('CustomerContext'));

				// iterate over the array of customer data
				foreach ($customer_context as $element => $value)
				{
					$customer_element->appendChild(
						new DOMElement($element, $value));
				} // end for each customer data
			} // end if the customer data is an array
			else
			{
				$transaction_element->appendChild(
					new DOMElement('CustomerContext', $customer_context));
			} // end if the customer data is a string
		} // end if we have customer data to include
		
      /** create a <PickupType/> child of rate_element. **/
		$pickup_element = $rate_element->appendChild(
			new DOMElement('PickupType'));
      $pickup_element->appendChild(
         new DOMElement('Code', $this->pickup_type));
		
      /** create a <Shipment/> element **/
      $shipment_element = $rate_element->appendChild(
         new DOMElement('Shipment'));
      /** create a <Shipper/> element **/
      $shipper_element = $shipment_element->appendChild(
         new DOMElement('Shipper'));
      // Now append the Shipper info.
      $shipper_address = $shipper_element->appendChild(
         new DOMElement('Address'));

		/** create the children of the Address Element **/
		// check if each was entered...
		$create = (!empty($this->shipper['addressline1']))
			? $shipper_address->appendChild(new DOMElement(
				'AddressLine1', $this->shipper['addressline1'])) : false;

		$create = (!empty($this->shipper['addressline2']))
			? $shipper_address->appendChild(new DOMElement(
				'AddressLine2', $this->shipper['addressline2'])) : false;

		$create = (!empty($this->shipper['city']))
			? $shipper_address->appendChild(new DOMElement(
				'City', $this->shipper['city'])) : false;

		$create = (!empty($this->shipper['state']))
			? $shipper_address->appendChild(new DOMElement(
				'StateProvinceCode', $this->shipper['state'])) : false;

		$create = (!empty($this->shipper['zip_code'])) 
			? $shipper_address->appendChild(new DOMElement(
				'PostalCode', $this->shipper['zip_code'])) : false;

      // Default to US only!
      $shipper_address->appendChild(new DOMElement('CountryCode', 'US'));
		unset($create);

      /** create the ship_to element/children **/
      $shipto_element = $shipment_element->appendChild(
         new DOMElement('ShipTo'));
      $shipto_address = $shipto_element->appendChild(
         new DOMElement('Address'));
     
		// check if each was entered...
		$create = (!empty($this->ship_to['addressline1']))
			? $shipto_address->appendChild(new DOMElement(
				'AddressLine1', $this->ship_to['addressline1'])) : false;

		$create = (!empty($this->ship_to['addressline2']))
			? $shipto_address->appendChild(new DOMElement(
				'AddressLine2', $this->ship_to['addressline2'])) : false;

		$create = (!empty($this->ship_to['city']))
			? $shipto_address->appendChild(new DOMElement(
				'City', $this->ship_to['city'])) : false;

		$create = (!empty($this->ship_to['state']))
			? $shipto_address->appendChild(new DOMElement(
				'StateProvinceCode', $this->ship_to['state'])) : false;

		$create = (!empty($this->ship_to['zip_code'])) 
			? $shipto_address->appendChild(new DOMElement(
				'PostalCode', $this->ship_to['zip_code'])) : false;

      // Default to US only!
      $shipto_address->appendChild(new DOMElement('CountryCode', 'US'));
		unset($create);
		
      /** Insert the UPS service to use, UPS Ground, Air, etc. **/
      $service_element = $shipment_element->appendChild(
         new DOMElement('Service'));
      $service_element->appendChild(
         new DOMElement('Code', $this->service));


      /** Package details **/
      $package_element = $shipment_element->appendChild(
         new DOMElement('Package'));
      $package_type = $package_element->appendChild(
         new DOMElement('PackagingType'));
      $package_type->appendChild(new DOMElement('Code', $this->package['code']));

      $package_weight = $package_element->appendChild(
         new DOMElement('PackageWeight'));
      $package_weight->appendChild(new DOMElement('Weight', $this->package['weight']));

		/** generate the XML **/
		$access_xml = $access_dom->saveXML();
		$address_xml = $rate_dom->saveXML();
		$return_value = $access_xml.$address_xml;
		
		return $return_value;
	} // end function buildRequest()
	
	/**
	 * Gets total charges
	 * 
	 * @access public
    * @param  response - this is the xml response from UPS.
	 * @return string   - total shipping charges
	 */
	public function getTotalCharges($response)
	{
      $return_value = ''; 
      $doc = new DOMDocument();
      if (!empty($response))
      {
         $doc->loadXML($response);
         $xpath = new DOMXPath($doc); 
         $query = "//RatingServiceSelectionResponse/RatedShipment/TotalCharges/MonetaryValue"; 
         $node = $xpath->query($query);
         // Verify there is a DOMNodeList that came back.
         if ($node->item(0))
         {
            $return_value = $node->item(0)->nodeValue;
         }
		   return $return_value;
      }
      else 
      {
         return $return_value; 
      }
	} // end function getTotalCharges()

   /*
    * Tells whether or not we got a successful result from
    * our UPS query for rates and/or service.
    *
    * @access public
    * @param  response - this is the xml response from UPS.
    * @return bool - true/false
    */
   public function isGoodResponse($response)
   {
      $doc = new DOMDocument();
      if (!empty($response))
      {
         $doc->loadXML($response);
         $xpath = new DOMXPath($doc); 
         $query = "//RatingServiceSelectionResponse/Response/ResponseStatusCode"; 
         $node = $xpath->query($query);
         // Verify there is a DOMNodeList that came back.
         if ($node->item(0))
         {
            if ($node->item(0)->nodeValue == 0)
            {
               return false;
            }
            return true;
         }
      } 
      return false;
   }

   /**
    * Returns the error description when a request fails.
    *
    * NOTE: Only call if isGoodResponse returns false, otherwise there
    *       is no error to return.
    *
    * @access public
    * @param  response - this the xml response from UPS
    * @param  &severity - will set this to the severity of the request failure.
    *         (e.g. TransientError (temporary try again laster) 
    *               HardError      (permanent failure) 
    *               Warning        (request processed but client should note
    *                               potential problems)
    * @param  &errorcode = will set this to the actual integer errorcode returned
    *         by UPS.
    *
    * @return string - description of failure
    */
    public function getError($response, &$severity, &$errorcode)
    {
      $return_value = '';
      $severity = '';
      $errorcode = '';
      $doc = new DOMDocument();
      if (!empty($response))
      {
         $doc->loadXML($response);
         $xpath = new DOMXPath($doc); 
         $query = "//RatingServiceSelectionResponse/Response/Error/ErrorDescription"; 
         $node = $xpath->query($query);
         // Verify there is a DOMNodeList that came back.
         if ($node->item(0))
         {
            $return_value = $node->item(0)->nodeValue;
         }

         unset($query);
         unset($node);
         $query = "//RatingServiceSelectionResponse/Response/Error/ErrorSeverity"; 
         $node = $xpath->query($query);
         // Verify there is a DOMNodeList that came back.
         if ($node->item(0))
         {
            $severity = $node->item(0)->nodeValue;
         }

         unset($query);
         unset($node);
         $query = "//RatingServiceSelectionResponse/Response/Error/ErrorCode"; 
         $node = $xpath->query($query);
         // Verify there is a DOMNodeList that came back.
         if ($node->item(0))
         {
            $errorcode = $node->item(0)->nodeValue;
         }
      } 
      return $return_value;
    }

} // end class UpsAPI_USRateServiceSelection

?>
