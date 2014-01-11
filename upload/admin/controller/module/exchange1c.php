<?php

class ControllerModuleExchange1C extends Controller {

	/**
	 * Get module page.
	 *
	 * @return void
	 */
	public function index()
	{
		if ($this->request->server['REQUEST_METHOD'] == 'POST')
		{
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('exchange1c', $this->request->post);
			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['config'] = $this->getConfigs(array(
			'e1c_import_username',
			'e1c_import_password',
			'e1c_import_pricetype'
		));
		
		$this->showPage($data);
	}

	/**
	 * Clear relations action.
	 *
	 * @return void
	 */
	public function clearRelations()
	{
		$this->load->model('module/exchange1c');

		$this->model_module_exchange1c->clearCategoriesMaps();
		$this->model_module_exchange1c->clearProductsMaps();

		$this->redirect($this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL'));
	}

	/**
	 * Install action.
	 *
	 * @return void
	 */
	public function install()
	{
		$this->load->model('module/exchange1c');
		$this->model_module_exchange1c->setTables();
	}

	/**
	 * Uninstall action.
	 *
	 * @return void
	 */
	public function uninstall()
	{
		$this->load->model('module/exchange1c');
		$this->model_module_exchange1c->unsetTables();
	}

	/**
	 * Render module page.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function showPage($data = array())
	{
		$this->data = $data;

		$this->data['lang'] = $this->load->language('module/exchange1c');
		$this->data['breadcrumbs'] = $this->buidlBreadcrumbs();
		$this->data['token'] = $this->session->data['token'];
		$this->data['version'] = $this->getVersion();
		$this->data['update'] = $this->getUpdate();

		$this->data['clear_relations'] = $this->url->link('module/exchange1c/clearrelations', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['action'] = $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		$this->document->setTitle($this->data['lang']['heading_title']);

		$this->template = 'module/exchange1c.tpl';
		$this->children = array('common/header', 'common/footer');

		$this->response->setOutput($this->render(), $this->config->get('config_compression'));
	}

	/**
	 * Build breadcrumbs.
	 *
	 * @return array
	 */
	protected function buidlBreadcrumbs()
	{
		return array(
			array(
				'text'       => $this->language->get('text_home'),
				'href'       => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator'  => false
			),
			array(
				'text'       => $this->language->get('text_module'),
				'href'       => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
				'separator'  => ' :: '
			),
			array(
				'text'       => $this->language->get('heading_title'),
				'href'       => $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL'),
				'separator'  => ' :: '
			)
		);
	}

	/**
	 * Get config.
	 *
	 * @param array $keys
	 * @return array
	 */
	protected function getConfigs($keys = array())
	{
		$result = array();

		foreach ($keys as $key)
		{
			$result[$key] = $this->config->get($key);
		}

		return $result;
	}

	/**
	 * Get version of module.
	 *
	 * @return string
	 */
	protected function getVersion()
	{
		$vfile = DIR_SYSTEM . "/third_party/exchange1c/version";

		if (file_exists($vfile) && is_readable($vfile))
		{
			return file_get_contents($vfile);
		}
	}

	/**
	 * Check for new versions.
	 *
	 * @return bool
	 */
	protected function getUpdate()
	{
		$new = $this->cache->get('e1c_version');

		if (is_null($new))
		{
			$new = @file_get_contents('https://raw.github.com/ethernet1/opencart-exchange1c/2.0-dev/upload/system/third_party/exchange1c/version');
			$this->cache->set('e1c_version', $new);
		}
		
		return (version_compare($this->getVersion(), $new) == -1)? true : false;
	}

}
