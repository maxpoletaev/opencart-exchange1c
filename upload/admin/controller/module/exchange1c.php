<?php
class ControllerModuleExchange1c extends Controller {
	private $error = array(); 

	public function index() {
	
		$this->load->language('module/exchange1c');

		//$this->document->title = $this->language->get('heading_title');
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('exchange1c', $this->request->post);		
					
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');
			
		$this->data['entry_username'] = $this->language->get('entry_username');
		$this->data['entry_password'] = $this->language->get('entry_password');
		$this->data['entry_flush_product'] = $this->language->get('entry_flush_product');
		$this->data['entry_flush_category'] = $this->language->get('entry_flush_category');
		$this->data['entry_flush_manufacturer'] = $this->language->get('entry_flush_manufacturer');
		$this->data['entry_flush_quantity'] = $this->language->get('entry_flush_quantity');
		$this->data['entry_lic_type'] = $this->language->get('entry_lic_type');
		$this->data['entry_version'] = 0;
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_tab_general'] = $this->language->get('text_tab_general');
		$this->data['text_tab_product'] = $this->language->get('text_tab_product');
		$this->data['text_tab_order'] = $this->language->get('text_tab_order');
		$this->data['text_empty'] = $this->language->get('text_empty');
		$this->data['text_homepage'] = $this->language->get('text_homepage');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_order_status'] = $this->language->get('entry_order_status');
		$this->data['entry_fill_parent_cats'] = $this->language->get('entry_fill_parent_cats');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
	
  		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

 		if (isset($this->error['exchange1c_username'])) {
			$this->data['error_exchange1c_username'] = $this->error['exchange1c_username'];
		} else {
			$this->data['error_exchange1c_username'] = '';
		}

 		if (isset($this->error['exchange1c_password'])) {
			$this->data['error_exchange1c_password'] = $this->error['exchange1c_password'];
		} else {
			$this->data['error_exchange1c_password'] = '';
		}
		
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

		
		//$this->data['action'] = HTTPS_SERVER . 'index.php?route=module/exchange1c&token=' . $this->session->data['token'];
		$this->data['action'] = $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL');

		//$this->data['cancel'] = HTTPS_SERVER . 'index.php?route=extension/exchange1c&token=' . $this->session->data['token'];
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		
		if (isset($this->request->post['exchange1c_username'])) {
			$this->data['exchange1c_username'] = $this->request->post['exchange1c_username'];
		} else {
			$this->data['exchange1c_username'] = $this->config->get('exchange1c_username');
		}
		
		if (isset($this->request->post['exchange1c_password'])) {
			$this->data['exchange1c_password'] = $this->request->post['exchange1c_password'];
		} else {
			$this->data['exchange1c_password'] = $this->config->get('exchange1c_password'); 
		} 
		
		if (isset($this->request->post['exchange1c_status'])) {
			$this->data['exchange1c_status'] = $this->request->post['exchange1c_status'];
		} else {
			$this->data['exchange1c_status'] = $this->config->get('exchange1c_status');
		}	
		
		if (isset($this->request->post['exchange1c_flush_product'])) {
			$this->data['exchange1c_flush_product'] = $this->request->post['exchange1c_flush_product'];
		} else {
			$this->data['exchange1c_flush_product'] = $this->config->get('exchange1c_flush_product');
		}

		if (isset($this->request->post['exchange1c_flush_category'])) {
			$this->data['exchange1c_flush_category'] = $this->request->post['exchange1c_flush_category'];
		} else {
			$this->data['exchange1c_flush_category'] = $this->config->get('exchange1c_flush_category');
		}

		if (isset($this->request->post['exchange1c_flush_manufacturer'])) {
			$this->data['exchange1c_flush_manufacturer'] = $this->request->post['exchange1c_flush_manufacturer'];
		} else {
			$this->data['exchange1c_flush_manufacturer'] = $this->config->get('exchange1c_flush_manufacturer');
		}
        
		if (isset($this->request->post['exchange1c_flush_quantity'])) {
			$this->data['exchange1c_flush_quantity'] = $this->request->post['exchange1c_flush_quantity'];
		} else {
			$this->data['exchange1c_flush_quantity'] = $this->config->get('exchange1c_flush_quantity');
		}

		if (isset($this->request->post['exchange1c_order_status'])) {
			$this->data['exchange1c_order_status'] = $this->request->post['exchange1c_order_status'];
		} else {
			$this->data['exchange1c_order_status'] = $this->config->get('exchange1c_order_status');
		}

		$this->load->model('localisation/order_status');

		$order_statuses = $this->model_localisation_order_status->getOrderStatuses();

		foreach ($order_statuses as $order_status) {
			$this->data['order_statuses'][] = array(
				'order_status_id' => $order_status['order_status_id'],
				'name'			  => $order_status['name']
			);
		}
				
		$this->template = 'module/exchange1c.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		
		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'module/exchange1c')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
	
