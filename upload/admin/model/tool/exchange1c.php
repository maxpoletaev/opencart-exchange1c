<?php
class ModelToolExchange1c extends Model {

	private $CATEGORIES = array();
	private $PROPERTIES = array();


	/**
	 * Генерирует xml с заказами
	 *
	 * @param 	int 	статус выгружаемых заказов
	 * @param 	int 	новый статус заказов
	 * @param 	bool 	уведомлять пользователя
	 * @return 	string
	 */
	public function queryOrders($params) {

		$this->load->model('sale/order');

		$orders = $this->model_sale_order->getOrders(array(
			'filter_order_status_id' => $params['query_status']
		));

		$document = array();
		$document_counter = 0;
		
		foreach ($orders as $orders_data) {
		
			$order = $this->model_sale_order->getOrder($orders_data['order_id']);
			
			$date = date('Y-m-d', strtotime($order['date_added']));
			$time = date('H:i:s', strtotime($order['date_added']));

			$document['Документ' . $document_counter] = array(
				 'Ид'			=> $order['order_id']
				,'Номер'		=> $order['order_id']
				,'Дата'			=> $date
				,'Время'		=> $time
				,'Валюта'		=> $params['currency']
				,'Курс'			=> 1
				,'ХозОперация'	=> 'Заказ товара'
				,'Роль'			=> 'Продавец'
				,'Сумма'		=> $order['total']
				,'Комментарий'	=> $order['comment']
			);

			$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
				 'Ид' 					=> $order['customer_id'] . '#' . $order['email']
				,'Наименование'			=> $order['payment_lastname'] . ' ' . $order['payment_firstname']
				,'Роль'					=> 'Покупатель'
				,'ПолноеНаименование'	=> $order['payment_lastname'] . ' ' . $order['payment_firstname']
				,'Фамилия'				=> $order['payment_lastname']
				,'Имя'					=> $order['payment_firstname']
				
				,'Адрес' 	=> array(
					'Представление'	=> $order['shipping_address_1'].', '.$order['shipping_city'].', '.$order['shipping_postcode'].', '.$order['shipping_country']
				)
				,'Контакты'	=> array(
					'Контакт1'	=> array(
						 'Тип'		=> 'ТелефонРабочий'
						,'Значение'	=> $order['telephone']
					)
					,'Контакт2'	=> array(
						 'Тип'		=> 'Почта'
						,'Значение'	=> $order['email']
					)
				)
			);

			// Товары
			$products = $this->model_sale_order->getOrderProducts($orders_data['order_id']);

			$product_counter = 0;
			foreach ($products as $product) {
				$id = $this->get1CProductIdByProductId($product['product_id']);

				$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
					 'Ид'			=> $id
					,'Наименование'	=> $product['name']
					,'ЦенаЗаЕдиницу'=> $product['price']
					,'Количество'	=> $product['quantity']
					,'Сумма'		=> $product['total']
				);

				$product_counter++;
			}

			$data = $order;

			$this->model_sale_order->addOrderHistory($orders_data['order_id'], array(
				'order_status_id'	=> $params['new_status'],
				'comment' 			=> '',
				'notify'			=> $params['notify']
			));

