<?php

class Ynmultilisting_Api_ConvertMailVars extends Core_Api_Abstract 
{
	
	protected static $_baseUrl;
	
	public static function getBaseUrl()
	{
		$request =  Zend_Controller_Front::getInstance()->getRequest();
		if(self::$_baseUrl == NULL && $request)
		{
			self::$_baseUrl = sprintf('%s://%s', $request->getScheme(), $request->getHttpHost());
			
		}
		return self::$_baseUrl;
	}
	/**
	 * @param   string $type
	 * @return  string
	 */
	public function selfURL() 
    {
      return self::getBaseUrl();
    }

	public function inflect($type) {
		return sprintf('vars_%s', $type);
	}

	public function vars_default($params, $vars) {
		return $params;
	}

	/**
	 * call from api
	 */
	public function process($params, $vars, $type) {
		$method_name = $this->inflect($type);
		if(method_exists($this, $method_name)) {
			return $this -> {$method_name}($params, $vars);
		}
		return $this -> vars_default($params, $vars);
	}
	
	public function vars_ynmultilisting_listing_created($params, $vars) {
		return $params;
	}
	
	public function vars_ynmultilisting_listing_approved($params, $vars) {
		return $params;
	}
	
	public function vars_ynmultilisting_listing_expired($params, $vars) {
		return $params;
	}
	
	public function vars_ynmultilisting_listing_reviewed($params, $vars) {
		return $params;
	}
	public function vars_ynmultilisting_subscribe_listing($params, $vars) {
		return $params;
	}
}


