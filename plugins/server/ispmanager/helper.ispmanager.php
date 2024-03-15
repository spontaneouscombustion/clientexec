<?php

class xml2Array {
  
   public $arrOutput = array();
   public $resParser;
   public $strXmlData;
  
   function parse($strInputXML) {
  
           $this->resParser = xml_parser_create ();
           xml_set_object($this->resParser,$this);
           xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
          
           xml_set_character_data_handler($this->resParser, "tagData");
      
           $this->strXmlData = xml_parse($this->resParser,$strInputXML );
           if($this->strXmlData === 0) {
               die(sprintf("XML error: %s at line %d",
           xml_error_string(xml_get_error_code($this->resParser)),
           xml_get_current_line_number($this->resParser)));
           }
                          
           xml_parser_free($this->resParser);
          
           return $this->arrOutput;
   }
   function tagOpen($parser, $name, $attrs) {
       $tag=array("name"=>$name,"attrs"=>$attrs);
       $this->arrOutput[] = $tag;
   }
  
   function tagData($parser, $tagData) {
       if(trim($tagData) !== '' && trim($tagData) !== '0') {
           if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
           }
           else {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
           }
       }
   }
  
   function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
   }
}


?>
