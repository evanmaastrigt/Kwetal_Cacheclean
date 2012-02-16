<?php
class Kwetal_Cacheclean_Model_Source extends Mage_Core_Model_Abstract
{
    public function getCacheTypes()
    {
        $return = array();
        
        foreach(Mage::getModel('core/cache')->getTypes() as $type => $cache) {
            $return[] = array('value' => $cache->getId(), 'label' => $cache->getDescription());
        }
        
        return $return;
    }
}