			$document_counter++;
		}

		/*
		$xml_values = $doc->addChild('ЗначенияРеквизитов');

		$xml_value = $doc->addChild('ЗначениеРеквизита');
		$xml_value->addChild('Наименование', 'Метод оплаты');
		$xml_value->addChild('Значение', 'Наличный расчет');

		$xml_value = $doc->addChild('ЗначениеРеквизита');
		$xml_value->addChild('Наименование', 'Метод оплаты');
		$xml_value->addChild('Значение', 'Наличный расчет');

		$xml_value = $doc->addChild('ЗначениеРеквизита');
		$xml_value->addChild('Наименование', 'Заказ оплачен');
		$xml_value->addChild('Значение', 'true');

		$xml_value = $doc->addChild('ЗначениеРеквизита');
		$xml_value->addChild('Наименование', 'Отменен');
		$xml_value->addChild('Значение', 'false');

		$xml_value = $doc->addChild('ЗначениеРеквизита');
		$xml_value->addChild('Наименование', 'Статус заказа');
		$xml_value->addChild('Значение', '[N] Принят');
		*/

		$root = '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d', time()) . '" />';
		$xml = $this->array_to_xml($document, new SimpleXMLElement($root));

		return $xml->asXML();
	}


	function array_to_xml($data, &$xml) {

		foreach($data as $key => $value) {
			if (is_array($value)) {
				if (!is_numeric($key)) {
					$subnode = $xml->addChild(preg_replace('/\d/', '', $key));
					$this->array_to_xml($value, $subnode);
				}
			}
			else {
				$xml->addChild($key, $value);
			}
		}

		return $xml;
	}

	/**
	 * Парсит цены и количество
	 *
	 * @param    string    наименование типа цены
	 */
	public function parseOffers($config_price_type = false) {

		$importFile = DIR_CACHE . 'exchange1c/offers.xml';
		$xml = simplexml_load_file($importFile);
		$data = array();
		$price_types = array();
		$data['price'] = 0;

		foreach ($xml->ПакетПредложений->ТипыЦен->ТипЦены as $type) {
			$price_types[(string)$type->Ид] = (string)$type->Наименование;
		}

		foreach ($xml->ПакетПредложений->Предложения->Предложение as $offer) {

			//UUID без номера после #
			$uuid = explode("#", $offer->Ид);
			$data['id'] = $uuid[0];

			//Цена за единицу
			if ($offer->Цены) {
				if (!$config_price_type) {
					$data['price'] = (float)$offer->Цены->Цена->ЦенаЗаЕдиницу;
				}
				else {
					foreach ($offer->Цены->Цена as $price) {
						if ($price_types[(string)$price->ИдТипаЦены] == $config_price_type) {
							$data['price'] = (float)$price->ЦенаЗаЕдиницу;
						}
					} 
				}
			}

			//Количество
			$data['quantity'] = $offer->Количество ? (int)$offer->Количество : 0 ;

			/*
			if($offer->ХарактеристикиТовара){
				//Заполняем массив с Атрибутами данными по умолчанию
				$product_option_value_description_data[1] = array('name' => '');
				$product_option_value_data[0] = array(
					'language'				=> $product_option_value_description_data,
					'quantity'				=> isset($data['quantity']) ? $data['quantity']:0,
					'subtract'				=> 1,
					// Пока записываем полную цену продукта с данной характеристикой, потом будем считать разницу цен.
					'price'				   => isset($data['price']) ? $data['price']:10 ,
					'prefix'				  => '+',
					'sort_order'			  => isset($offer->ХарактеристикиТовара->Сортировка) ? (int)$offer->ХарактеристикиТовара->Сортировка : 0
				);

				//Если характеристика одна, то незачем объединять их и потому название и значения запишем как надо

				$count_options = count($offer->ХарактеристикиТовара->ХарактеристикаТовара);

				$data['product_option'][0] = array(
					//Название Атрибута
					'language'			 => array( 1 => array( 'name' => ($count_options == 1 ) ? (string)$offer->ХарактеристикиТовара->ХарактеристикаТовара->Наименование : 'Варианты')),
					'product_option_value' => $product_option_value_data,
					'sort_order'		   => 0
				);

				//Считываем характеристики и объединяем, если их больше 1-й
				if($count_options == 1){
					$data['product_option'][0]['product_option_value'][0]['language'][1]['name'] = (string)$offer->ХарактеристикиТовара->ХарактеристикаТовара->Значение;
				}
				else {
					foreach($offer->ХарактеристикиТовара->ХарактеристикаТовара as $option ){
						$data['product_option'][0]['product_option_value'][0]['language'][1]['name'].= (string)$option->Наименование. ': '. (string)$option->Значение.' ';
				}}

				//Если 1С выгружает значение СортировкаХарактеристики, то считываем его, если нет, то топаем дальше и этот код никому не мешает.
				if($offer->ХарактеристикиТовара->СортировкаХарактеристики) $data['product_option'][0]['product_option_value'][0]['sort_order'] = (int)$offer->ХарактеристикиТовара->СортировкаХарактеристики;
			}
			*/

			if ($offer->СкидкиНаценки) {
				$value = array();
				foreach ($offer->СкидкиНаценки->СкидкаНаценка as $discount) {
					$value = array(
						 'customer_group_id'	=> 1
						,'priority'		=> (isset($discount->Приоритет)) ? (int)$discount->Приоритет : 0
						,'price'		=> (int)(($data['price']*(100-(float)str_replace(',','.',(string)$discount->Процент)))/100)
						,'date_start'	=> (isset($discount->ДатаНачала)) ? (string)$discount->ДатаНачала : '2011-01-01'
						,'date_end'		=> (string)$discount->ДатаОкончания
					);

					$data['product_discount'][] = $value;

					if ($discount->ЗначениеУсловия) {
						$value['quantity'] = (int)$discount->ЗначениеУсловия;
					}
					
					unset($value);
				}
			}
		   	
			$data['status'] = 1;
			$this->updateProduct($data);
			unset($data);
		}

		$this->cache->delete('product');

	}

	/**
	 * Парсит товары и категории
	 */
	public function parseImport() {		

		$importFile = DIR_CACHE . 'exchange1c/import.xml';

		$xml = simplexml_load_file($importFile);
		$data = array();
		
		// Группы
		if($xml->Классификатор->Группы) $this->insertCategory($xml->Классификатор->Группы->Группа);

		// Свойства
		if ($xml->Классификатор->Свойства) $this->insertAttribute($xml->Классификатор->Свойства->Свойство);

		$this->load->model('catalog/manufacturer');

		// Товары
		if ($xml->Каталог->Товары->Товар) {
			foreach ($xml->Каталог->Товары->Товар as $product) {

				$uuid = explode('#', (string)$product->Ид);
				$data['id'] = $uuid[0];
				$data['uuid'] = $uuid[0];

				$data['model'] = $product->Артикул?(string)$product->Артикул :'не задана';
				$data['sku'] = $data['model'];

				$data['name'] = $product->Наименование?(string)$product->Наименование:'не задано';
			
				if ($product->Картинка) {
					$data['image'] =(string)$product->Картинка[0];
					unset($product->Картинка[0]);
					foreach ($product->Картинка as $image) {
					  $data['product_image'][] =array(
							'image' => (string)$image,
							'sort_order' => 0
						);
					}
				}

				if($product->Группы) $data['category_1c_id'] = (string)$product->Группы->Ид;

				if($product->Описание) $data['description'] = (string)$product->Описание;

				if($product->Статус) $data['status'] = (string)$product->Статус;

				// Свойства продукта
				if ($product->ЗначенияСвойств) {
					foreach ($product->ЗначенияСвойств->ЗначенияСвойства as $property) {
						if (isset($this->PROPERTIES[(string)$property->Ид]['name'])) {

							$attribute = $this->PROPERTIES[(string)$property->Ид];

							if (isset($attribute['values'][(string)$property->Значение])) {
								$attribute_value = (string)$attribute['values'][(string)$property->Значение];
							}
							else if ((string)$property->Значение != '') {
								$attribute_value = (string)$property->Значение;
							}
							else {
								continue;
							}

							switch ($attribute['name']) {
			
								case 'Производитель':
									$manufacturer_name = $attribute_value;
									$query = $this->db->query("SELECT manufacturer_id FROM ". DB_PREFIX ."manufacturer WHERE name='". $manufacturer_name ."'");
									
									if ($query->num_rows) {
										$data['manufacturer_id'] = $query->row['manufacturer_id'];
									}
									else {
										$data_manufacturer = array(
											'name' 				 => $manufacturer_name,
											'keyword'			 => '',
											'sort_order' 		 => 0,
											'manufacturer_store' => array(0 => 0)
										);
										
										$data_manufacturer['manufacturer_description'] = array(
											1 => array(
												'meta_keyword' 		=> '',
												'meta_description' 	=> '',
												'description' 		=> '',
												'seo_title'			=> '',
												'seo_h1' 			=> ''
											),
										);

										$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($data_manufacturer);
										$data['manufacturer_id'] = $manufacturer_id;
									}
								break;
								
								case 'oc.seo_h1':
									$data['seo_h1'] = $attribute_value;
								break;
								
								case 'oc.seo_title':
									$data['seo_title'] = $attribute_value;
								break;
								
								case 'oc.sort_order':
									$data['sort_order'] = $attribute_value;
								break;
									
								default:
									$data['product_attribute'][] = array(
										'attribute_id' 					=> $attribute['id'],
										'product_attribute_description'	=> array(
											1 => array(
												'text' => $attribute_value
											)
										)
									);
									
										
							}
						}
					}
				}

				// Реквезиты продукта
				if($product->ЗначенияРеквизитов) {
					foreach ($product->ЗначенияРеквизитов->ЗначениеРеквизита as $requisite){
						switch ($requisite->Наименование){
							case 'Вес':
								$data['weight'] = $requisite->Значение ? (float)$requisite->Значение : 0;
							break;
						}
					}
				}

				$this->setProduct($data);
				unset($data);
			}
		}

		unset($xml);
	}


	/**
	 * Инициализируем данные для категории дабы обновлять данные, а не затирать
	 *
	 * @param 	array 	старые данные
	 * @param 	int 	id родительской категории
	 * @param 	array 	новые данные
	 * @return 	array
	 */
	private function initCategory($category, $parent, $data = array()){
		
		$result = array(
			 'status' 		=> isset($data['status']) ? $data['status'] : 1
			,'top'			=> isset($data['top']) ? $data['top'] : 1
			,'parent_id'	=> $parent
			,'category_store' => isset($data['category_store']) ? $data['category_store'] : array(0)
			,'keyword'		=> isset($data['keyword']) ? $data['keyword'] : ''
			,'image'		=> (isset($category->Картинка)) ? (string)$category->Картинка : ((isset($data['image'])) ? $data['image'] : '')
			,'sort_order'	=> (isset($category->Сортировка)) ? (int)$category->Сортировка : ((isset($data['sort_order'])) ? $data['sort_order'] : 0)
			,'column'		=> 1
		);

		$result['category_description'] = array(
			1 => array(
				 'name'			=> (string)$category->Наименование
				,'meta_keyword' => (isset($data['category_description'][1]['meta_keyword'])) ? $data['category_description'][1]['meta_keyword'] : ''
				,'meta_description' => (isset($data['category_description'][1]['meta_description'])) ? $data['category_description'][1]['meta_description'] : ''
				,'description'	=> (isset($category->Описание)) ? (string)$category->Описание : ((isset($data['category_description'][1]['description'])) ? $data['category_description'][1]['description'] : '')
				,'seo_title'	=> (isset($data['category_description'][1]['seo_title'])) ? $data['category_description'][1]['seo_title'] : ''
				,'seo_h1'		=> (isset($data['category_description'][1]['seo_h1'])) ? $data['category_description'][1]['seo_h1'] : ''
			),
		);

		return $result;
	}


	/**
	 * Функция добавляет корневую категорию и всех детей
	 *
	 * @param 	SimpleXMLElement
	 * @param 	int
	 */
	private function insertCategory($xml, $parent = 0) {

		$this->load->model('catalog/category');

		foreach ($xml as $category){

			if (isset($category->Ид) && isset($category->Наименование) ){ 
				$id =  (string)$category->Ид;

				$data = array();

				$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'category_to_1c` WHERE `1c_category_id` = "' . $this->db->escape($id) . '"');

				if ($query->num_rows) {
					$category_id = (int)$query->row['category_id'];
					$data = $this->model_catalog_category->getCategory($category_id);
					$data['category_description'] = $this->model_catalog_category->getCategoryDescriptions($category_id);
					$data = $this->initCategory($category, $parent, $data);
					$this->model_catalog_category->editCategory($category_id, $data);
				}
				else {
					$data = $this->initCategory($category, $parent);
					$category_id = $this->model_catalog_category->addCategory($data);
					$this->db->query('INSERT INTO `' . DB_PREFIX . 'category_to_1c` SET category_id = ' . (int)$category_id . ', `1c_category_id` = "' . $this->db->escape($id) . '"');
				}

				$this->CATEGORIES[$id] = $category_id;
			}

			if ($category->Группы) $this->insertCategory($category->Группы->Группа, $category_id);
		}

		unset($xml);
	}
	
	
	/**
	 * Создает атрибуты из свойств
	 *
	 * @param 	SimpleXMLElement
	 */
	private function insertAttribute($xml) {
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/attribute_group');


		$attribute_group = $this->model_catalog_attribute_group->getAttributeGroup(1);
		
		if (!$attribute_group) {

			$attribute_group_description[1] = array (
				'name'	=> 'Свойства'
			);

			$data = array (
				'sort_order' 		 => 0,
				'attribute_group_description' => $attribute_group_description
			);

			$this->model_catalog_attribute_group->addAttributeGroup($data);
		}
		
		foreach ($xml as $attribute) {
			$id 	= (string)$attribute->Ид;
			$name	= (string)$attribute->Наименование;
			$values = array();
			
			if ((string)$attribute->ВариантыЗначений) {
				if ((string)$attribute->ТипЗначений == 'Справочник') {
					foreach($attribute->ВариантыЗначений->Справочник as $option_value){
						if ((string)$option_value->Значение != '') {
							$values[(string)$option_value->ИдЗначения] = (string)$option_value->Значение;
						}
					}
				}
			}


			$data = array (
				'attribute_group_id'	=>	1,
				'sort_order'			=> 	0,
			);
			
			$data['attribute_description'][1]['name'] = (string)$name;
			
			// Если атрибут уже был добавлен, то возвращаем старый id, если атрибута нет, то создаем его и возвращаем его id
			$current_attribute = $this->db->query('SELECT attribute_id FROM ' . DB_PREFIX . 'attribute_to_1c WHERE 1c_attribute_id = "' . $id . '"');
			if (!$current_attribute->num_rows) {
				$attribute_id = $this->model_catalog_attribute->addAttribute($data);
				$this->db->query('INSERT INTO `' .  DB_PREFIX . 'attribute_to_1c` SET attribute_id = ' . (int)$attribute_id . ', `1c_attribute_id` = "' . $id . '"');
			}
			else {
				$data = $current_attribute->row;
				$attribute_id = $data['attribute_id'];
			}

			$this->PROPERTIES[$id] = array(
				'id'	 => $attribute_id,
				'name'   => $name,
				'values' => $values
			);
			
		}	
		
		unset($xml);
	}


	/**
	* Функция работы с продуктом
	* @param 	int
	* @return 	array
	*/

	private function getProductWithAllData($product_id) {
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		$data = array();

		if ($query->num_rows) {

			$data = $query->row;

			$data = array_merge($data, array('product_description' => $this->model_catalog_product->getProductDescriptions($product_id)));
			$data = array_merge($data, array('product_option' => $this->model_catalog_product->getProductOptions($product_id)));

			$data['product_image'] = array();

			$results = $this->model_catalog_product->getProductImages($product_id);

			foreach ($results as $result) {
				$data['product_image'][] = array(
					'image' => $result['image'],
					'sort_order' => $result['sort_order']
				);
			}

			$data = array_merge($data, array('product_discount' => $this->model_catalog_product->getProductDiscounts($product_id)));
			$data = array_merge($data, array('product_special' => $this->model_catalog_product->getProductSpecials($product_id)));
			$data = array_merge($data, array('product_download' => $this->model_catalog_product->getProductDownloads($product_id)));
			$data = array_merge($data, array('product_category' => $this->model_catalog_product->getProductCategories($product_id)));
			$data = array_merge($data, array('product_store' => $this->model_catalog_product->getProductStores($product_id)));
			$data = array_merge($data, array('product_related' => $this->model_catalog_product->getProductRelated($product_id)));
			$data = array_merge($data, array('product_attribute' => $this->model_catalog_product->getProductAttributes($product_id)));
			
			if (VERSION == '1.5.3.1') {
				$data = array_merge($data, array('product_attribute' => $this->model_catalog_product->getProductTags($product_id)));
			}
		}

		$query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "product_id='.$product_id.'"');
		if ($query->num_rows) $data['keyword'] = $query->row['keyword'];

		return $data;
	}

	/**
	 * Обновляет массив с информацией о продукте
	 *
	 * @param 	array 	новые данные
	 * @param 	array 	обновляемые данные
	 * @return 	array
	 */
	private function initProduct($product, $data = array()) {

		$this->load->model('tool/image');

		$result = array(
			 'model' => (isset($product['model'])) ? $product['model'] : (isset($data['model']) ? $data['model']: '')
			,'sku'	 => (isset($product['sku'])) ? $product['sku'] : (isset($data['sku']) ? $data['sku']: '')
			,'upc'	 => (isset($product['upc'])) ? $product['upc'] : (isset($data['upc']) ? $data['upc']: '')
			,'ean'	 => (isset($product['ean'])) ? $product['ean'] : (isset($data['ean']) ? $data['ean']: '')
			,'jan'	 => (isset($product['jan'])) ? $product['jan'] : (isset($data['jan']) ? $data['jan']: '')
			,'isbn'	 => (isset($product['isbn'])) ? $product['isbn'] : (isset($data['isbn']) ? $data['isbn']: '')
			,'mpn'   => (isset($product['mpn'])) ? $product['mpn'] : (isset($data['mpn']) ? $data['mpn']: '')

			,'points' 		 => (isset($product['points'])) ? $product['points'] : (isset($data['points']) ? $data['points']: 0)
			,'location'		 => (isset($product['location'])) ? $product['location'] : (isset($data['location']) ? $data['location']: '')
			,'product_store' => array(0)
			,'keyword'		 => (isset($product['keyword'])) ? $product['keyword'] : (isset($data['keyword']) ? $data['keyword']: '')
			,'image'		 => (isset($product['image'])) ? $product['image'] : (isset($data['image']) ? $data['image']: '')
			,'product_image' => (isset($product['product_image'])) ? $product['product_image'] : (isset($data['product_image']) ? $data['product_image']: array())
			,'preview'		 => $this->model_tool_image->resize('no_image.jpg', 100, 100)

			,'manufacturer_id'	=> (isset($product['manufacturer_id'])) ? $product['manufacturer_id'] : (isset($data['manufacturer_id']) ? $data['manufacturer_id']: 0)
			,'shipping'			=> (isset($product['shipping'])) ? $product['shipping'] : (isset($data['shipping']) ? $data['shipping']: 1)
			,'date_available'	=> date('Y-m-d', time() - 86400)
			,'quantity'			=> (isset($product['quantity'])) ? $product['quantity'] : (isset($data['quantity']) ? $data['quantity']: 0)
			,'minimum'			=> (isset($product['minimum'])) ? $product['minimum'] : (isset($data['minimum']) ? $data['minimum']: 1)
			,'subtract'			=> (isset($product['subtract'])) ? $product['subtract'] : (isset($data['subtract']) ? $data['subtract']: 1)
			,'sort_order'		=> (isset($product['sort_order'])) ? $product['sort_order'] : (isset($data['sort_order']) ? $data['sort_order']: 1)
			,'stock_status_id'	=> $this->config->get('config_stock_status_id')
			,'price'			=> (isset($product['price'])) ? $product['price'] : (isset($data['price']) ? $data['price']: 0)
			,'cost'				=> (isset($product['cost'])) ? $product['cost'] : (isset($data['cost']) ? $data['cost']: 0)
			,'status'			=> (isset($product['status'])) ? $product['status'] : (isset($data['status']) ? $data['status']: 1)
			,'tax_class_id'		=> (isset($product['tax_class_id'])) ? $product['tax_class_id'] : (isset($data['tax_class_id']) ? $data['tax_class_id']: 0)
			,'weight'			=> (isset($product['tax_class_id'])) ? $product['tax_class_id'] : (isset($data['tax_class_id']) ? $data['tax_class_id']: 0)
			,'weight_class_id'	=> (isset($product['weight_class_id'])) ? $product['weight_class_id'] : (isset($data['weight_class_id']) ? $data['weight_class_id']: 1)
			,'length'			=> (isset($product['length'])) ? $product['length'] : (isset($data['length']) ? $data['length']: '')
			,'width'			=> (isset($product['width'])) ? $product['width'] : (isset($data['width']) ? $data['width']: '')
			,'height'			=> (isset($product['height'])) ? $product['height'] : (isset($data['height']) ? $data['height']: '')
			,'length_class_id'	=> (isset($product['length_class_id'])) ? $product['length_class_id'] : (isset($data['length_class_id']) ? $data['length_class_id']: 1)

			,'product_discount'	=> (isset($product['product_discount'])) ? $product['product_discount'] : (isset($data['product_discount']) ? $data['product_discount']: array())
			,'product_special'	=> (isset($product['product_special'])) ? $product['product_special'] : (isset($data['product_special']) ? $data['product_special']: array())
			,'product_download'	=> (isset($product['product_download'])) ? $product['product_download'] : (isset($data['product_download']) ? $data['product_download']: array())
			,'product_related'	=> (isset($product['product_related'])) ? $product['product_related'] : (isset($data['product_related']) ? $data['product_related']: array())
			,'product_attribute' => (isset($product['product_attribute'])) ? $product['product_attribute'] : (isset($data['product_attribute']) ? $data['product_attribute']: array())
		);

		if (VERSION == '1.5.3.1') {
			$result['product_tag'] = (isset($product['product_tag'])) ? $product['product_tag'] : (isset($data['product_tag']) ? $data['product_tag']: array());
		}

		$result['product_description'] = array(
			1 => array(
				 'name' 			=> isset($product['name']) ? $product['name'] : (isset($data['product_description'][1]['name']) ? $data['product_description'][1]['name']: 'Имя не задано')
				,'meta_keyword'		=> isset($product['meta_keyword']) ? trim($product['meta_keyword']): (isset($data['product_description'][1]['meta_keyword']) ? $data['product_description'][1]['meta_keyword']: '')
				,'meta_description' => isset($product['meta_description']) ? trim($product['meta_description']): (isset($data['product_description'][1]['meta_description']) ? $data['product_description'][1]['meta_description']: '')
				,'description' 		=> isset($product['description']) ? nl2br($product['description']): (isset($data['product_description'][1]['description']) ? $data['product_description'][1]['description']: '')
				,'seo_title' 		=> isset($product['seo_title']) ? $product['seo_title']: (isset($data['product_description'][1]['seo_title']) ? $data['product_description'][1]['seo_title']: '')
				,'seo_h1' 			=> isset($product['seo_h1']) ? $product['seo_h1']: (isset($data['product_description'][1]['seo_h1']) ? $data['product_description'][1]['seo_h1']: '')
				,'tag' 				=> isset($product['tag']) ? $product['tag']: (isset($data['product_description'][1]['tag']) ? $data['product_description'][1]['tag']: '')
			),
		);

		if (isset($product['product_option'])) {
			if (!empty($result['product_option'])) {
				$result['product_option'][0]['product_option_value'][] = $product['product_option'][0]['product_option_value'][0];
			}
			else {
				$result['product_option'] = $product['product_option'];
			}
		}

		if (isset($product['category_1c_id']) && isset($this->CATEGORIES[$product['category_1c_id']])) {
			$result['product_category'] = array((int)$this->CATEGORIES[$product['category_1c_id']]);
			$result['main_category_id'] = (int)$this->CATEGORIES[$product['category_1c_id']];
		}
		else {
			$result['product_category'] = isset($data['product_category']) ? $data['product_category']: array(0);
			$result['main_category_id'] = isset($data['main_category_id']) ? $data['main_category_id']: 0;
		}
				
		return $result;
	}



	/**
	 *	Функция работы с продуктом
	 *
	 * 	@param array
	 */
	private function setProduct($product) {

		if (!$product) return;

		//Проверяем есть ли такой товар в БД
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_to_1c` WHERE `1c_id` = "' . $this->db->escape($product['id']) . '"');

		if ($query->num_rows) {
			$product['price'] = 0;
			// Удаляем атрибуты т.к. еще не придумал как их сравнивать и обновлять.
			//$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$query->row['product_id'] . "'");
			//$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_description WHERE product_id = '" . (int)$query->row['product_id'] . "'");
			//$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$query->row['product_id'] . "'");
			//$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_description WHERE product_id = '" . (int)$query->row['product_id'] . "'");
			return $this->updateProduct($product, (int)$query->row['product_id']);
		} 	

		// Заполняем значения продукта
		$data = $this->initProduct($product);

		$this->load->model('catalog/product');

		$product_id = $this->model_catalog_product->addProduct($data);

		// Добавляемя линкт в дб
		$this->db->query('INSERT INTO `' .  DB_PREFIX . 'product_to_1c` SET product_id = ' . (int)$product_id . ', `1c_id` = "' . $this->db->escape($product['uuid']) . '"');
	}


	/**
	 * Обновляет продукт
	 *
	 * @param array
	 * @param int
	 */
	private function updateProduct($product, $product_id = false) {

		$this->load->model('catalog/product');

		// Проверяем что обновлять?
		if (!$product_id) {
			$product_id = $this->getProductIdBy1CProductId($product['id']);
		}

		// Обновляем описание продукта
		$product_old = $this->getProductWithAllData($product_id);

		// Работаем с ценой на разные варианты товаров.
		if ((!empty($product['product_option'])) && ((float)$product_old['price'] != 0)) {

			$product['product_option'][0]['product_option_value'][0]['price'] = (float)$product['product_option'][0]['product_option_value'][0]['price'] - (float)$product_old['price'];
			$product['price'] = (float)$product_old['price'];
			$product['quantity'] = (int)$product['quantity'] + (int)$product_old['quantity'];

		}
		else if ((!empty($product['product_option'])) && ((float)$product_old['price'] == 0)){

			$product['product_option'][0]['product_option_value'][0]['price'] = 0;
		
		}
		
		$this->load->model('catalog/product');
		$product_old = $this->initProduct($product, $product_old);

		//Редактируем продукт
		$product_id = $this->model_catalog_product->editProduct($product_id, $product_old);

	}

	/**
	 * Получает 1c_id из product_id
	 *
	 * @param 	int
	 * @return 	string|bool
	 */
	private function get1CProductIdByProductId($product_id) {
		$sql = 'SELECT 1c_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `product_id` = ' . $product_id;
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			return $query->row['1c_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает product_id из 1c_id
	 *
	 * @param 	string
	 * @return 	int|bool
	 */
	private function getProductIdBy1CProductId($product_id) {
		$sql = 'SELECT product_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `1c_id` = "' . $product_id . '"';
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			return $query->row['product_id'];
		}
		else {
			return false;
		}
	}


	/**
	 * Заполняет продуктами родительские категории
	 */
	public function fillParentsCategories() {
		$this->db->query('DELETE FROM `' .DB_PREFIX . 'product_to_category` WHERE `main_category` = 0');
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_to_category` WHERE `main_category` = 1');
		
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$parents = $this->findParentsCategories($row['category_id']);
				foreach ($parents as $parent) {
					if ($row['category_id'] != $parent && $parent['parent_id'] != 0) {
						$this->db->query('INSERT INTO `' .DB_PREFIX . 'product_to_category` SET `product_id` = ' . $row['product_id'] . ', `category_id` = ' . $parent . ', `main_category` = 0');
					}
				}
			}
		}
	}

	/**
	 * Ищет все родительские категории
	 *
	 * @param 	int
	 * @return 	array
	 */
	private function findParentsCategories($category_id) {
		$query = $this->db->query('SELECT * FROM `'.DB_PREFIX.'category` WHERE `category_id` = "'.$category_id.'"');
		if ($query->row['parent_id']){
			$result = $this->findParentsCategories($query->row['parent_id']);
		}
		$result[] = $category_id;
		return $result;
	}


	/**
	 * Создает таблицы, нужные для работы
	 */
	public function checkDbSheme() {

		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'product_to_1c"');

		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'product_to_1c` (
							`product_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`product_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (product_id) REFERENCES '. DB_PREFIX .'product(product_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);			
		}


		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'category_to_1c"');

		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'category_to_1c` (
							`category_id` int(11) NOT NULL,
							`1c_category_id` varchar(255) NOT NULL,
							KEY (`category_id`),
							KEY `1c_id` (`1c_category_id`),
							FOREIGN KEY (category_id) REFERENCES '. DB_PREFIX .'category(category_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);			
		}

		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'attribute_to_1c"');
		
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'attribute_to_1c` (
							`attribute_id` int(11) NOT NULL,
							`1c_attribute_id` varchar(255) NOT NULL,
							KEY (`attribute_id`),
							KEY `1c_id` (`1c_attribute_id`),
							FOREIGN KEY (attribute_id) REFERENCES '. DB_PREFIX .'attribute(attribute_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);			
		}
	}

}
?>