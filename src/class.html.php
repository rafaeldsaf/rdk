<?php
/**
 *
 * @author rafaeldsaf@live.com
 *
 */

namespace RDK;

class Html
{
    private static array $preScript ;
    private static array $styleCSS;
    private static array $head;

    private static string $html;
    private static array $posScript;

    /**
     * Cria uma string HTML de forma dinamica
     * @param string $tag
     * @param string|array $_attr
     * @param string $_content
     * @return string
     */
    public static function tagHTML(string $tag, string|array $_attr = '', string $_content = ''): string
    {
        $_final = '<' . $tag;
        if (is_array($_attr)) {
            foreach ($_attr as $key => $value) {
                if ($value != '') {
                    if (strpos($value, '"') !== false) {
                        $_final .= ' ' . $key . "='" . $value . "'";
                    } else {
                        $_final .= ' ' . $key . '="' . $value . '"';
                    }
                } else
                    $_final .= ' ' . $key;
            }
            if ((!isset($_attr['name'])) and (isset($_attr['id']))) {
                if (isset($_attr['multiple'])) {
                    $_final .= ' ' . 'name="' . $_attr['id'] . '[]"';
                } else {
                    $_final .= ' ' . 'name="' . $_attr['id'] . '"';
                }
            }
            if (($tag == 'a') and (!isset($_attr['href']))) {
                $_final .= ' ' . 'href="javascript:;"';
            }
        }
        else if ($_attr <> ''){
            $_final .= "class=\"$_attr\"";
        }

        if ($tag == 'input') {
            if ($_content != '') {
                if (($_attr['type'] == 'checkbox') or ($_attr['type'] == 'radio')) {
                    $_final = self::tagHTML('label', array('for' => $_attr['id'], 'id' => "label_{$_attr['id']}")) . $_final;
                } else {
                    $_final = self::tagHTML('label', array('for' => $_attr['id'], 'id' => "label_{$_attr['id']}"), $_content) . $_final;
                }
            }
        }

        if (($tag <> 'input') or (($tag == 'input') and ($_attr['type'] == 'checkbox')) or (($tag == 'input') and ($_attr['type'] == 'radio'))) {
            if (is_array($_content)) {
                $_final .= '>' . implode('', $_content);
            } else {
                $_final .= $_content;
            }
        }
        $_final .= '</' . $tag . '>';

        return $_final;
    }

    /**
     * @param string|array $href
     * @return void
     */
    public static function styleCSS(string|array $href): void
    {
        if (is_array($href)){
            foreach ($href as $_line){
                self::$styleCSS[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_line));
            }
        }
        else{
            self::$styleCSS[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $href));
        }
    }

    /**
     * @param string|array $href
     * @return void
     */
    public static function preScript(string|array $href): void
    {
        if (is_array($href)){
            foreach ($href as $_line){
                self::$preScript[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_line));
            }
        }
        else{
            self::$preScript[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $href));
        }
    }

    /**
     * @param string|array $href
     * @return void
     */
    public static function posScript(string|array $href): void
    {
        if (is_array($href)){
            foreach ($href as $_line){
                self::$posScript[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_line));
            }
        }
        else{
            self::$posScript[] = self::tagHTML('link', array('rel' => "stylesheet", 'href' => $href));
        }
    }

    /**
     * @param string|array $href
     * @return void
     */
    public static function headVal(string|array $href): void
    {
        if (is_array($href)){
            foreach ($href as $_line){
                self::$head[] = $_line;
            }
        }
        else{
            self::$head[] = $href;
        }
    }

    /**
     * @param $html
     * @return void
     */
    public static function basicHTML($html): void
    {

        foreach (self::$head as $_head){
            self::$html .= $_head;
        }
        foreach (self::$styleCSS as $_css){
            self::$html .= self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_css));
        }
        foreach (self::$preScript as $_js){
            self::$html .= self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_js));
        }
        self::$html .= $html;
        foreach (self::$posScript as $_js){
            self::$html .= self::tagHTML('link', array('rel' => "stylesheet", 'href' => $_js));
        }

        self::showHTML($html);
    }

    /**
     * @param string $html
     * @return void
     */
    private static function showHTML(string $html): void
    {
        header("Content-Type: text/html");
        echo '<!DOCTYPE HTML><html>';

        ob_start();

        echo $html;
        echo '</html>';

        ob_flush();

        die;
    }

    /**
     * @param array $arr
     * @param string $value
     * @param string $label
     * @param string $all
     * @param string $selected
     * @return array
     */
    public static function buildOption(array $arr, string $value, string $label, string $all = '', string $selected = ''): array
    {
        $_line = [];
        if ($all <> '') {
            $_line[] = self::tagHTML('option', array('value' => $all), $all);
        }

        foreach ($arr as $ln) {
            if ($ln[$value] <> '') {
                if ($ln[$value] == $selected)
                    $_line[] = self::tagHTML('option', array('value' => $ln[$value], 'selected' => ''), $ln[$label]);
                else
                    $_line[] = self::tagHTML('option', array('value' => $ln[$value]), $ln[$label]);
            }
        }
        return $_line;
    }

    /**
     * @return string
     */
    public static function getHtml(): string
    {
        return self::$html;
    }

    /**
     * @return array
     */
    public static function getHead(): array
    {
        return self::$head;
    }

    /**
     * @return array
     */
    public static function getCSS(): array
    {
        return self::$styleCSS;
    }

    /**
     * @param string $type
     * @return bool|array
     */
    public static function getScript(string $type = 'pos'): bool|array
    {
        if ($type == 'pos') {
            return self::$posScript;
        }
        else if ($type == 'pre') {
            return self::$preScript;
        }
        return false;
    }
}
