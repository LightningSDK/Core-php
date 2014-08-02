<?

namespace Lightning\View;

class JS {
  protected static $included_files = array();
  protected static $inline_scripts = array();
  protected static $startup_scripts = array();

  public static function add($file) {
    self::$included_files[$file] = $file;
  }

  public static function inline($script) {
    self::$inline_scripts[] = $script;
  }

  public static function startup($script) {
    self::$startup_scripts[] = $script;
  }

  public static function render() {
    $output = '';

    foreach (self::$included_files as $file) {
      $output .= '<script language="javascript" src="' . $file . '"></script>';
    }

    if (!empty(self::$inline_scripts) || !empty(self::$startup_scripts)) {
      $output .= '<script language="javascript">';
      foreach (self::$inline_scripts as $script) {
        $output .= $script . "\n\n";
      }

      if (!empty(self::$startup_scripts)) {
        $output .= '$(function(){';
        foreach (self::$startup_scripts as $script) {
          $output .= $script;
        }
        $output .= '})';
      }
      $output .= '</script>';
    }

    return $output;
  }
}