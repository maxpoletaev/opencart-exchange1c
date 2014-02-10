<?php

class ModelToolExchange1c extends Model {

	private $CATEGORIES = array();
	private $PROPERTIES = array();


	/**
	 * Генерирует xml с заказами
	 *
	 * @param	int	статус выгружаемых заказов
	 * @param	int	новый статус заказов
	 * @param	bool	уведомлять пользователя
	 * @return	string
	 */
	public function queryOrders($params) {

		$this->load->model('sale/order');

		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status'] . "");
		} else {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}

		$document = array();
		$document_counter = 0;

		if ($query->num_rows) {

			foreach ($query->rows as $orders_data) {

				$order = $this->model_sale_order->getOrder($orders_data['order_id']);

				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));

				$document['Документ' . $document_counter] = array(
					 'Ид'          => $order['order_id']
					,'Номер'       => $order['order_id']
					,'Дата'        => $date
					,'Время'       => $time
					,'Валюта'      => $params['currency']
					,'Курс'        => 1
					,'ХозОперация' => 'Заказ товара'
					,'Роль'        => 'Продавец'
					,'Сумма'       => $order['total']
					,'Комментарий' => $order['comment']
				);

				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
					 'Ид'                 => $order['customer_id'] . '#' . $order['email']
					,'Наименование'		    => $order['payment_lastname'] . ' ' . $order['payment_firstname']
					,'Роль'               => 'Покупатель'
					,'ПолноеНаименование'	=> $order['payment_lastname'] . ' ' . $order['payment_firstname']
					,'Фамилия'            => $order['payment_lastname']
					,'Имя'			          => $order['payment_firstname']
					,'Адрес' => array(
						'Представление'	=> $order['shipping_address_1'].', '.$order['shipping_city'].', '.$order['shipping_postcode'].', '.$order['shipping_country']
					)
					,'Контакты' => array(
						'Контакт1' => array(
							'Тип' => 'ТелефонРабочий'
							,'Значение'	=> $order['telephone']
						)
						,'Контакт2'	=> array(
							 'Тип' => 'Почта'
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
						 'Ид'             => $id
						,'Наименование'   => $product['name']
						,'ЦенаЗаЕдиницу'  => $product['price']
						,'Количество'     => $product['quantity']
						,'Сумма'          => $product['total']
					);
					
					if ($this->config->get('exchange1c_relatedoptions')) {
						$this->load->model('module/related_options');
						if ($this->model_module_related_options->get_product_related_options_use($product['product_id'])) {
							$order_options = $this->model_sale_order->getOrderOptions($orders_data['order_id'], $product['order_product_id']);
							$options = array();
							foreach ($order_options as $order_option) {
								$options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
							}
							if (count($options) > 0) {
								$ro = $this->model_module_related_options->get_related_options_set_by_poids($product['product_id'], $options);
								if ($ro != FALSE) {
									$char_id = $this->model_module_related_options->get_char_id($ro['relatedoptions_id']);
									if ($char_id != FALSE) {
										$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter]['Ид'] .= "#".$char_id;
									}
								}
							}
							
						}
						
					}

