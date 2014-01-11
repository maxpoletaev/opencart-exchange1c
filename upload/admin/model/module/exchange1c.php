<?php

class ModelModuleExchange1C extends Model {

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	protected $dbPrefix = DB_PREFIX;

	/**
	 * Create tables on install module.
	 *
	 * @return void
	 */
	public function setTables()
	{
		$this->db->query("
			CREATE TABLE IF NOT EXISTS {$this->dbPrefix}category_to_1c (
				category_id INT(11) NOT NULL,
				category_1c_id VARCHAR(255) NOT NUll,
				KEY (category_id), KEY (category_1c_id)
			)
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS {$this->dbPrefix}product_to_1c (
				product_id INT(11) NOT NULL,
				product_1c_id VARCHAR(255) NOT NUll,
				KEY (product_id), KEY (product_1c_id)
			)
		");
	}

	/**
	 * Drop tables on uninstall modules.
	 *
	 * @return void
	 */
	public function unsetTables()
	{
		$this->db->query("DROP TABLE {$this->dbPrefix}category_to_1c");
		$this->db->query("DROP TABLE {$this->dbPrefix}product_to_1c");
	}

	/**
	 * Get categories relations to 1C.
	 *
	 * @return array
	 */
	public function getCategoriesMaps()
	{
		$query = $this->db->query("SELECT * FROM {$this->dbPrefix}category_to_1c");

		$result = array();

		foreach ($query->rows as $row)
		{
			$category1cId = $row['category_1c_id'];

			if ( ! empty($category1cId))
			{
				$result[$category1cId] = $row['category_id'];
			}
		}

		return $result;
	}

	/**
	 * Get products relations to 1C.
	 *
	 * @return void
	 */
	public function getProductsMaps()
	{
		$query = $this->db->query("SELECT * FROM {$this->dbPrefix}product_to_1c");

		$result = array();

		foreach ($query->rows as $row)
		{
			$product1cId = $row['product_1c_id'];

			if ( ! empty($product1cId))
			{
				$result[$product1cId] = $row['product_id'];
			}
		}

		return $result;
	}

	/**
	 * Save categories relations.
	 *
	 * @param array $links
	 * @return void
	 */
	public function setCategoriesMaps($links)
	{
		$this->clearCategoriesMaps();

		foreach ($links as $category1cId => $categoryId)
		{
			$this->db->query("INSERT INTO {$this->dbPrefix}category_to_1c SET category_id = '{$categoryId}', category_1c_id = '{$category1cId}'");
		}
	}

	/**
	 * Save products relations.
	 *
	 * @param array $links
	 * @return void
	 */
	public function setProductsMaps($links)
	{
		$this->clearProductsMaps();

		foreach ($links as $product1cId => $productId)
		{
			$this->db->query("INSERT INTO {$this->dbPrefix}product_to_1c SET product_id = '{$productId}', product_1c_id = '{$product1cId}'");
		}
	}

	/**
	 * Delete categories relations.
	 *
	 * @return void
	 */
	public function clearCategoriesMaps()
	{
		$this->db->query("TRUNCATE TABLE {$this->dbPrefix}category_to_1c");
	}

	/**
	 * Delete products relations.
	 *
	 * @return void
	 */
	public function clearProductsMaps()
	{
		$this->db->query("TRUNCATE TABLE {$this->dbPrefix}product_to_1c");
	}

}
