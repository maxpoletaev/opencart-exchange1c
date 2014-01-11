<?php namespace Exchange1C\Import;

use Exchange1C\OpenCart;
use Exchange1C\Scheme;
use Exchange1C\Log;
use StdClass;

class ProductImport extends BaseImport {

	/**
	 * OpenCart model for products.
	 *
	 * @var ModelCatalogProduct
	 */
	private $productModel;


	/**
	 * OpenCart model for Exchange1C module.
	 *
	 * @var ModelModuleExchange1C
	 */
	private $exchangeModel;


	/**
	 * 1C categories to OpenCart categories relations.
	 *
	 * @var array
	 */
	private $categoryMap = array();


	/**
	 * 1C products to OpenCart products relations.
	 *
	 * @var array
	 */
	private $productMap = array();


	/**
	 * 1C price types cache.
	 *
	 * @var array
	 */
	private $priceTypes = array();


	/**
	 * Class constructor.
	 *
	 * @param Registry $registry
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->exchangeModel = OpenCart::loadModel('module/exchange1c');
		$this->productModel = OpenCart::loadModel('catalog/product');

		$this->categoryMap = $this->exchangeModel->getCategoriesMaps();
		$this->productMap = $this->exchangeModel->getProductsMaps();
	}


	/**
	 * Class destuctor.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->exchangeModel->setProductsMaps($this->productMap);
	}


	/**
	 * Import.xml entry point.
	 * 
	 * @param string $importFile
	 * @return void
	 */
	public function parseImport($importFile)
	{
		if (file_exists($importFile))
		{
			$importXml = simplexml_load_file($importFile);
			
			$this->processProducts($importXml->Каталог->Товары);
		}
	}


	/**
	 * Offers.xml entry point.
	 * 
	 * @param string $importFile
	 * @return void
	 */
	public function parseOffers($offersFile)
	{
		if (file_exists($offersFile))
		{
			$offersXml = simplexml_load_file($offersFile);
			
			$this->processPriceTypes($offersXml->ПакетПредложений->ТипыЦен);
			
			$this->processPrices($offersXml->ПакетПредложений->Предложения);
		}
	}


	/**
	 * Products parser.
	 *
	 * @param SimpeXMLElement $xmlProducts
	 * @return void
	 */
	private function processProducts($xmlProducts)
	{
		foreach ($xmlProducts->Товар as $xmlProduct)
		{
			$product1cId = (string)$xmlProduct->Ид;
			
			$category1cId = (string)$xmlProduct->Группы[0]->Ид;
			
			$mainCategoryId = isset($this->categoryMap[$category1cId])?
				$this->categoryMap[$category1cId] : 0
			;

			if (isset($this->productMap[$product1cId]))
			{
				$productId = $this->productMap[$product1cId];
				$productOldData = $this->getProductOldData($productId);

				$productNewData = $this->getProductNewData($xmlProduct, $productOldData);
				// $productNewData['main_category_id'] = $mainCategoryId;

				$this->editProduct($productId, $productNewData);
			}
			else
			{
				$productNewData = $this->getProductNewData($xmlProduct);

				foreach ($xmlProduct->Группы->Ид as $category1cId)
				{
					if ((string)$category1cId != $mainCategoryId)
					{
						if (isset($this->categoryMap[(string)$category1cId]))
						{
							$categoryId = $this->categoryMap[(string)$category1cId];
							$productNewData['product_category'][] = $categoryId;
						}
					}
				}

				$this->addProduct($product1cId, $productNewData);
			}
		}
	}


	/**
	 * Price types parser.
	 *
	 * @param SimpleXMLElement $xmlPriceTypes
	 * @return void
	 */
	private function processPriceTypes($xmlPriceTypes)
	{
		foreach ($xmlPriceTypes->ТипЦены as $priceType)
		{
			$priceTypeId = (string)$priceType->Ид;

			$this->priceTypes[$priceTypeId] = (string)$priceType->Наименование;
		}
	}


