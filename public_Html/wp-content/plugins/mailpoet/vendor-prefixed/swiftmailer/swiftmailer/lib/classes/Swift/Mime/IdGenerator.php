<?php
namespace MailPoetVendor;
if (!defined('ABSPATH')) exit;
class Swift_Mime_IdGenerator implements Swift_IdGenerator
{
 private $idRight;
 public function __construct($idRight)
 {
 $this->idRight = $idRight;
 }
 public function getIdRight()
 {
 return $this->idRight;
 }
 public function setIdRight($idRight)
 {
 $this->idRight = $idRight;
 }
 public function generateId()
 {
 // 32 hex values for the left part
 return \bin2hex(\random_bytes(16)) . '@' . $this->idRight;
 }
}
