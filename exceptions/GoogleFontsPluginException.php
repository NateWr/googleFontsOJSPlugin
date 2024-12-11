<?php
namespace APP\plugins\generic\googleFonts\exceptions;

use APP\plugins\generic\googleFonts\GoogleFontsPlugin;
use Exception;
use Throwable;

/**
 * Exception emitted from the Google Fonts plugin
 *
 * Adds plugin details to the error messages to indicate the origin
 * of the messages.
 */
class GoogleFontsPluginException extends Exception
{
  public function __construct(protected GoogleFontsPlugin $plugin, $message, $code = 0, Throwable $previous = null)
  {
    $message = "{$this->plugin->getDisplayName()} ({$this->plugin->getName()}): " . $message;
    parent::__construct($message, $code, $previous);
  }
}