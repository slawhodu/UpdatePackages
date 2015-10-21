<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */
require_once 'config/debug.php';
require_once 'config/developer.php';
require_once 'config/security.php';
require_once 'config/secret_keys.php';
require_once 'config/performance.php';
require_once('include/ConfigUtils.php');
require_once 'include/utils/utils.php';
require_once 'include/utils/CommonUtils.php';
require_once 'include/Loader.php';
vimport('include.runtime.EntryPoint');

session_save_path(vglobal('root_directory') . '/cache/session');

class Vtiger_WebUI extends Vtiger_EntryPoint
{

	/**
	 * Function to check if the User has logged in
	 * @param Vtiger_Request $request
	 * @throws AppException
	 */
	protected function checkLogin(Vtiger_Request $request)
	{
		if (!$this->hasLogin()) {
			$return_params = $_SERVER['QUERY_STRING'];
			if ($return_params && !$_SESSION['return_params']) {
				//Take the url that user would like to redirect after they have successfully logged in.
				$return_params = urlencode($return_params);
				Vtiger_Session::set('return_params', $return_params);
			}
			header('Location: index.php');
			throw new AppException('Login is required');
		}
	}

	/**
	 * Function to get the instance of the logged in User
	 * @return Users object
	 */
	function getLogin()
	{
		$user = parent::getLogin();
		if (!$user) {
			$userid = Vtiger_Session::get('AUTHUSERID', $_SESSION['authenticated_user_id']);
			if ($userid) {
				$user = CRMEntity::getInstance('Users');
				$user->retrieveCurrentUserInfoFromFile($userid);
				$this->setLogin($user);
			}
		}
		return $user;
	}

	protected function triggerCheckPermission($handler, $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if (empty($moduleModel)) {
			throw new AppException(vtranslate($moduleName) . ' ' . vtranslate('LBL_HANDLER_NOT_FOUND'));
		}

		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if ($permission) {
			$handler->checkPermission($request);
			return;
		}
		throw new AppException(vtranslate($moduleName) . ' ' . vtranslate('LBL_NOT_ACCESSIBLE'));
	}

	protected function triggerPreProcess($handler, $request)
	{
		if ($request->isAjax()) {
			return true;
		}
		$handler->preProcess($request);
	}

	protected function triggerPostProcess($handler, $request)
	{
		if ($request->isAjax()) {
			return true;
		}
		$handler->postProcess($request);
	}

	function isInstalled()
	{
		global $dbconfig;
		if (empty($dbconfig) || empty($dbconfig['db_name']) || $dbconfig['db_name'] == '_DBC_TYPE_') {
			return false;
		}
		return true;
	}

	function process(Vtiger_Request $request)
	{
		$log = LoggerManager::getLogger('System');
		vglobal('log', $log);
		Vtiger_Session::init();
		$forceSSL = vglobal('forceSSL');
		if ($forceSSL && !Vtiger_Functions::getBrowserInfo()->https) {
			header("Location: https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		}

		// Better place this here as session get initiated
		//skipping the csrf checking for the forgot(reset) password
		$csrfProtection = vglobal('csrfProtection');
		if ($csrfProtection) {
			if ($request->get('mode') != 'reset' && $request->get('action') != 'Login')
				require_once 'libraries/csrf-magic/csrf-magic.php';
		}
		// TODO - Get rid of global variable $current_user
		// common utils api called, depend on this variable right now
		$currentUser = $this->getLogin();
		vglobal('current_user', $currentUser);

		$currentLanguage = Vtiger_Language_Handler::getLanguage();
		vglobal('current_language', $currentLanguage);
		$module = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		if ($currentUser && $qualifiedModuleName) {
			$moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($currentLanguage, $qualifiedModuleName);
			vglobal('mod_strings', $moduleLanguageStrings['languageStrings']);
		}

		if ($currentUser) {
			$moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($currentLanguage);
			vglobal('app_strings', $moduleLanguageStrings['languageStrings']);
		}

		$view = $request->get('view');
		$action = $request->get('action');
		$response = false;

		try {
			if ($this->isInstalled() === false && $module != 'Install') {
				header('Location:install/Install.php');
				exit;
			}

			if (empty($module)) {
				if ($this->hasLogin()) {
					$defaultModule = vglobal('default_module');
					if (!empty($defaultModule) && $defaultModule != 'Home') {
						$module = $defaultModule;
						$qualifiedModuleName = $defaultModule;
						$view = 'List';
						if ($module == 'Calendar') {
							// To load MyCalendar instead of list view for calendar
							//TODO: see if it has to enhanced and get the default view from module model
							$view = 'Calendar';
						}
					} else {
						$module = 'Home';
						$qualifiedModuleName = 'Home';
						$view = 'DashBoard';
					}
				} else {
					$module = 'Users';
					$qualifiedModuleName = 'Settings:Users';
					$view = 'Login';
				}
				$request->set('module', $module);
				$request->set('view', $view);
			}

			if (!empty($action)) {
				$componentType = 'Action';
				$componentName = $action;
			} else {
				$componentType = 'View';
				if (empty($view)) {
					$view = 'Index';
				}
				$componentName = $view;
			}
			$handlerClass = Vtiger_Loader::getComponentClassName($componentType, $componentName, $qualifiedModuleName);
			$handler = new $handlerClass();
			if ($handler) {
				vglobal('currentModule', $module);
				$csrfProtection = vglobal('csrfProtection');
				if ($csrfProtection) {
					// Ensure handler validates the request
					$handler->validateRequest($request);
				}

				if ($handler->loginRequired()) {
					$this->checkLogin($request);
				}

				//TODO : Need to review the design as there can potential security threat
				$skipList = array('Users', 'Home', 'CustomView', 'Import', 'Export', 'Inventory', 'Vtiger', 'Migration', 'Install');

				if (!in_array($module, $skipList) && stripos($qualifiedModuleName, 'Settings') === false) {
					$this->triggerCheckPermission($handler, $request);
				}

				// Every settings page handler should implement this method
				if (stripos($qualifiedModuleName, 'Settings') === 0 || ($module == 'Users')) {
					$handler->checkPermission($request);
				}

				$notPermittedModules = array('ModComments', 'Integration', 'DashBoard');

				if (in_array($module, $notPermittedModules) && $view == 'List') {
					header('Location:index.php?module=Home&view=DashBoard');
				}

				$this->triggerPreProcess($handler, $request);
				$response = $handler->process($request);
				$this->triggerPostProcess($handler, $request);
			} else {
				throw new AppException(vtranslate('LBL_HANDLER_NOT_FOUND'));
			}
		} catch (AppException $e) {
			$log->error($e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
			Vtiger_Functions::throwNewException($e->getMessage(), false);
			if (SysDebug::get('DISPLAY_DEBUG_BACKTRACE')) {
				exit('<pre>'.$e->getTraceAsString().'</pre>');
			}
		} catch (NoPermittedException $e) {
			//No permissions for the record
			$log->error($e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
			Vtiger_Functions::throwNoPermittedException($e->getMessage(), false);
			if (SysDebug::get('DISPLAY_DEBUG_BACKTRACE')) {
				exit('<pre>'.$e->getTraceAsString().'</pre>');
			}
		} catch (Exception $e) {
			$log->error($e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
			Vtiger_Functions::throwNewException($e->getMessage(), false);
			if (SysDebug::get('DISPLAY_DEBUG_BACKTRACE')) {
				exit('<pre>'.$e->getTraceAsString().'</pre>');
			}
		}

		if ($response) {
			$response->emit();
		}
	}
}