					$product_counter++;
				}

				$data = $order;

				$this->model_sale_order->addOrderHistory($orders_data['order_id'], array(
					'order_status_id' => $params['new_status'],
					'comment'         => '',
					'notify'          => $params['notify']
				));

				$document_counter++;
			}
		}

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

	function format($var){
		return preg_replace_callback(
		    '/\\\u([0-9a-fA-F]{4})/',
		    create_function('$match', 'return mb_convert_encoding("&#" . intval($match[1], 16) . ";", "UTF-8", "HTML-ENTITIES");'),
		    json_encode($var)
		);
	}

	/**
	 * Парсит цены и количество
	 *
	 * @param    string    наименование типа цены
	 */
	public function parseOffers($filename, $config_price_type, $language_id) {

		$importFile = DIR_CACHE . 'exchange1c/' . $filename;
		$xml = simplexml_load_file($importFile);
		
		$price_types = array();
		
		$enable_log = $this->config->get('exchange1c_full_log');
		$exchange1c_relatedoptions = $this->config->get('exchange1c_relatedoptions');

		$this->load->model('catalog/option');

		if ($enable_log)
			$this->log->write("Начат разбор файла: " . $filename);

		if ($xml->ПакетПредложений->ТипыЦен->ТипЦены) {
			foreach ($xml->ПакетПредложений->ТипыЦен->ТипЦены as $type) {
				$price_types[(string)$type->Ид] = (string)$type->Наименование;
			}
		}

		if (!empty($config_price_type) && count($config_price_type) > 0) {
			$config_price_type_main = array_shift($config_price_type);
		}

		// Инициализация массива скидок для оптимизации алгоритма
		if (!empty($config_price_type) && count($config_price_type) > 0) {
			$discount_price_type = array();
			foreach ($config_price_type as $obj) {
				$discount_price_type[$obj['keyword']] = array(
					'customer_group_id' => $obj['customer_group_id'],
					'quantity' => $obj['quantity'],
					'priority' => $obj['priority']
				); 
			}
		}
		
		$offer_cnt = 0;

		if ($xml->ПакетПредложений->Предложения->Предложение) {
			foreach ($xml->ПакетПредложений->Предложения->Предложение as $offer) {

				$new_product = (!isset($data));
				
				$offer_cnt++;
				
				if (!$exchange1c_relatedoptions || $new_product) {
					
					$data = array();
					$data['price'] = 0;
					
					//UUID без номера после #
					$uuid = explode("#", $offer->Ид);
					$data['1c_id'] = $uuid[0];
					if ($enable_log)
						$this->log->write("Товар: [UUID]:" . $data['1c_id']);
	
					$product_id = $this->getProductIdBy1CProductId ($uuid[0]);
	
					//Цена за единицу
					if ($offer->Цены) {
	
						// Первая цена по умолчанию - $config_price_type_main
						if (!$config_price_type_main['keyword']) {
							$data['price'] = (float)$offer->Цены->Цена->ЦенаЗаЕдиницу;
						}
						else {
							if ($offer->Цены->Цена->ИдТипаЦены) {
								foreach ($offer->Цены->Цена as $price) {
									if ($price_types[(string)$price->ИдТипаЦены] == $config_price_type_main['keyword']) {
										$data['price'] = (float)$price->ЦенаЗаЕдиницу;
										if ($enable_log)
											$this->log->write(" найдена цена  > " . $data['price']);
	
									}
								}
							}
						}
	
						// Вторая цена и тд - $discount_price_type
						if (!empty($discount_price_type) && $offer->Цены->Цена->ИдТипаЦены) {
							foreach ($offer->Цены->Цена as $price) {
								$key = $price_types[(string)$price->ИдТипаЦены];
								if (isset($discount_price_type[$key])) {
									$value = array(
										'customer_group_id'	=> $discount_price_type[$key]['customer_group_id'],
										'quantity'      => $discount_price_type[$key]['quantity'],
										'priority'      => $discount_price_type[$key]['priority'],
										'price'         => (float)$price->ЦенаЗаЕдиницу,
										'date_start'    => '0000-00-00',
										'date_end'      => '0000-00-00'
									);
									$data['product_discount'][] = $value;
									unset($value);
								}
							}
						}
					}
	
					//Количество
					$data['quantity'] = isset($offer->Количество) ? (int)$offer->Количество : 0;
				}

				//Характеристики
				if ($offer->ХарактеристикиТовара->ХарактеристикаТовара) {
					
					$product_option_value_data = array();
					$product_option_data = array();
					
					$lang_id = (int)$this->config->get('config_language_id');
					$count = count($offer->ХарактеристикиТовара->ХарактеристикаТовара);
	
					foreach ($offer->ХарактеристикиТовара->ХарактеристикаТовара as $i => $opt) {
						$name_1c = (string)$opt->Наименование;
						$value_1c = (string)$opt->Значение;
	
						if (!empty($name_1c) && !empty($value_1c)) {
							
							if ($exchange1c_relatedoptions) {
								$uuid = explode("#", $offer->Ид);
								if (!isset($char_id) || $char_id != $uuid[1]) {
									$char_id = $uuid[1];
									if ($enable_log) $this->log->write("Характеристика: ".$char_id);
								}
							}
							
							if ($enable_log) $this->log->write(" Найдены характеристики: " . $name_1c . " -> " . $value_1c);
	
							$option_id = $this->setOption($name_1c);
							
							$option_value_id = $this->setOptionValue($option_id, $value_1c);
							
							$product_option_value_data[] = array(
								'option_value_id'         => (int) $option_value_id,
								'product_option_value_id' => '',
								'quantity'                => isset($data['quantity']) ? (int)$data['quantity'] : 0,
								'subtract'                => 0,
								'price'                   => isset($data['price']) ? (int)$data['price'] : 0,
								'price_prefix'            => '+',
								'points'                  => 0,
								'points_prefix'           => '+',
								'weight'                  => 0,
								'weight_prefix'           => '+'
							);
	
							$product_option_data[] = array(
								'product_option_id'    => '',
								'name'                 => (string)$name_1c,
								'option_id'            => (int) $option_id,
								'type'                 => 'select',
								'required'             => 1,
								'product_option_value' => $product_option_value_data
							);
							
							if ($exchange1c_relatedoptions) {
								
								if ( !isset($data['relatedoptions'])) {
									$data['relatedoptions'] = array();
									$data['related_options_variant_search'] = TRUE;
									$data['related_options_use'] = TRUE;
								}
								
								$ro_found = FALSE;
								foreach ($data['relatedoptions'] as $ro_num => $relatedoptions) {
									if ($relatedoptions['char_id'] == $char_id) {
										$data['relatedoptions'][$ro_num]['options'][$option_id] = $option_value_id;
										$ro_found = TRUE;
										break;
									}
								}
								if (!$ro_found) {
									$data['relatedoptions'][] = array('char_id' => $char_id, 'quantity' => (isset($offer->Количество) ? (int)$offer->Количество : 0), 'options' => array($option_id => $option_value_id));
								}

							} else { 
								$data['product_option'] = $product_option_data;
							}
						}
					}
				}

				if (!$exchange1c_relatedoptions || $new_product) {
					
					if ($offer->СкидкиНаценки) {
						$value = array();
						foreach ($offer->СкидкиНаценки->СкидкаНаценка as $discount) {
							$value = array(
								 'customer_group_id'	=> 1
								,'priority'     => isset($discount->Приоритет) ? (int)$discount->Приоритет : 0
								,'price'        => (int)(($data['price'] * (100 - (float)str_replace(',', '.', (string)$discount->Процент))) / 100)
								,'date_start'   => isset($discount->ДатаНачала) ? (string)$discount->ДатаНачала : ''
								,'date_end'     => isset($discount->ДатаОкончания) ? (string)$discount->ДатаОкончания : ''
								,'quantity'     => 0
							);
	
							$data['product_discount'][] = $value;
	
							if ($discount->ЗначениеУсловия) {
								$value['quantity'] = (int)$discount->ЗначениеУсловия;
							}
	
							unset($value);
						}
					}
	
					$data['status'] = 1;
				}
				
				if (!$exchange1c_relatedoptions || $offer_cnt == count($xml->ПакетПредложений->Предложения->Предложение)
					|| $data['1c_id'] != substr($xml->ПакетПредложений->Предложения->Предложение[$offer_cnt]->Ид, 0, strlen($data['1c_id'])) ) {
						
						$this->updateProduct($data, $product_id, $language_id);
						unset($data);
				}
				

				
			}
		}

		$this->cache->delete('product');

		if ($enable_log)
			$this->log->write("Окончен разбор файла: " . $filename );

	}
	
	private function setOption($name){
		$lang_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT option_id FROM ". DB_PREFIX ."option_description WHERE name='". $this->db->escape($name) ."'");

		if ($query->num_rows > 0) {
			$option_id = $query->row['option_id'];
		}
        else {
			//Нет такой опции
			$this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET type = 'select', sort_order = '0'");
			$option_id = $this->db->getLastId();
			$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . $option_id . "', language_id = '" . $lang_id . "', name = '" . $this->db->escape($name) . "'");
		}
		return $option_id;
	}

	private function setOptionValue($option_id, $value) {
		$lang_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT option_value_id FROM ". DB_PREFIX ."option_value_description WHERE name='". $this->db->escape($value) ."' AND option_id='". $option_id ."'");

		if ($query->num_rows > 0) {
			$option_value_id = $query->row['option_value_id'];
		}
		else {
			//Добавляем значение опции, только если нет в базе
			$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . $option_id . "', image = '', sort_order = '0'");
			$option_value_id = $this->db->getLastId();
			$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '".$option_value_id."', language_id = '" . $lang_id . "', option_id = '" . $option_id . "', name = '" . $this->db->escape($value) . "'");
		}
		return $option_value_id;
	}

	/**
	 * Парсит товары и категории
	 */
	public function parseImport($filename, $language_id) {

		$importFile = DIR_CACHE . 'exchange1c/' . $filename;

		$enable_log = $this->config->get('exchange1c_full_log');
		$apply_watermark = $this->config->get('exchange1c_apply_watermark');

		$xml = simplexml_load_file($importFile);
		$data = array();

		// Группы
		if($xml->Классификатор->Группы) $this->insertCategory($xml->Классификатор->Группы->Группа, 0, $language_id);

		// Свойства
		if ($xml->Классификатор->Свойства) $this->insertAttribute($xml->Классификатор->Свойства->Свойство);

		$this->load->model('catalog/manufacturer');

		// Товары
		if ($xml->Каталог->Товары->Товар) {
			foreach ($xml->Каталог->Товары->Товар as $product) {

				$uuid = explode('#', (string)$product->Ид);
				$data['1c_id'] = $uuid[0];

				$data['model'] = $product->Артикул? (string)$product->Артикул : 'не задана';
				$data['name'] = $product->Наименование? (string)$product->Наименование : 'не задано';
				$data['weight'] = $product->Вес? (float)$product->Вес : 0;
				$data['sku'] = $product->Артикул? (string)$product->Артикул : '';

				if ($enable_log)
					$this->log->write("Найден товар:" . $data['name'] . " арт: " . $data['sku'] . "1C UUID: " . $data['1c_id']);

				if ($product->Картинка) {
					$data['image'] = $apply_watermark ? $this->applyWatermark((string)$product->Картинка[0]) : (string)$product->Картинка[0];
					unset($product->Картинка[0]);
					foreach ($product->Картинка as $image) {
					  $data['product_image'][] =array(
							'image' => $apply_watermark ? $this->applyWatermark((string)$image) : (string)$image,
							'sort_order' => 0
						);
					}
				}

				if($product->ХарактеристикиТовара){

					$count_options = count($product->ХарактеристикиТовара->ХарактеристикаТовара);

					foreach($product->ХарактеристикиТовара->ХарактеристикаТовара as $option ) {
						$option_desc .= (string)$option->Наименование . ': ' . (string)$option->Значение . ';';
					}
					$option_desc .= ";\n";

				}

				if ($product->Группы) $data['category_1c_id'] = (string)$product->Группы->Ид;
				if ($product->Описание) $data['description'] = (string)$product->Описание;
				if ($product->Статус) $data['status'] = (string)$product->Статус;

				// Свойства продукта
				if ($product->ЗначенияСвойств) {
					if ($enable_log)
						$this->log->write("   загружаются свойства... ");
					foreach ($product->ЗначенияСвойств->ЗначенияСвойства as $property) {
						if (isset($this->PROPERTIES[(string)$property->Ид]['name'])) {

							$attribute = $this->PROPERTIES[(string)$property->Ид];

							if (isset($attribute['values'][(string)$property->Значение])) {
								$attribute_value = str_replace("'", "&apos;", (string)$attribute['values'][(string)$property->Значение]);
							}
							else if ((string)$property->Значение != '') {
								$attribute_value = str_replace("'", "&apos;", (string)$property->Значение);
							}
							else {
								continue;
							}
							if ($enable_log)
								$this->log->write("   > " . $attribute_value);

							switch ($attribute['name']) {

								case 'Производитель':
									$manufacturer_name = $attribute_value;
									$query = $this->db->query("SELECT manufacturer_id FROM ". DB_PREFIX ."manufacturer WHERE name='". $manufacturer_name ."'");

									if ($query->num_rows) {
										$data['manufacturer_id'] = $query->row['manufacturer_id'];
									}
									else {
										$data_manufacturer = array(
											'name' => $manufacturer_name,
											'keyword' => '',
											'sort_order' => 0,
											'manufacturer_store' => array(0 => 0)
										);

										$data_manufacturer['manufacturer_description'] = array(
											$language_id => array(
												'meta_keyword' => '',
												'meta_description' => '',
												'description' => '',
												'seo_title' => '',
												'seo_h1' => ''
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
										'attribute_id'			=> $attribute['id'],
										'product_attribute_description'	=> array(
											$language_id => array(
												'text' => $attribute_value
											)
										)
									);


							}
						}
					}
					if ($enable_log)
						$this->log->write("   свойства загружены... ");
				}

				// Реквизиты продукта
				if($product->ЗначенияРеквизитов) {
					foreach ($product->ЗначенияРеквизитов->ЗначениеРеквизита as $requisite){
						switch ($requisite->Наименование){
							case 'Вес':
								$data['weight'] = $requisite->Значение ? (float)$requisite->Значение : 0;
							break;

							case 'ОписаниеВФорматеHTML':
								$data['description'] = $requisite->Значение ? (string)$requisite->Значение : '';
							break;
						}
					}
				}

				$this->setProduct($data, $language_id);
				unset($data);
			}
		}

		unset($xml);
		if ($enable_log)
			$this->log->write("Окончен разбор файла: " . $filename );
	}


	/**
	 * Инициализируем данные для категории дабы обновлять данные, а не затирать
	 *
	 * @param	array	старые данные
	 * @param	int	id родительской категории
	 * @param	array	новые данные
	 * @return	array
	 */
	private function initCategory($category, $parent, $data = array(), $language_id) {

		$result = array(
			 'status'         => isset($data['status']) ? $data['status'] : 1
			,'top'            => isset($data['top']) ? $data['top'] : 1
			,'parent_id'      => $parent
			,'category_store' => isset($data['category_store']) ? $data['category_store'] : array(0)
			,'keyword'        => isset($data['keyword']) ? $data['keyword'] : ''
			,'image'          => (isset($category->Картинка)) ? (string)$category->Картинка : ((isset($data['image'])) ? $data['image'] : '')
			,'sort_order'     => (isset($category->Сортировка)) ? (int)$category->Сортировка : ((isset($data['sort_order'])) ? $data['sort_order'] : 0)
			,'column'         => 1
		);

		$result['category_description'] = array(
			$language_id => array(
				 'name'             => (string)$category->Наименование
				,'meta_keyword'     => (isset($data['category_description'][$language_id]['meta_keyword'])) ? $data['category_description'][$language_id]['meta_keyword'] : ''
				,'meta_description'	=> (isset($data['category_description'][$language_id]['meta_description'])) ? $data['category_description'][$language_id]['meta_description'] : ''
				,'description'		  => (isset($category->Описание)) ? (string)$category->Описание : ((isset($data['category_description'][$language_id]['description'])) ? $data['category_description'][$language_id]['description'] : '')
				,'seo_title'        => (isset($data['category_description'][$language_id]['seo_title'])) ? $data['category_description'][$language_id]['seo_title'] : ''
				,'seo_h1'           => (isset($data['category_description'][$language_id]['seo_h1'])) ? $data['category_description'][$language_id]['seo_h1'] : ''
			),
		);

		return $result;
	}


	/**
	 * Функция добавляет корневую категорию и всех детей
	 *
	 * @param	SimpleXMLElement
	 * @param	int
	 */
	private function insertCategory($xml, $parent = 0, $language_id) {

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
					$data = $this->initCategory($category, $parent, $data, $language_id);
					$this->model_catalog_category->editCategory($category_id, $data);
				}
				else {
					$data = $this->initCategory($category, $parent, array(), $language_id);
					//$category_id = $this->getCategoryIdByName($data['category_description'][1]['name']) ? $this->getCategoryIdByName($data['category_description'][1]['name']) : $this->model_catalog_category->addCategory($data);
					$category_id = $this->model_catalog_category->addCategory($data);
					$this->db->query('INSERT INTO `' . DB_PREFIX . 'category_to_1c` SET category_id = ' . (int)$category_id . ', `1c_category_id` = "' . $this->db->escape($id) . '"');
				}

				$this->CATEGORIES[$id] = $category_id;
			}

			if ($category->Группы) $this->insertCategory($category->Группы->Группа, $category_id, $language_id);
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
				'name' => 'Свойства'
			);

			$data = array (
				'sort_order'			=> 0,
				'attribute_group_description'	=> $attribute_group_description
			);

			$this->model_catalog_attribute_group->addAttributeGroup($data);
		}

		foreach ($xml as $attribute) {
			$id	= (string)$attribute->Ид;
			$name = (string)$attribute->Наименование;
			$values	= array();

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
				'attribute_group_id'    => 1,
				'sort_order'            => 0,
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
				'id'     => $attribute_id,
				'name'   => $name,
				'values' => $values
			);

		}

		unset($xml);
	}


	/**
	* Функция работы с продуктом
	* @param	int
	* @return	array
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

			$data = array_merge($data, array('main_category_id' => $this->model_catalog_product->getProductMainCategoryId($product_id)));
			$data = array_merge($data, array('product_discount' => $this->model_catalog_product->getProductDiscounts($product_id)));
			$data = array_merge($data, array('product_special' => $this->model_catalog_product->getProductSpecials($product_id)));
			$data = array_merge($data, array('product_download' => $this->model_catalog_product->getProductDownloads($product_id)));
			$data = array_merge($data, array('product_category' => $this->model_catalog_product->getProductCategories($product_id)));
			$data = array_merge($data, array('product_store' => $this->model_catalog_product->getProductStores($product_id)));
			$data = array_merge($data, array('product_related' => $this->model_catalog_product->getProductRelated($product_id)));
			$data = array_merge($data, array('product_attribute' => $this->model_catalog_product->getProductAttributes($product_id)));

			if (VERSION == '1.5.3.1') {
				$data = array_merge($data, array('product_tag' => $this->model_catalog_product->getProductTags($product_id)));
			}
		}

		$query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "product_id='.$product_id.'"');
		if ($query->num_rows) $data['keyword'] = $query->row['keyword'];

		return $data;
	}

	/**
	 * Обновляет массив с информацией о продукте
	 *
	 * @param	array	новые данные
	 * @param	array	обновляемые данные
	 * @return	array
	 */
	private function initProduct($product, $data = array(), $language_id) {

		$this->load->model('tool/image');

		$result = array(
			'product_description' => array()
			,'model'    => (isset($product['model'])) ? $product['model'] : (isset($data['model']) ? $data['model']: '')
			,'sku'      => (isset($product['sku'])) ? $product['sku'] : (isset($data['sku']) ? $data['sku']: '')
			,'upc'      => (isset($product['upc'])) ? $product['upc'] : (isset($data['upc']) ? $data['upc']: '')
			,'ean'      => (isset($product['ean'])) ? $product['ean'] : (isset($data['ean']) ? $data['ean']: '')
			,'jan'      => (isset($product['jan'])) ? $product['jan'] : (isset($data['jan']) ? $data['jan']: '')
			,'isbn'     => (isset($product['isbn'])) ? $product['isbn'] : (isset($data['isbn']) ? $data['isbn']: '')
			,'mpn'      => (isset($product['mpn'])) ? $product['mpn'] : (isset($data['mpn']) ? $data['mpn']: '')

			,'location'     => (isset($product['location'])) ? $product['location'] : (isset($data['location']) ? $data['location']: '')
			,'price'        => (isset($product['price'])) ? $product['price'] : (isset($data['price']) ? $data['price']: 0)
			,'tax_class_id' => (isset($product['tax_class_id'])) ? $product['tax_class_id'] : (isset($data['tax_class_id']) ? $data['tax_class_id']: 0)
			,'quantity'     => (isset($product['quantity'])) ? $product['quantity'] : (isset($data['quantity']) ? $data['quantity']: 0)
			,'minimum'      => (isset($product['minimum'])) ? $product['minimum'] : (isset($data['minimum']) ? $data['minimum']: 1)
			,'subtract'     => (isset($product['subtract'])) ? $product['subtract'] : (isset($data['subtract']) ? $data['subtract']: 1)
			,'stock_status_id'  => $this->config->get('config_stock_status_id')
			,'shipping'         => (isset($product['shipping'])) ? $product['shipping'] : (isset($data['shipping']) ? $data['shipping']: 1)
			,'keyword'          => (isset($product['keyword'])) ? $product['keyword'] : (isset($data['keyword']) ? $data['keyword']: '')
			,'image'            => (isset($product['image'])) ? $product['image'] : (isset($data['image']) ? $data['image']: '')
			,'date_available'   => date('Y-m-d', time() - 86400)
			,'length'           => (isset($product['length'])) ? $product['length'] : (isset($data['length']) ? $data['length']: '')
			,'width'            => (isset($product['width'])) ? $product['width'] : (isset($data['width']) ? $data['width']: '')
			,'height'           => (isset($product['height'])) ? $product['height'] : (isset($data['height']) ? $data['height']: '')
			,'length_class_id'  => (isset($product['length_class_id'])) ? $product['length_class_id'] : (isset($data['length_class_id']) ? $data['length_class_id']: 1)
			,'weight'           => (isset($product['weight'])) ? $product['weight'] : (isset($data['weight']) ? $data['weight']: 0)
			,'weight_class_id'  => (isset($product['weight_class_id'])) ? $product['weight_class_id'] : (isset($data['weight_class_id']) ? $data['weight_class_id']: 1)
			,'status'           => (isset($product['status'])) ? $product['status'] : (isset($data['status']) ? $data['status']: 1)
			,'sort_order'       => (isset($product['sort_order'])) ? $product['sort_order'] : (isset($data['sort_order']) ? $data['sort_order']: 1)
			,'manufacturer_id'  => (isset($product['manufacturer_id'])) ? $product['manufacturer_id'] : (isset($data['manufacturer_id']) ? $data['manufacturer_id']: 0)
			,'main_category_id' => 0
			,'product_store'    => array(0)
			,'product_option'   => array()
			,'points'           => (isset($product['points'])) ? $product['points'] : (isset($data['points']) ? $data['points']: 0)
			,'product_image'    => (isset($product['product_image'])) ? $product['product_image'] : (isset($data['product_image']) ? $data['product_image']: array())
			,'preview'          => $this->model_tool_image->resize('no_image.jpg', 100, 100)
			,'cost'             => (isset($product['cost'])) ? $product['cost'] : (isset($data['cost']) ? $data['cost']: 0)
			,'product_discount' => (isset($product['product_discount'])) ? $product['product_discount'] : (isset($data['product_discount']) ? $data['product_discount']: array())
			,'product_special'  => (isset($product['product_special'])) ? $product['product_special'] : (isset($data['product_special']) ? $data['product_special']: array())
			,'product_download' => (isset($product['product_download'])) ? $product['product_download'] : (isset($data['product_download']) ? $data['product_download']: array())
			,'product_related'  => (isset($product['product_related'])) ? $product['product_related'] : (isset($data['product_related']) ? $data['product_related']: array())
			,'product_attribute'    => (isset($product['product_attribute'])) ? $product['product_attribute'] : (isset($data['product_attribute']) ? $data['product_attribute']: array())
		);

		if (VERSION == '1.5.3.1') {
			$result['product_tag'] = (isset($product['product_tag'])) ? $product['product_tag'] : (isset($data['product_tag']) ? $data['product_tag']: array());
		}

		$result['product_description'] = array(
			$language_id => array(
				'name'              => isset($product['name']) ? $product['name'] : (isset($data['product_description'][$language_id]['name']) ? $data['product_description'][$language_id]['name']: 'Имя не задано')
				,'seo_h1'           => isset($product['seo_h1']) ? $product['seo_h1']: (isset($data['product_description'][$language_id]['seo_h1']) ? $data['product_description'][$language_id]['seo_h1']: '')
				,'seo_title'        => isset($product['seo_title']) ? $product['seo_title']: (isset($data['product_description'][$language_id]['seo_title']) ? $data['product_description'][$language_id]['seo_title']: '')
				,'meta_keyword'     => isset($product['meta_keyword']) ? trim($product['meta_keyword']): (isset($data['product_description'][$language_id]['meta_keyword']) ? $data['product_description'][$language_id]['meta_keyword']: '')
				,'meta_description' => isset($product['meta_description']) ? trim($product['meta_description']): (isset($data['product_description'][$language_id]['meta_description']) ? $data['product_description'][$language_id]['meta_description']: '')
				,'description'      => isset($product['description']) ? nl2br($product['description']): (isset($data['product_description'][$language_id]['description']) ? $data['product_description'][$language_id]['description']: '')
				,'tag'              => isset($product['tag']) ? $product['tag']: (isset($data['product_description'][$language_id]['tag']) ? $data['product_description'][$language_id]['tag']: '')
			),
		);

		if (isset($product['product_option'])) {
			$product['product_option_id'] = '';
			$product['name'] = '';
			if(!empty($product['product_option']) && isset($product['product_option'][0]['type'])){
				$result['product_option'] = $product['product_option'];
				if(!empty($data['product_option'])){
					$result['product_option'][0]['product_option_value'] = array_merge($product['product_option'][0]['product_option_value'],$data['product_option'][0]['product_option_value']);
				}
			}
			else {
				$result['product_option'] = $data['product_option'];
			}
		}
		else {
			$product['product_option'] = array();
		}

		if (isset($product['category_1c_id']) && isset($this->CATEGORIES[$product['category_1c_id']])) {
			$result['product_category'] = array((int)$this->CATEGORIES[$product['category_1c_id']]);
			$result['main_category_id'] = (int)$this->CATEGORIES[$product['category_1c_id']];
		}
		else {
			$result['product_category'] = isset($data['product_category']) ? $data['product_category']: array(0);
			$result['main_category_id'] = isset($data['main_category_id']) ? $data['main_category_id']: 0;
		}
		
		if (isset($product['related_options_use'])) {
			$result['related_options_use'] = $product['related_options_use'];
		}
		if (isset($product['related_options_variant_search'])) {
			$result['related_options_variant_search'] = $product['related_options_variant_search'];
		}
		if (isset($product['relatedoptions'])) {
			$result['relatedoptions'] = $product['relatedoptions'];
		}

		return $result;
	}



	/**
	 * Функция работы с продуктом
	 *
	 * @param array
	 */
	private function setProduct($product, $language_id) {

		if (!$product) return;

		// Проверяем, связан ли 1c_id с product_id
		$product_id = $this->getProductIdBy1CProductId($product['1c_id']);
		$data = $this->initProduct($product, array(), $language_id);

		if ($product_id) {
			$this->updateProduct($product, $product_id, $language_id);
		}
		else {
			// Проверяем, существует ли товар с тем-же артикулом
			// Если есть, то обновляем его
			$product_id = $this->getProductBySKU($data['sku']);
			if ($product_id !== false) {
				$this->updateProduct($product, $product_id, $language_id);
			}
			// Если нет, то создаем новый
			else {
				$this->load->model('catalog/product');
				$this->model_catalog_product->addProduct($data);
				$product_id = $this->getProductBySKU($data['sku']);
			}

			// Добавляем линк
			if ($product_id){
				$this->db->query('INSERT INTO `' .  DB_PREFIX . 'product_to_1c` SET product_id = ' . (int)$product_id . ', `1c_id` = "' . $this->db->escape($product['1c_id']) . '"');
			}
		}
	}


	/**
	 * Обновляет продукт
	 *
	 * @param array
	 * @param int
	 */
	private function updateProduct($product, $product_id = false, $language_id) {

		// Проверяем что обновлять?
		if ($this->config->get('exchange1c_relatedoptions')) {
			if ($product_id == false) {
				$this->setProduct($product, $language_id);
				return;
			}
		} else {
			if ($product_id !== false) {
				$product_id = $this->getProductIdBy1CProductId($product['1c_id']);
			}
		}

		// Обновляем описание продукта
		$product_old = $this->getProductWithAllData($product_id);

		// Работаем с ценой на разные варианты товаров.
		if(!empty($product['product_option'][0])){
			if(isset($product_old['price']) && (float) $product_old['price'] > 0){

				$price = (float) $product_old['price'] - (float) $product['product_option'][0]['product_option_value'][0]['price'];

				$product['product_option'][0]['product_option_value'][0]['price_prefix'] = ($price > 0) ? '-':'+';
				$product['product_option'][0]['product_option_value'][0]['price'] = abs($price);

				$product['price'] = (float) $product_old['price'];

			}
			else{
				$product['product_option'][0]['product_option_value'][0]['price'] = 0;
			}

		}

		$this->load->model('catalog/product');

		$product_old = $this->initProduct($product, $product_old, $language_id);

		//Редактируем продукт
		$product_id = $this->model_catalog_product->editProduct($product_id, $product_old);

	}

	/**
	 * Получает product_id по артикулу
	 *
	 * @param 	string
	 * @return 	int|bool
	 */
	private function getProductBySKU($sku) {

		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($sku) . "'");

        if ($query->num_rows) {
			return $query->row['product_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает 1c_id из product_id
	 *
	 * @param	int
	 * @return	string|bool
	 */
	private function get1CProductIdByProductId($product_id) {
		$query = $this->db->query('SELECT 1c_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `product_id` = ' . $product_id);

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
	 * @param	string
	 * @return	int|bool
	 */
	private function getProductIdBy1CProductId($product_id) {

		$query = $this->db->query('SELECT product_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `1c_id` = "' . $product_id . '"');

		if ($query->num_rows) {
			return $query->row['product_id'];
		}
		else {
			return false;
		}
	}

	private function getCategoryIdByName($name) {
		$query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "category_description` WHERE `name` = '" . $name . "'");
		if ($query->num_rows) {
			return $query->row['category_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 *
	 * @param	string
	 * @return	string
	 */
	private function applyWatermark($filename) {
		if (!empty($filename)) {
			$info = pathinfo($filename);
			$wmfile = DIR_IMAGE . $this->config->get('exchange1c_watermark');
			if (is_file($wmfile)) {
				$extension = $info['extension'];
				$minfo = getimagesize($wmfile);
				$image = new Image(DIR_IMAGE . $filename);
				$image->watermark($wmfile, 'center', $minfo['mime']);
				$new_image = utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '_watermark.' . $extension;
				$image->save(DIR_IMAGE . $new_image);
				return $new_image;
			}
			else {
				return $filename;
			}
		}
		else {
			return 'no_image.jpg';
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
					if ($row['category_id'] != $parent && $parent != 0) {
						$this->db->query('INSERT INTO `' .DB_PREFIX . 'product_to_category` SET `product_id` = ' . $row['product_id'] . ', `category_id` = ' . $parent . ', `main_category` = 0');
					}
				}
			}
		}
	}

	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {
		$query = $this->db->query('SELECT * FROM `'.DB_PREFIX.'category` WHERE `category_id` = "'.$category_id.'"');
		if (isset($query->row['parent_id'])) {
			$result = $this->findParentsCategories($query->row['parent_id']);
		}
		$result[] = $category_id;
		return $result;
	}

	/**
	 * Получает language_id из code (ru, en, etc)
	 * Как ни странно, подходящей функции в API не нашлось
	 *
	 * @param	string
	 * @return	int
	 */
	public function getLanguageId($lang) {
		$query = $this->db->query('SELECT `language_id` FROM `' . DB_PREFIX . 'language` WHERE `code` = "'.$lang.'"');
		return $query->row['language_id'];
	}


	/**
	 * Очищает таблицы магазина
	 */
	public function flushDb($params) {

		$enable_log = $this->config->get('exchange1c_full_log');
		// Удаляем товары
		if ($params['product']) {
			if ($enable_log)
				$this->log->write("Очистка таблиц товаров: ");
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_attribute`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_attribute`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_description`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_description`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_discount`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_discount`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_image`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_image`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_option`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option_value`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_option_value`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_related`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_related`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_reward`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_reward`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_special`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_special`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
				
			if ($this->config->get('exchange1c_relatedoptions'))	{
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_to_char`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_to_char`');
				
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions`');
				
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_option`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_option`');
				
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant`');
				
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_option`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_option`');
				
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_product`');
				if ($enable_log) $this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_product`');	
			}
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_category`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_category`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_download`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_download`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_layout`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_layout`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_store`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_store`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
			if ($enable_log)
				$this->log->write('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
		}

		// Очищает таблицы категорий
		if ($params['category']) {
			if ($enable_log)
				$this->log->write("Очистка таблиц категорий:");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
			if ($enable_log)
				$this->log->write('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		}

		// Очищает таблицы от всех производителей
		if ($params['manufacturer']) {
			if ($enable_log)
				$this->log->write("Очистка таблиц производителей:");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
			if ($enable_log)
				$this->log->write('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
		}

		// Очищает атрибуты
		if ($params['attribute']) {
			if ($enable_log)
				$this->log->write("Очистка таблиц атрибутов:");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'attribute');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_description');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_to_1c');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_to_1c');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group_description');
			if ($enable_log)
				$this->log->write('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group_description');
		}

		// Выставляем кол-во товаров в 0
		if($params['quantity']) {
			$this->db->query('UPDATE ' . DB_PREFIX . 'product ' . 'SET quantity = 0');
		}

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
