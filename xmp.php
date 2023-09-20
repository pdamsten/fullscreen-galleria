<?php
/******************************************************************************

Description: Read xmp metadata
Version: 0.1
Author: Petri Damstén
Author URI: https://petridamsten.com/
License: MIT

******************************************************************************/

class XMPMetadata {
  protected $xml = [];

  function __construct($fname = null) 
  {
    if ($fname) {
      $this->read($fname);
    }
  }

  function read($fname) 
  {
    $this->xml = [];
    $buffer = '';
    if ($fp = fopen($fname, 'rb')) {
      $buffer = fread($fp, 1024*1024);
      $offset = 0;
      while (($endpos = strpos($buffer, '</x:xmpmeta>', $offset)) !== false ) {
        if (($startpos = strpos($buffer, '<x:xmpmeta', $offset)) !== false) {
            array_push($this->xml, substr($buffer, $startpos, $endpos - $startpos + 12));
        }
        $offset = $endpos + 12;
      }
      fclose($fp);
    }
  }

  function calc_gps($gps, $ref)
  {
    return '';
  }

  function longitude()
  {
    return $this->calc_gps($xml->value('exif:GPSInfo.GPSLongitude'), 
                           $xml->value('exif:GPSInfo.GPSLongitudeRef'));
  }

  function latitude()
  {
    return $this->calc_gps($xml->value('exif:GPSInfo.GPSLatitude'), 
                           $xml->value('exif:GPSInfo.GPSLatitudeRef'));
  }

  function value($key)
  {
    # could not get simplexml_load_string working with xmp metadata
    foreach ($this->xml as $xml) {
      if (($startpos = strpos($xml, $key.'="')) !== false) {
        $startpos += strlen($key) + 2;
        if (($endpos = strpos($xml, '"', $startpos)) !== false) {
          return substr($xml, $startpos, $endpos - $startpos);
        }
      }
      if (($startpos = strpos($xml, $key.'>')) !== false) {
        $startpos += strlen($key) + 1;
        $bag = strpos($xml, '<rdf:Bag>', $startpos);
        $xdef = strpos($xml, 'x-default">', $startpos);

        if ($bag < $xdef) {
          $startpos = $bag + 9;
          if (($endpos = strpos($xml, '</rdf:Bag>', $startpos)) !== false) {
            $s = substr($xml, $startpos, $endpos - $startpos);
            $s = str_replace('<rdf:li>', '', $s);
            $s = str_replace('</rdf:li>', '|', $s);
            $s = trim($s, " \n\r\t\v\x00|");
            $s = preg_replace("/\s*\|\s*/m", ', ', $s);
            return $s;
          }
        } else {
          $startpos = $xdef + 11;
          if (($endpos = strpos($xml, '<', $startpos)) !== false) {
            return substr($xml, $startpos, $endpos - $startpos);
          }
        }
      }
    }
  }

}

if (isset($argv) && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
  $xml = new XMPMetadata($argv[1]);
  echo($xml->value('exif:Photo.BodySerialNumber'));
  echo($xml->value('dc:title'));
  echo($xml->value('dc:subject'));
}