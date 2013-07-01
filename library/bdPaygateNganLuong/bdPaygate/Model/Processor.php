<?php

class bdPaygateNganLuong_bdPaygate_Model_Processor extends XFCP_bdPaygateNganLuong_bdPaygate_Model_Processor
{
	public function getProcessorNames()
	{
		$names = parent::getProcessorNames();

		$names['nganluong'] = 'bdPaygateNganLuong_Processor';

		return $names;
	}
}