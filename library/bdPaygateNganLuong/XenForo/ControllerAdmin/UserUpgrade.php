<?php

class bdPaygateNganLuong_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygateNganLuong_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygateNganLuong_hijackOptions();

		return parent::actionIndex();
	}
}