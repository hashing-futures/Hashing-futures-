<?php
namespace MailPoetVendor;
if (!defined('ABSPATH')) exit;
interface Swift_CharacterStream
{
 public function setCharacterSet($charset);
 public function setCharacterReaderFactory(Swift_CharacterReaderFactory $factory);
 public function importByteStream(Swift_OutputByteStream $os);
 public function importString($string);
 public function read($length);
 public function readBytes($length);
 public function write($chars);
 public function setPointer($charOffset);
 public function flushContents();
}
