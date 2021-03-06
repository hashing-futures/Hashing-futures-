<?php
namespace MailPoetVendor;
if (!defined('ABSPATH')) exit;
class Swift_Mime_ContentEncoder_QpContentEncoder extends Swift_Encoder_QpEncoder implements Swift_Mime_ContentEncoder
{
 protected $dotEscape;
 public function __construct(Swift_CharacterStream $charStream, Swift_StreamFilter $filter = null, $dotEscape = \false)
 {
 $this->dotEscape = $dotEscape;
 parent::__construct($charStream, $filter);
 }
 public function __sleep()
 {
 return ['charStream', 'filter', 'dotEscape'];
 }
 protected function getSafeMapShareId()
 {
 return static::class . ($this->dotEscape ? '.dotEscape' : '');
 }
 protected function initSafeMap()
 {
 parent::initSafeMap();
 if ($this->dotEscape) {
 unset($this->safeMap[0x2e]);
 }
 }
 public function encodeByteStream(Swift_OutputByteStream $os, Swift_InputByteStream $is, $firstLineOffset = 0, $maxLineLength = 0)
 {
 if ($maxLineLength > 76 || $maxLineLength <= 0) {
 $maxLineLength = 76;
 }
 $thisLineLength = $maxLineLength - $firstLineOffset;
 $this->charStream->flushContents();
 $this->charStream->importByteStream($os);
 $currentLine = '';
 $prepend = '';
 $size = $lineLen = 0;
 while (\false !== ($bytes = $this->nextSequence())) {
 // If we're filtering the input
 if (isset($this->filter)) {
 // If we can't filter because we need more bytes
 while ($this->filter->shouldBuffer($bytes)) {
 // Then collect bytes into the buffer
 if (\false === ($moreBytes = $this->nextSequence(1))) {
 break;
 }
 foreach ($moreBytes as $b) {
 $bytes[] = $b;
 }
 }
 // And filter them
 $bytes = $this->filter->filter($bytes);
 }
 $enc = $this->encodeByteSequence($bytes, $size);
 $i = \strpos($enc, '=0D=0A');
 $newLineLength = $lineLen + (\false === $i ? $size : $i);
 if ($currentLine && $newLineLength >= $thisLineLength) {
 $is->write($prepend . $this->standardize($currentLine));
 $currentLine = '';
 $prepend = "=\r\n";
 $thisLineLength = $maxLineLength;
 $lineLen = 0;
 }
 $currentLine .= $enc;
 if (\false === $i) {
 $lineLen += $size;
 } else {
 // 6 is the length of '=0D=0A'.
 $lineLen = $size - \strrpos($enc, '=0D=0A') - 6;
 }
 }
 if (\strlen($currentLine)) {
 $is->write($prepend . $this->standardize($currentLine));
 }
 }
 public function getName()
 {
 return 'quoted-printable';
 }
}
