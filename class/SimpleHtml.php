<?php

class SimpleHtml {
    private $contents = '';
    private $code;

    public function __construct($code = 200) {
        $this->code = $code;
    }

    private static function hscArray($text) {
        return is_array($text) ? implode('<br>', array_map('htmlspecialchars', $text)) : htmlspecialchars($text);
    }

    public function addErrorDiv($text = '') /*:self*/ {
        $this->contents .= '<div style="color:red">' . self::hscArray($text) . '</div>';
        return $this;
    }

    public function addPre($text = '') /*:self*/ {
        $this->contents .= '<pre>' . self::hscArray($text) . '</pre>';
        return $this;
    }

    public function addScript($text = '') /*:self*/ {
        $this->contents .= '<script>' . self::hscArray($text) . '</script>';
        return $this;
    }

    public function render() {
        switch ($this->code) {
            case 404:
                header('HTTP/1.1 404 Not Found');
                break;
            case 500:
                header('HTTP/1.1 500 Internal Server Error');
                break;
        }
        header('Content-Type: text/html');
        echo '<html lang="en"><head><title>Mbn Library</title></head><body>' . $this->contents . '</body></html>';
    }
}
