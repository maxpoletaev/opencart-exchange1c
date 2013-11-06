<?php namespace Exchange1C\Import;

use Exchange1C\OpenCart;
use Exchange1C\Scheme;
use Exchange1C\Log;

class CategoryImport extends BaseImport {

	/**
	 * OpenCart model for categories.
	 *
	 * @var ModelCatalogCategory
	 */
	private $categoryModel;

	
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
	 * Class constructor.
	 *
	 * @param Registry $registry
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->categoryModel = OpenCart::loadModel('catalog/category');
		$this->exchangeModel = OpenCart::loadModel('module/exchange1c');

		$this->categoryMap = $this->exchangeModel->getCategoriesMaps();
	}


	/**
	 * Class destuctor.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->exchangeModel->setCategoriesMaps($this->categoryMap);
	}


	/**
	 * Import.xml entry point.
	 * 
	 * @param string $importFile
	 * @return void
	 */
	public function parseImport($importFile = null)
	{
		if (file_exists($importFile))
		{
			$importXml = simplexml_load_file($importFile);
			
			if ($importXml->Классификатор->Группы)
			{
				$this->processCategories($importXml->Классификатор->Группы);
			}
		}
	}


	/**
	 * Categories parser.
	 *
	 * @param SimpleXMLElement $importXml
	 * @return void
	 */
	private function processCategories($xmlCategories, $parent = 0)
	{
		foreach ($xmlCategories->Группа as $xmlCategory)
		{
			$category1cId = (string)$xmlCategory->Ид;

			if (isset($this->categoryMap[$category1cId]))
			{
				$categoryId = $this->categoryMap[$category1cId];
				$categoryOldData = $this->getCategoryOldData($categoryId);

				$categoryNewData = $this->getCategoryNewData($xmlCategory, $categoryOldData);
				// $categoryNewData['parent_id'] = $parent;

				$this->editCategory($categoryId, $categoryNewData);
			}
			else
			{
				$categoryNewData = $this->getCategoryNewData($xmlCategory);
				$categoryNewData['parent_id'] = $parent;

				$categoryId = $this->addCategory($category1cId, $categoryNewData);
			}
	
			if ($xmlCategory->Группы) {
				$this->processCategories($xmlCategory->Группы, $categoryId);
			}
		}
	}

	
	/**
	 * Create category and link.
	 *
	 * @param string $category1cId
	 * @param array $categoryData
	 * @return int
	 */
	private function addCategory($category1cId, $categoryData)
	{
		$beforeEvent = $this->pluginManager->runPlugins('beforeAddCategory',
			array($category1cId, &$categoryData)
		);

		$categoryId = $this->categoryModel->addCategory($categoryData);
		$this->categoryMap[$category1cId] = $categoryId;

		$afterEvent = $this->pluginManager->runPlugins('afterAddCategory',
			array($categoryId, $category1cId, $categoryData)
		);

		Log::write("Add category: {$categoryId} ({$category1cId})");
		
		return $categoryId;
	}


	/**
	 * Edit category.
	 *
	 * @param int $categoryId
	 * @param array $categoryData
	 */
	private function editCategory($categoryId, $categoryData)
	{
		$beforeEvent = $this->pluginManager->runPlugins('beforeEditCategory',
			array($categoryId, &$categoryData)
		);

		$this->categoryModel->editCategory($categoryId, $categoryData);

		$afterEvent = $this->pluginManager->runPlugins('afterEditCategory',
			array($categoryId, $categoryData)
		);

		Log::write("Edit category: {$categoryId}");
	}


	/**
	 * Get all data of the category.
	 *
	 * @param int $categoryId
	 * @return array
	 */
	private function getCategoryOldData($categoryId)
	{
		$categoryData = $this->categoryModel->getCategory($categoryId);
		$categoryData['category_description'] = $this->categoryModel->getCategoryDescriptions($categoryId);

		return $categoryData;
	}


	/**
	 * Compare and build new data for category.
	 *
	 * @param SimpleXMLElement $xmlCategory
	 * @param array $oldData
	 * @return array
	 */
	private function getCategoryNewData($xmlCategory, $oldData = array())
	{
		$newData = Scheme::process('category', $xmlCategory, $oldData);

		if (empty($oldData))
		{
			foreach ($this->languageIds as $languageId)
			{
				$newData['category_description'][$languageId] = Scheme::process('category_description', $xmlCategory);
			}
		}
		else
		{
			foreach ($this->languageIds as $languageId)
			{
				$newData['category_description'][$languageId] = Scheme::process('category_description', $xmlCategory, $oldData['category_description'][$languageId]);
			}
		}

		return $newData;
	}
}