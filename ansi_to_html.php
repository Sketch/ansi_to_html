<?php
    /* ANSI to HTML
     * Author: Sketch@M*U*S*H (Ryan Dowell)
	  * With help from Mike@M*U*S*H (Mike Griffiths)
	  * Original source: https://github.com/Sketch/ansi_to_html
     *
     * The purpose of the function ansi_to_html is to transform strings from
     * a PennMUSH game that encode color data as ANSI/XTerm codes into a string
     * that encodes color data as HTML span tags.
     *
     * The basis of operation is making preg_replace_callback call a
     * function object as a callback. The object's state changes with each
     * callback. The state determines how to replace the given ANSI codes with
     * span tags.
     */

  namespace AnsiToHtml {

    // Note: Keys are cast to Integers!
    $foregrounds = array(
      30 => 'x',
      31 => 'r',
      32 => 'g',
      33 => 'y',
      34 => 'b',
      35 => 'm',
      36 => 'c',
      37 => 'w'
    );

    $backgrounds = array(
      40 => 'x',
      41 => 'r',
      42 => 'g',
      43 => 'y',
      44 => 'b',
      45 => 'm',
      46 => 'c',
      47 => 'w'
    );

    // Turn string like "\e[1;32m" into chunked array of codes like ['1', '32'].
	 // If the code is an XTerm color code like "\e[38;5;123m",
	 // it will be the single element of the array like ['38;5;123'].
    function escape_code_to_array($input) {
      $str = trim($input, "\033[m");
      $exploded = explode(';', $input); 
      if ($exploded[0] === '38' || $exploded[0] === '48') {
        return Array($str);
      } else {
        return $exploded;
      }
    }

    class ColorState {
      private $ansi_fg = 'default';
      private $ansi_bg = 'default';
      private $ansi_hilite = false;
      private $ansi_invert = false;
      private $ansi_underscore = false;
      private $ansi_flash = false;
      private $debug = false;

      private function debug($str) {
        if ($this->debug) {
          echo "<!-- $str -->";
        }
      }

      // Returns true if ANSI state has no colors or flags.
      public function state_is_default() {
        return ($this->ansi_fg == 'default' && $this->ansi_bg == 'default' &&
               !($this->ansi_hilite || $this->ansi_invert ||
                $this->ansi_underscore || $this->ansi_flash));
      }

      // Mutator helper.
      // Expects 4-element array representing ANSI flag states.
      // The string '-' means "keep state'. Otherwise assigns given value.
      private function alter_state_flags($flags) {
        $this->debug("altering: " . print_r($flags, 1));
        $this->ansi_hilite = ($flags[0] === '-' ? $this->ansi_hilite : $flags[0]);
        $this->ansi_underscore = ($flags[1] === '-' ? $this->ansi_underscore : $flags[1]);
        $this->ansi_flash = ($flags[2] === '-' ? $this->ansi_flash : $flags[2]);
        $this->ansi_invert = ($flags[3] === '-' ? $this->ansi_invert : $flags[3]);
      }

      // Mutator. Call with array of numeric codes of an ANSI escape code.
      private function alter_state($codes) {
        global $foregrounds;
        global $backgrounds;
        foreach ($codes as $code) {
          switch ($code) {
            case '0':
              $this->alter_state_flags(array(FALSE,FALSE,FALSE,FALSE));
              $this->ansi_fg = 'default';
              $this->ansi_bg = 'default';
              break;
            case '1': $this->debug("1 == hilite"); $this->alter_state_flags(array(TRUE,'-','-','-')); break;
            case '4': $this->debug("4 == underscore"); $this->alter_state_flags(array('-',TRUE,'-','-')); break;
            case '5': $this->debug("5 == flash"); $this->alter_state_flags(array('-','-',TRUE,'-')); break;
            case '7': $this->debug("7 == invert"); $this->alter_state_flags(array('-','-','-',TRUE)); break;
            default:
              if (isSet($foregrounds[$code])) {
                $this->ansi_fg = $foregrounds[$code];
              } elseif (isSet($backgrounds[$code])) {
                $this->ansi_bg = $backgrounds[$code];
              } elseif (preg_match('/^38;5;(\d+)$/', $code, $matches)) {
                $this->ansi_fg = $matches[1];
              } elseif (preg_match('/^48;5;(\d+)$/', $code, $matches)) {
                $this->ansi_bg = $matches[1];
              } else {
                $this->debug("Bad code '$code'!");
              }
            break;
          }
        }
      }

      // Main callback
      public function alter_state_and_print_span($captures) {
        $str = $this->str_closing_tag();
        $this->debug("CAPTURES: " . print_r($captures, 1));
        $this->alter_state(escape_code_to_array($captures[1]));
        $str .= $this->str_state_tag();
        return $str;
      }

      // Return an array of CSS classes corresponding to the current state.
      public function state_classes() {
        $spanclasses = array('mush');

        if ($this->ansi_invert) {
          array_push($spanclasses, 'invert');
        }
        if ($this->ansi_hilite) {
          array_push($spanclasses, 'mush_strong');
        }
        if ($this->ansi_underscore) {
          array_push($spanclasses, 'underscore');
        }
        if ($this->ansi_flash) {
          array_push($spanclasses, 'flash');
        }
        if ($this->ansi_fg != 'default') {
          array_push($spanclasses, 'fg_' . $this->ansi_fg);
        }
        if ($this->ansi_bg != 'default') {
          array_push($spanclasses, 'bg_' . $this->ansi_bg);
        }

        return $spanclasses;
      }

      // Returns a span tag based on this object's ANSI state.
      public function str_state_tag() {
        $str  =  '<span class="';
        $str .= join(' ', $this->state_classes());
        $str .= '">';
        return $str;
      }

      // returns a closing tag
      public function str_closing_tag() {
        return '</span>';
      }
    }
  }

  namespace {
    function ansi_string_to_html($input) {
      $state = new AnsiToHtml\ColorState();
      $str = '<span class="mush">';
      $str .= preg_replace_callback('/\033\[([^m]+)m/', array($state, 'alter_state_and_print_span'), $input, -1);
      $str .= '</span>';
      return $str;
    }
  }

?>
