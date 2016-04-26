<?php
class Yncredit_TransactionController extends Core_Controller_Action_Standard
{
	protected $_user;
	protected $_session;
	protected $_order;

	public function init()
	{
		if( !$this->_helper->requireAuth()->setAuthParams('yncredit', null, 'use_credit')->isValid() ) return;
		// Get user and session
		$this -> _user = Engine_Api::_() -> user() -> getViewer();
		$this -> _session = new Zend_Session_Namespace('Ynpayment_PayPackage');

		// Check viewer and user
		if (!$this -> _user || !$this -> _user -> getIdentity())
		{
			if ($this -> _session -> __isset('user_id'))
			{
				$this -> _user = Engine_Api::_() -> getItem('user', $this -> _session -> user_id);
			}
			// If no user, redirect to home?
			if (!$this -> _user || !$this -> _user -> getIdentity())
			{
				return $this -> _redirector();
			}
		}
		$this -> _session -> user_id = $this -> _user -> getIdentity();

		// Get Credit order
		$order_id = $this -> _getParam('order_id', $this -> _session -> order_id);
		$params = $this -> _getAllParams();
		
		if($params['action'] != 'finish')
		{
			if ($order_id)
			{
				$this -> _order = Engine_Api::_() -> getDbTable('orders', 'yncredit') -> findRow($order_id);
			}
			else
			{
				$this -> _redirector();
			}
	
			// If no product or product is empty, redirect to home?
			if (!$this -> _order || !$this -> _order -> getIdentity())
			{
				$this -> _redirector();
			}
	
			$this -> _session -> __set('order_id', $this -> _order -> getIdentity());
		}
	}
	public function indexAction()
	{
		return $this -> _helper -> redirector -> gotoRoute(array('action' => 'process'), 'yncredit_transaction', true);
	}
	public function processAction()
	{
		$api = Engine_Api::_() -> yncredit();
		$gatewayTable = Engine_Api::_() -> getDbtable('gateways', 'payment');
		$gatewaySelect = $gatewayTable -> select() -> where('enabled = ?', 1) -> where('gateway_id = ?', $this -> _order -> gateway_id);
		if (null == ($gateway = $gatewayTable -> fetchRow($gatewaySelect)))
		{
			$this -> _redirector();
		}

		// Prepare host info
		$schema = 'http://';
		if (!empty($_ENV["HTTPS"]) && 'on' == strtolower($_ENV["HTTPS"]))
		{
			$schema = 'https://';
		}
		$host = $_SERVER['HTTP_HOST'];

		// Prepare transaction
		$params = array();
		$params['language'] = $this -> _user -> language;
		$localeParts = explode('_', $this -> _user -> language);
		if (count($localeParts) > 1)
		{
			$params['region'] = $localeParts[1];
		}
		$params['vendor_order_id'] = $this -> _order -> getIdentity();
		$params['return_url'] = $schema . $host . $this -> view -> url(array('action' => 'return')) . '?order_id=' . $params['vendor_order_id'] . '&state=' . 'return';
		$params['cancel_url'] = $schema . $host . $this -> view -> url(array('action' => 'return')) . '?order_id=' . $params['vendor_order_id'] . '&state=' . 'cancel';
		$params['ipn_url'] = $schema . $host . $this -> view -> url(array(
			'action' => 'index',
			'controller' => 'ipn'
		)) . '?order_id=' . $params['vendor_order_id'] . '&state=' . 'ipn';

		$gatewayPlugin = $api -> getGateway($this -> _order -> gateway_id);
		$plugin = $api -> getPlugin($this -> _order -> gateway_id);
		$transaction = $plugin -> createPackageTransaction($this -> _order, $params);

		// Pull transaction params
		$this -> view -> transactionUrl = $transactionUrl = $gatewayPlugin -> getGatewayUrl();
		$this -> view -> transactionMethod = $transactionMethod = $gatewayPlugin -> getGatewayMethod();
		$this -> view -> transactionData = $transactionData = $transaction -> getData();

		$this -> _session -> lock();

		// Handle redirection
		if ($transactionMethod == 'GET')
		{
			$transactionUrl .= '?' . http_build_query($transactionData);
			return $this -> _helper -> redirector -> gotoUrl($transactionUrl, array('prependBase' => false));
		}
	}
	public function returnAction()
	{
		// Get order
		if (!$this -> _user 
			|| !($orderId = $this -> _getParam('order_id', $this -> _session -> order_id)) 
			|| !($order = Engine_Api::_() -> getDbTable('orders', 'yncredit') -> findRow($orderId)) 
			|| !($gateway = Engine_Api::_() -> getItem('payment_gateway', $order -> gateway_id)))
		{
			return $this -> _finishPayment('failed');
		}
		try
		{
			if(in_array($gateway -> title, array('2Checkout', 'PayPal')))
			{
				$api = Engine_Api::_() -> yncredit();
				$plugin = $api -> getPlugin($gateway -> getIdentity());
				$status = $plugin -> onPackageTransactionReturn($order, $this -> _getAllParams());
			}
			else
			{
				$status = $order -> onPackageTransactionReturn($this -> _getAllParams());
			}
		}
		catch( Payment_Model_Exception $e )
		{
			$status = 'failed';
			$this -> _session -> __set('errorMessage', $e -> getMessage());
		}
		return $this -> _helper -> redirector -> gotoRoute(array(
			'action' => 'finish',
			'state' => $status
		), 'yncredit_transaction', true);
	}
	public function finishAction()
	{
		$this -> view -> status = $status = $this -> _getParam('state');
		if ($status == 'completed')
		{
			$url = $this -> view -> escape($this -> view -> url(array(), 'yncredit_my', true));
		}
		else
		{
			$url = $this -> view -> escape($this -> view -> url(( array()), 'yncredit_general', true));
			$this -> view -> error = $this -> _session -> errorMessage;
		}
		$this -> view -> continue_url = $url;
		$this -> _session -> unsetAll();
	}
	protected function _redirector()
	{
		$this -> _session -> unsetAll();
		return $this -> _helper -> redirector -> gotoRoute(array(), 'yncredit_general', true);
	}

}