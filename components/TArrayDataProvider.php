<?php

/**
 * TArrayDataProvider
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * 
 */
class TArrayDataProvider extends CArrayDataProvider
{

	public $keyDelimiter = ',';
	
	/**
	 * Fetches the data item keys from the persistent data storage.
	 * @return array list of data item keys.
	 */
	protected function fetchKeys()
	{
		if($this->keyField === false)
		{
			return array_keys($this->rawData);
		}
		
		if($this->keyField === true)
		{
			$firstRow = reset($this->rawData);
			return is_array($firstRow) ? array_keys($firstRow) : array();
		}
		
		$keys = array();
		if(is_array($this->keyField))
		{
			foreach($this->getData() as $i => $data)
			{
				$keys[$i] = array();
				if(is_object($data))
				{
					foreach($this->keyField as $key)
					{
						$keys[$i][] = $data->$key;
					}
				}
				else
				{
					foreach($this->keyField as $key)
					{
						$keys[$i][] = $data[$key];
					}
				}
				$keys[$i] = implode($this->keyDelimiter, $keys[$i]);
			}
		}
		else
		{
			foreach($this->getData() as $i => $data)
			{
				$keys[$i] = is_object($data) ? $data->{$this->keyField} : $data[$this->keyField];
			}
		}
		return $keys;
	}

}