	/**
	 * Prices parser.
	 *
	 * @param SimpleXMLElement $xmlPrices
	 * @return void
	 */
	private function processPrices($xmlPrices)
	{
		foreach ($xmlPrices->Предложение as $offer)
		{
			$product1cId = (string)$offer->Ид;
		
			if (isset($this->productMap[$product1cId]))
			{
				$productId = $this->productMap[$product1cId];

				$productOldData = $this->getProductOldData($productId);

				$productData = new StdClass();

				$productData->quantity = (int)$offer->Количество;

				$configPriceType = OpenCart::config()->get('e1c_import_pricetype');

				if ( ! empty($configPriceType))
				{
					foreach ($offer->Цены->Цена as $price)
					{
						$priceTypeId = (string)$price->ИдТипаЦены;
						$pirceType = $this->priceTypes[$priceTypeId];

						if ($pirceType == $configPriceType)
						{
							$productData->price = str_replace(' ', '', (string)$price->ЦенаЗаЕдиницу);
							Log::debug("Found price: {$productData->price}.");
						}
						else
						{
							$productData->price = 0;
							Log::debug("Not found price, set to {$productData->price}.");
						}
					}
				}

				// @TODO: if "e1c_import_pricetype" is empty

				$productNewData = $this->getProductNewData($productData, $productOldData);
				$this->editProduct($productId, $productNewData);
			}
		}
	}


	/**
	 * Compare and build new data for product.
	 *
	 * @param SimpleXMLElement $xmlProduct
	 * @param array $oldData
	 * @return array
	 */
	private function getProductNewData($xmlProduct, $oldData = array())
	{
		$dataScheme = new Scheme('product');
		$descrScheme = new Scheme('product_description');

		$newData = $dataScheme->process($xmlProduct, $oldData);

		if (empty($oldData))
		{
			foreach ($this->languageIds as $languageId)
			{
				$newData['product_description'][$languageId] = $descrScheme->process($xmlProduct);
			}
		}
		else
		{
			foreach ($this->languageIds as $languageId)
			{
				$newData['product_description'][$languageId] = $descrScheme->processs($xmlProduct, $oldData['product_description'][$languageId]);
			}
		}

		return $newData;
	}


	/**
	 * Get all data of the product.
	 *
	 * @param int $productId
	 * @return array
	 */
	private function getProductOldData($productId)
	{
		$productData = $this->productModel->getProduct($productId);
		$productData['product_description'] = $this->productModel->getProductDescriptions($productId);
		$productData['product_attribute'] = $this->productModel->getProductAttributes($productId);
		$productData['product_discount'] = $this->productModel->getProductDiscounts($productId);
		$productData['product_filter'] = $this->productModel->getProductFilters($productId);
		$productData['product_image'] = $this->productModel->getProductImages($productId);
		$productData['product_option'] = $this->productModel->getProductOptions($productId);
		$productData['product_related'] = $this->productModel->getProductRelated($productId);
		$productData['product_reward'] = $this->productModel->getProductRewards($productId);
		$productData['product_special'] = $this->productModel->getProductSpecials($productId);
		$productData['product_category'] = $this->productModel->getProductCategories($productId);
		$productData['product_download'] = $this->productModel->getProductDownloads($productId);
		$productData['product_layout'] = $this->productModel->getProductLayouts($productId);
		$productData['product_store'] = $this->productModel->getProductStores($productId);

		// getProduct() not return the 'main_category_id', set manually
		if ( ! empty($productData['product_category']))
		{
			$productData['main_category_id'] = $productData['product_category'][0];
		}

		return $productData;
	}


	/**
	 * Create product and link.
	 * 
	 * @param string $product1cId
	 * @param array $productData
	 * @return void
	 */
	private function addProduct($product1cId, $productData)
	{
		$this->pluginManager->runPlugins('beforeAddProduct',
			array($product1cId, &$productData)
		);

		$productId = $this->productModel->addProduct($productData);
		$this->productMap[$product1cId] = $productId;

		$this->pluginManager->runPlugins('afterAddProduct',
			array($productId, $product1cId, $productData)
		);

		Log::debug("Add product: {$productId}");
	}


	/**
	 * Edit product.
	 *
	 * @param int $productId
	 * @param array $productData
	 */
	private function editProduct($productId, $productData)
	{
		$this->pluginManager->runPlugins('beforeEditProduct',
			array($productId, &$productData)
		);

		$this->productModel->editProduct($productId, $productData);

		$this->pluginManager->runPlugins('afterEditProduct',
			array($productId, $productData)
		);

		Log::debug("Edit product: {$productId}");
	}
}