	public function install() {}
	
	public function uninstall() {}
	
	// --- 
	public function modeCheckauth() {
	
		//  Проверяем включен или нет модуль
		if( ! $this->config->get('exchange1c_status') ) {
			echo "failure\n";
			echo "1c module OFF";
			exit;
		}
		
		// Проверяем логин и пароль на доступ
		if(!isset($_SERVER['PHP_AUTH_USER']) OR ! isset($_SERVER['PHP_AUTH_PW'])) {
			echo "failure\n";
			echo "no login/password";
			exit;
		}
		

		// Авторизуем
		if(($this->config->get('exchange1c_username') != '') && ($_SERVER['PHP_AUTH_USER'] != $this->config->get('exchange1c_username'))) {
			echo "failure\n";
			echo "error login";
		}
		
		if(($this->config->get('exchange1c_password') != '') && ($_SERVER['PHP_AUTH_PW'] != $this->config->get('exchange1c_password'))) {
			echo "failure\n";
			echo "error password";
			exit;
		}
		
		echo "success\n";
		echo session_name()."\n";
		echo session_id() ."\n";
	}
	
	public function modeCatalogInit() {
		
		$this->load->model('tool/exchange1c');
		
		// чистим кеш, убиваем старые данные
		$this->cleanCacheDir();
		
		// Проверяем естль ли БД для хранения промежуточных данных.
		$this->model_tool_exchange1c->checkDbSheme();
		
		// Удаляем товары
		if($this->config->get('exchange1c_flush_product')) {
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_attribute');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_discount');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_image');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_option');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_option_value');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_related');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_reward');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_special');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_1c');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_category');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_download');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_layout');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_store');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
		}

		// Очищает таблицы категорий
		if($this->config->get('exchange1c_flush_category')) {
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		}
			
		// Очищает таблицы от всех производителей
		if($this->config->get('exchange1c_flush_manufacturer')) {
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
			//$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
		}
			
		// Очищает атрибуты
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_description');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_to_1c');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group_description');

        
		// Выставляем кол-во товаров в 0
        if($this->config->get('exchange1c_flush_quantity')) {
        	$this->db->query('UPDATE ' . DB_PREFIX . 'product ' . 'SET quantity = 0');
        }

		$limit = 100000 * 1024;
	
		echo "zip=no\n";
		echo "file_limit=".$limit."\n";
	
	}

	public function modeSaleInit() {
		$limit = 100000 * 1024;
	
		echo "zip=no\n";
		echo "file_limit=".$limit."\n";
	}
	
	public function modeFile() {
	
		$cache = DIR_CACHE . 'exchange1c/';
		
		// Проверяем на наличие имени файла
		if( isset($this->request->get['filename']) ) {
			$uplod_file = $cache . $this->request->get['filename'];
		} else {
			echo "failure\n";
			echo "ERROR 10: No file name variable";
			return;
		}
		
		// Проверяем XML или изображения
		if( strpos( $this->request->get['filename'], 'import_files') !== false ) {
			$cache = DIR_IMAGE;
			$uplod_file = $cache . $this->request->get['filename'];
			$this->checkUploadFileTree( dirname($this->request->get['filename']) , $cache);
			
			// TODO: физическое обновление изображений. 
			
		}
				
		// Получаем данные
		$DATA = file_get_contents("php://input");
		
		if ($DATA !== false) {
			if ($fp = fopen($uplod_file, "wb")) {
				$result = fwrite($fp, $DATA);
				
				if ($result === strlen($DATA)) {
					echo "success\n";
					
					chmod($uplod_file , 0777);
					//echo "success\n";
				} else {
					echo "failure\n";
				}
			} else {
				echo "failure\n";
				echo "Can not open file: $uplod_file\n";
				echo $cache;
			}
		} else {
			echo "failure\n";
			echo "No data file\n";
		}

	
	}
	
	public function modeImport() {
		
		$cache = DIR_CACHE . 'exchange1c/';
	
		// Проверяем на наличие имени файла
		if( isset($this->request->get['filename'])) {
			$importFile = $cache . $this->request->get['filename'];
		} else {
			echo "failure\n";
			echo "ERROR 10: No file name variable" . $this->request->get['filename'];
			return 0;
		}
		
		$this->load->model('tool/exchange1c');
		
		if($this->request->get['filename'] == 'import.xml') {
			
			$this->model_tool_exchange1c->parseImport();
			echo "success\n";
			
		} elseif($this->request->get['filename'] == 'offers.xml') {
			
			$this->model_tool_exchange1c->parseOffers();
			echo "success\n";
			
		} else {
		
			echo "failure\n";
			echo $this->request->get['filename'];
			
		}
		
		$this->cache->delete('product');
		
		return;
	}

	public function modeQueryOrders() {

		$this->load->model('tool/exchange1c');
		$orders = $this->model_tool_exchange1c->queryOrders($this->config->get('config_order_status_id'), $this->config->get('exchange1c_order_status'));
		echo iconv('utf-8', 'cp1251', $orders);

		return;
	}
	
	
	// -- Системные процедуры
	private function cleanCacheDir() {
	
		// Проверяем есть ли директория
		if( file_exists(DIR_CACHE . 'exchange1c')) {
			if(is_dir(DIR_CACHE . 'exchange1c')) { return $this->cleanDir(DIR_CACHE . 'exchange1c/'); }
			else { unlink(DIR_CACHE . 'exchange1c'); }
		}
		
		mkdir(DIR_CACHE . 'exchange1c'); 
		
		return 0;
	}
	
	private function checkUploadFileTree($path, $curDir = null) {
		
		if(!$curDir) $curDir = DIR_CACHE . 'exchange1c/';
		
		foreach( explode('/', $path) as $name) {
			
			if( ! $name ) continue;
			
			if(file_exists( $curDir . $name ) ) {
				// Есть такое поделие
				if(is_dir( $curDir . $name ) ) {
					$curDir = $curDir . $name . '/';
					continue;
				}
				
				unlink($curDir . $name);				
			} 
			
			mkdir($curDir . $name );
			
			$curDir = $curDir . $name . '/';
		}
		
	}
	
	
	private function cleanDir($root, $self = false) {
	
		$dir = dir($root);
		
		while( $file = $dir->read() ) {
			
			if($file == '.' OR $file == '..') continue;
			
			if( file_exists($root . $file)) {
				
				if(is_file($root . $file)) { unlink($root . $file); continue; }
				
				if(is_dir($root . $file)) { $this->cleanDir($root . $file . '/', true); continue; }
				
				var_dump($file);	
			} 
			
			var_dump($file);
		}
		
		if($self) {
			
			if(file_exists($root) AND is_dir($root)) { rmdir($root); return 0; }
			
			var_dump($root);
		}
		
		return 0;
	}
	
	
	
	
	
	
}
?>
