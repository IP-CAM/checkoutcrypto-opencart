<?php 
/*
Copyright (c) 2013 John Atkinson (jga)
 */
require('cc.php');

class ControllerPaymentCheckoutCrypto extends Controller {
	private $error = array();
	private $payment_module_name  = 'checkoutcrypto';
	
	public function install() {
		$this->load->model('payment/checkoutcrypto');
		$this->load->model('setting/setting');
		$this->model_payment_checkoutcrypto->install();
        $this->model_setting_setting->editSetting('checkoutcrypto', array('checkoutcrypto_api_key'=>0));
	}


	public function uninstall() {
		$this->load->model('payment/checkoutcrypto');
        $this->model_payment_checkoutcrypto->uninstall();

    }

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/'.$this->payment_module_name)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        if (!$this->request->post['checkoutcrypto_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }else{
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('checkoutcrypto', array('checkoutcrypto_api_key'=>$this->request->post['checkoutcrypto_api_key']));
	$apikey = $this->request->post['checkoutcrypto_api_key'];
}
        if(isset($this->request->post['checkoutcrypto_refresh']) AND $this->request->post['checkoutcrypto_refresh'] == 'on' AND isset($apikey)) {
            //refresh coins here    
            $result = $this->ccRefresh($apikey);
            if(!$result) {
                $this->error['refresh'] = $this->language->get('error_refresh');
            }
        }

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
    }

    private function ccRefresh($apikey) {

			$this->load->model('setting/setting');        
		try {
            $ccApi = new CheckoutCryptoApi($apikey);
            $response = $ccApi->query(array('action' => 'refreshcoins','apikey' => $apikey));
        } catch (exception $e) {
            //var_dump($e);
        }

        if($response['response']['status'] == 'success') {

            $coins = $response['response']['coins'];
            foreach($coins as $coin) {

                $coin_name = $coin['coin_name'];
                $coin_code = $coin['coin_code'];
                $coin_rate = $coin['rate'];
                $coin_img = $coin['coin_image'];
				
				$saveto = '../'.'image/data/checkoutcrypto/'.$coin_name.'.png';

				$ch = curl_init ($coin_img);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
				$raw=curl_exec($ch);
				curl_close ($ch);
				if(file_exists($saveto)){
					unlink($saveto);
				}
				$fp = fopen($saveto,'x');
				fwrite($fp, $raw);
				fclose($fp);

				$basepath = $this->config->get('config_url');
				$coin_img = $basepath.'image/data/checkoutcrypto/'.$coin_name.'.png';
                //check if coin exists
                try {
                    $query = $this->db->query("SELECT coin_rate FROM " . DB_PREFIX . "cc_coins WHERE coin_code = '".$coin_code."'");
                } catch (exception $e) {
                    //var_dump($e);
                }

                if(!isset($query->row['coin_rate'])) {
                    //coin does not exists, insert
                    try {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "cc_coins (coin_code, coin_name, coin_rate, coin_img, date_added) VALUES ('".$coin_code."', '".$coin_name."', ".$coin_rate.", '".$coin_img."', NOW())");
                    } catch (exception $e) {
                        //var_dump($e);
                    }
                } else {
                    //coin exists, update
                    try {
                        $this->db->query("UPDATE " . DB_PREFIX . "cc_coins SET coin_rate = '".$coin_rate."' WHERE coin_code = '".$coin_code."'");
                    } catch (exception $e) {
                        //var_dump($e);
                    }
                }
            }

        } else {
            return FALSE;
        }
        return TRUE;
    }

	public function index() {
		$this->load->language('payment/'.$this->payment_module_name);
		$this->load->model('setting/setting');
	
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting($this->payment_module_name, $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect(HTTPS_SERVER . 'index.php?route=extension/payment&token=' . $this->session->data['token']);
		}
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		//edit this language code once we know all the strings we need
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');
		$this->data['text_8'] = $this->language->get('text_8');
		$this->data['text_7'] = $this->language->get('text_7');
		$this->data['text_6'] = $this->language->get('text_6');
		$this->data['text_5'] = $this->language->get('text_5');
		$this->data['text_4'] = $this->language->get('text_4');
		$this->data['text_3'] = $this->language->get('text_3');
        $this->data['text_2'] = $this->language->get('text_2');

        $this->data['text_sort_order'] = $this->language->get('text_sort_order');
        $this->data['server_live'] = $this->language->get('server_live');
        $this->data['server_beta'] = $this->language->get('server_beta');

  		$this->data['entry_refresh'] = $this->language->get('entry_refresh');
        $this->data['entry_api_key'] = $this->language->get('entry_api_key');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_show_currency'] = $this->language->get('entry_show_currency');
        $this->data['entry_cc_decimal'] = $this->language->get('entry_cc_decimal');
        $this->data['entry_countdown_timer'] = $this->language->get('entry_countdown_timer');
        $this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_server'] = $this->language->get('entry_server');
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel']= $this->language->get('button_cancel');

		$this->data['tab_general']= $this->language->get('tab_general');
		//end language code
		
        if (isset($this->error['api_key'])) {
            $this->data['error_api_key'] = $this->error['api_key'];
        } else {
            $this->data['error_api_key'] = '';
        }

        if (isset($this->error['refresh'])) {
            $this->data['error_refresh'] = $this->error['refresh'];
        } else {
            $this->data['error_refresh'] = '';
        }

		 if (isset($this->error['countdown_timer'])) {
            $this->data['error_countdown_timer'] = $this->error['countdown_timer'];
        } else {
            $this->data['error_countdown_timer'] = '';
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_payment'),
            'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('payment/checkoutcrypto', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
		
		$result = $this->db->query("SHOW COLUMNS FROM ". DB_PREFIX ."order;");
		$rows = $result->rows;
		$max = $result->num_rows;
		$checkoutcrypto_total_in_db = 0;
        $checkoutcrypto_address_in_db = 0;
        $checkoutcrypto_order_id_in_db = 0;

		for($i = 0;$i < $max;$i++) {
			if($rows[$i]["Field"] == "checkoutcrypto_total") {
				$checkoutcrypto_total_in_db = 1;
			}
			if($rows[$i]["Field"] == "checkoutcrypto_address") {
				$checkoutcrypto_address_in_db = 1;
            }
            if($rows[$i]["Field"] == "checkoutcrypto_order_id") {
                $checkoutcrypto_order_id_in_db = 1;
            }
		}
		if(!$checkoutcrypto_total_in_db) {
			$this->db->query("ALTER TABLE ". DB_PREFIX ."order ADD checkoutcrypto_total DOUBLE AFTER currency_value;");
		}
		if(!$checkoutcrypto_address_in_db) {
			$this->db->query("ALTER TABLE ". DB_PREFIX ."order ADD checkoutcrypto_address VARCHAR(34) AFTER checkoutcrypto_total;");
		}
        if(!$checkoutcrypto_order_id_in_db) {
            $this->db->query("ALTER TABLE ". DB_PREFIX ."order ADD checkoutcrypto_order_id INT(34) AFTER checkoutcrypto_address;");
        }

		$this->data['action'] = HTTPS_SERVER . 'index.php?route=payment/'.$this->payment_module_name.'&token=' . $this->session->data['token'];
		$this->data['cancel'] = HTTPS_SERVER . 'index.php?route=extension/payment&token=' . $this->session->data['token'];	

		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post[$this->payment_module_name.'_api_key'])) {
			$this->data[$this->payment_module_name.'_api_key'] = $this->request->post[$this->payment_module_name.'_api_key'];
		} else {
			$this->data[$this->payment_module_name.'_api_key'] = $this->config->get($this->payment_module_name.'_api_key');
		} 
		
        if (isset($this->request->post[$this->payment_module_name.'_show_currency'])) {
			$this->data[$this->payment_module_name.'_show_currency'] = $this->request->post[$this->payment_module_name.'_show_currency'];
        } else {
			$this->data[$this->payment_module_name.'_show_currency'] = $this->config->get($this->payment_module_name.'_show_currency');
		}
        if (isset($this->request->post[$this->payment_module_name.'_cc_decimal'])) {
			$this->data[$this->payment_module_name.'_cc_decimal'] = $this->request->post[$this->payment_module_name.'_cc_decimal'];
		} else {
			$this->data[$this->payment_module_name.'_cc_decimal'] = $this->config->get($this->payment_module_name.'_cc_decimal');
		}
        if (isset($this->request->post[$this->payment_module_name.'_countdown_timer'])) {
			$this->data[$this->payment_module_name.'_countdown_timer'] = $this->request->post[$this->payment_module_name.'_countdown_timer'];
		} else {
			$this->data[$this->payment_module_name.'_countdown_timer'] = $this->config->get($this->payment_module_name.'_countdown_timer');
		}
		if (isset($this->request->post[$this->payment_module_name.'_order_status_id'])) {
			$this->data[$this->payment_module_name.'_order_status_id'] = $this->request->post[$this->payment_module_name.'_order_status_id'];
		} else {
			$this->data[$this->payment_module_name.'_order_status_id'] = $this->config->get($this->payment_module_name.'_order_status_id'); 
		} 
        if (isset($this->request->post[$this->payment_module_name.'_status'])) {
			$this->data[$this->payment_module_name.'_status'] = $this->request->post[$this->payment_module_name.'_status'];
		} else {
			$this->data[$this->payment_module_name.'_status'] = $this->config->get($this->payment_module_name.'_status');
		}
        if (isset($this->request->post[$this->payment_module_name.'_server'])) {
            $this->data[$this->payment_module_name.'_server'] = $this->request->post[$this->payment_module_name.'_server'];
        } else {
            $this->data[$this->payment_module_name.'_server'] = $this->config->get($this->payment_module_name.'_server');
        }
		$this->template = 'payment/'.$this->payment_module_name.'.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		
		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
	}
	
}
