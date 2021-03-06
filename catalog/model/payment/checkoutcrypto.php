<?php 
/*
Copyright (c) 2013 John Atkinson (jga)
*/

class ModelPaymentCheckoutCrypto extends Model {
  	public function getMethod($address) {
		$this->load->language('payment/checkoutcrypto');
		
		if ($this->config->get('checkoutcrypto_status')) {
        	$status = TRUE;
		} else {
			$status = FALSE;
		}

		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'         	=> 'checkoutcrypto',
        		'title'      	=> $this->language->get('text_title'),
				'sort_order' 	=> $this->config->get('checkoutcrypto_sort_order'),
      		);
    	}
   
    	return $method_data;
  	}
}
?>
