<?php

class bdPaygateNganLuong_bdPaygate_Model_Processor extends XFCP_bdPaygateNganLuong_bdPaygate_Model_Processor
{
	public function getCurrencies()
	{
		$currencies = parent::getCurrencies();

		$currencies[bdPaygateBaoKim_Processor::CURRENCY_VND] = 'VND';

		return $currencies;
	}

	public function getProcessorNames()
	{
		$names = parent::getProcessorNames();

		$names['nganluong'] = 'bdPaygateNganLuong_Processor';

		return $names;
	}
}