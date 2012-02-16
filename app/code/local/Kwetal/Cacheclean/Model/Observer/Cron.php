<?php
class Kwetal_Cacheclean_Model_Observer_Cron extends Mage_Core_Model_Config_Data
{    
    const XML_PATH_CACHE_CLEAN_TYPES = 'kwetal_cache/cache_clean/types';
    
    public function cleanCache()
    {
    	$sizeBefore = $this->getCacheDirSize();
    
    	$cache = Mage::getModel('core/cache');
    	$types = explode(',', Mage::getStoreConfig(self::XML_PATH_CACHE_CLEAN_TYPES));
    	$allTags = array();
    	foreach($types as $type) {
    		if($tags = $cache->getTagsByType($type)) {
    			$allTags = $allTags + $tags;
    		}
    	}
    
    	Mage::app()->getCache()
    	           ->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $allTags);
    
    	$sizeAfter = $this->getCacheDirSize();
    
    	return 'Size before: ' . $sizeBefore[0] . ' <==> Size after: ' . $sizeAfter[0];
    }
    
    public function clearOldTags()
    {
    	$sizeBefore = $this->getCacheDirSize();
    
    	Mage::app()->getCache()
    	           ->clean(Zend_Cache::CLEANING_MODE_OLD);
    
    	$sizeAfter = $this->getCacheDirSize();
    
    	return 'Size before: ' . $sizeBefore[0] . ' <==> Size after: ' . $sizeAfter[0];
    }
    
    private function getCacheDirSize()
    {
    	$size = shell_exec('du -hs ' . Mage::getBaseDir('var') . '/cache/');
    	return preg_split('#\s+#', $size, null, PREG_SPLIT_NO_EMPTY);
    }
    
    protected function _beforeSave()
    {
        $cronExpr = trim($this->getData('groups/cache_clean/fields/cron_clean_tags/value'));
        if('' == $cronExpr) {
        	return;
        }
        $this->_matchCronExpression($cronExpr, 'cron_clean_tags');
        
        $cronExpr = trim($this->getData('groups/cache_clean/fields/cron_clean_old/value'));
        if('' == $cronExpr) {
        	return;
        }
        $this->_matchCronExpression($cronExpr, 'cron_clean_old');
    }
    
    protected function _matchCronExpression($cronExpr, $type)
    {
    	$cronExprArray = explode(' ', $cronExpr);
    	$e = preg_split('#\s+#', $cronExpr, null, PREG_SPLIT_NO_EMPTY);
    	if(sizeof($e) < 5 || sizeof($e) > 6) {
    		throw new Exception('Invalid Cron Expression: ' . $type . '; must have 5 fields');
    	}
    	
    	$match = $this->matchCronExpression($cronExprArray[0]) &&
    	         $this->matchCronExpression($cronExprArray[1]) &&
    	         $this->matchCronExpression($cronExprArray[2]) &&
    	         $this->matchCronExpression($cronExprArray[3]) &&
    	         $this->matchCronExpression($cronExprArray[4]);
    	if(! $match) {
    		throw new Exception('Invalid Cron Expression: ' . $type);
    	}
    }
     
    public function matchCronExpression($expr)
    {
//     	handle ALL match
    	if($expr === '*') {
    		return true;
    	}
    
//     	handle multiple options
    	if(strpos($expr, ',') !== false) {
    		foreach(explode(',', $expr) as $e) {
    			if($this->matchCronExpression($e)) {
    				return true;
    			}
    		}
    		return false;
    	}
    
//     	handle modulus
    	if(strpos($expr, '/') !== false) {
    		$e = explode('/', $expr);
    		if(sizeof($e) !== 2) {
    			throw Mage::exception('Mage_Cron', "Invalid cron expression, expecting 'match/modulus': " . $expr);
    		}
    		if(! is_numeric($e[1])) {
    			throw Mage::exception('Mage_Cron', "Invalid cron expression, expecting numeric modulus: " . $expr);
    		}
    		$expr = $e[0];
    		$mod = $e[1];
    	} else {
    		$mod = 1;
    	}
    
//     	handle all match by modulus
    	if($expr == '*') {
    		$from = 0;
    		$to = 60;
    	}
//     	handle range
    	elseif(strpos($expr, '-') !== false) {
    		$e = explode('-', $expr);
    		if(sizeof($e) !== 2) {
    			throw Mage::exception('Mage_Cron', "Invalid cron expression, expecting 'from-to' structure: " . $expr);
    		}
    
    		$from = $this->getNumeric($e[0]);
    		$to = $this->getNumeric($e[1]);
    	}
//     	handle regular token
    	else {
    		$from = $this->getNumeric($expr);
    		$to = $from;
    	}
    
    	if($from === false || $to === false) {
    		throw Mage::exception('Mage_Cron', "Invalid cron expression: " . $expr);
    	}
    
    	return true;
    }
    
    public function getNumeric($value)
    {
    	static $data = array('jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
    						 'jul' => 7,'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    						 'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
    	);
    
    	if (is_numeric($value)) {
    		return $value;
    	}
    
    	if (is_string($value)) {
    		$value = strtolower(substr($value,0,3));
    		if (isset($data[$value])) {
    			return $data[$value];
    		}
    	}
    
    	return false;
    }
}


