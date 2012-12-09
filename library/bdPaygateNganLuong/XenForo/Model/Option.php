<?php

class bdPaygateNganLuong_XenForo_Model_Option extends XFCP_bdPaygateNganLuong_XenForo_Model_Option
{
	// this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
	// for no apparent reason use XenForo_Model::create to create the optionModel
	// (instead of using XenForo_Controller::getModelFromCache)
	private static $_bdPaygateNganLuong_hijackOptions = false;
	
	public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
	{
		if (self::$_bdPaygateNganLuong_hijackOptions === true)
		{
			$optionIds[] = 'bdPaygateNganLuong_id';
			$optionIds[] = 'bdPaygateNganLuong_pass';
			$optionIds[] = 'bdPaygateNganLuong_email';
		}
		
		$options = parent::getOptionsByIds($optionIds, $fetchOptions);
		
		self::$_bdPaygateNganLuong_hijackOptions = false;

		return $options;
	}
	
	public function bdPaygateNganLuong_hijackOptions()
	{
		self::$_bdPaygateNganLuong_hijackOptions = true;
	}
}