<?php

/**
* Detects date format (unix timestamp or ISO) and returns it formatted according to settings
*/
class CE_View_Helper_DateRenderer extends Zend_View_Helper_Abstract
{
    public function dateRenderer($date)
    {
        return CE_Lib::dateRenderer($date);
    }
}
