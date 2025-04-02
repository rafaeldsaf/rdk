<?php

namespace RDK;

class Basic
{
    private static float $endTime;
    private static float $iniTime;

    /**
     * @param string $string
     * @param string $case
     * @return string
     */
    static public function cleanAccent(string $string, string $case = ''): string
    {
        $string = str_replace(array('á', 'à', 'ä', 'ã', 'â'), "a", $string);
        $string = str_replace(array('Á', 'À', 'Ä', 'Ã', 'Â'), "A", $string);

        $string = str_replace(array('é', 'è', 'ë', 'ê'), "e", $string);
        $string = str_replace(array('É', 'È', 'Ë', 'Ê'), "E", $string);

        $string = str_replace(array('í', 'ì', 'ï', 'î'), "i", $string);
        $string = str_replace(array('Í', 'Ì', 'Ï', 'Î'), "I", $string);

        $string = str_replace(array('ó', 'ò', 'ö', 'õ', 'ô'), "o", $string);
        $string = str_replace(array('Ó', 'Ò', 'Ö', 'õ', 'Ô'), "O", $string);

        $string = str_replace(array('ú', 'ù', 'ü', 'û'), "u", $string);
        $string = str_replace(array('Ú', 'Ù', 'Ü', 'Û'), "U", $string);

        $string = str_replace(array('ç'), "c", $string);
        $string = str_replace(array('Ç'), "C", $string);
        $string = str_replace(array(''), "C", $string);

        if ($case == 'up') {
            return strtoupper($string);
        } else if ($case == 'low') {
            return strtolower($string);
        }
        return $string;
    }

    /**
     * @param $url
     * @param $dir
     * @param string $name
     * @return false|string
     */
    static public function downloadFile(string $url, string $dir, string $name = ''): bool|string
    {
        if ($name == '') {
            $name = uniqid(rand(), true) . '.tmp';
        }
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                return false;
            }
        }
        ini_set('user_agent', "PHP\r\nX-MyCustomHeader: Foo");
        $data = file_get_contents($url);
        if (file_put_contents($dir . $name, $data) === false) {
            return false;
        }
        return $dir . $name;
    }

    /**
     * @param string $ip
     * @return array|false
     */
    static public function geoCheck(string $ip): false|array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $result = self::curlCall('http://ip-api.com/json/' . $ip);
        $output = json_decode($result);
        return (array)$output;
    }

    /**
     * @param string $url
     * @param array|bool $options
     * @param string $ret
     * @param bool $debug
     * @return bool|mixed|string
     */
    static public function curlCall(string $url, array|bool $options = false, string $ret = '', bool $debug = false): mixed
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        if ($debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        } else {
            curl_setopt($ch, CURLOPT_VERBOSE, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        if ($options['httpheader'] <> '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['httpheader']);
        }
        if ($options['userpwd'] <> '') {
            $tipo = CURLAUTH_BASIC;
            if ($options['userpwd']['tipo'] == 'digest') {
                $tipo = CURLAUTH_DIGEST;
            }
            curl_setopt($ch, CURLOPT_HTTPAUTH, $tipo);
            curl_setopt($ch, CURLOPT_USERPWD, "{$options['userpwd']['user']}:{$options['userpwd']['pass']}");
        }
        if ($options['sslcert'] <> '') {
            curl_setopt($ch, CURLOPT_SSLCERT, $options['sslcert']['publicKey']);
            curl_setopt($ch, CURLOPT_SSLKEY, $options['sslcert']['privateKey']);
            curl_setopt($ch, CURLOPT_CAINFO, $options['sslcert']['chainKeys']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        if ($options['post'] <> '') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post']);
        }
        if ($options['custom'] <> '') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['custom']);
        }
        if ($debug) {
            $verbose = fopen($GLOBALS['path'] . 'log/curl.log', 'a+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }
        $result = curl_exec($ch);

        if ($result === false) {
            return false;
        }
        if ($ret == 'http_code') {
            $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param string $dir
     * @return bool
     */
    static public function setPath(string $dir): bool
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                return false;
            }
        }
        $GLOBALS['path'] = $dir;
        return true;
    }

    /**
     * @param array|string $_msg
     * @param string $_arq
     * @return bool
     */
    static public function logFile(array|string $_msg, string $_arq = ''): bool
    {
        if ($_arq == '') {
            $_arq = date('Y-m-d') . '.log';
        }

        if (!is_dir("{$GLOBALS['path']}log")) {
            if (!mkdir("{$GLOBALS['path']}log")) {
                return false;
            }
        }
        if (is_array($_msg)) {
            $_msg = json_encode($_msg);
        }

        if (!file_put_contents("{$GLOBALS['path']}log/" . $_arq, "[" . date('Y-m-d H:i:s') . "] {$_msg}" . PHP_EOL, FILE_APPEND)) {
            return false;
        }
        return true;
    }

    /**
     * @param array $hours
     * @return string
     */
    static public function sumHours(array $hours): string
    {
        $h = 0;
        $m = 0;
        $s = 0;
        foreach ($hours as $hour) {
            if (!empty($hour)) {
                $hms = explode(':', $hour);
                $h += $hms[0];
                $m += $hms[1];
                $s += $hms[2];
                if ($s >= 60) {
                    $s = $s - 60;
                    $m += 1;
                }
                if ($m >= 60) {
                    $m = $m - 60;
                    $h += 1;
                }
            }
        }
        return self::zeroLeft($h, 2) . ':' . self::zeroLeft($m, 2) . ':' . self::zeroLeft($s, 2);
    }

    /**
     * @param string $hours
     * @return string
     */
    static public function subHora(string $hours): string
    {
        $h = 0;
        $m = 0;
        $s = 0;
        foreach ($hours as $hour) {
            $hms = explode(':', $hour);
            if ($h == 0) {
                $h = $hms[0];
            } else {
                $h -= $hms[0];
            }

            if ($m == 0) {
                $m = $hms[1];
            } else {
                $m -= $hms[1];
            }

            if ($s == 0) {
                $s = $hms[2];
            } else {
                $s -= $hms[2];
            }
            if ($s < 0) {
                $s = 60 + $s;
                $m -= 1;
            }
            if ($m < 0) {
                $m = 60 + $m;
                $h -= 1;
            }
        }
        return self::zeroLeft($h, 2) . ':' . self::zeroLeft($m, 2) . ':' . self::zeroLeft($s, 2);
    }

    /**
     * @param string $value
     * @param int $qtd
     * @param string $str
     * @param int $type
     * @return string
     */
    static private function valPad(string $value, int $qtd, string $str = '0', int $type = STR_PAD_LEFT): string
    {
        return str_pad($value, $qtd, $str, $type);
    }

    /**
     * @param string $value
     * @param int $qtd
     * @return string
     */
    static public function zeroLeft(string $value, int $qtd): string
    {
        return self::valPad($value, $qtd, '0');
    }

    /**
     * @param string $value
     * @param int $qtd
     * @return string
     */
    static public function zeroRight(string $value, int $qtd): string
    {
        return self::valPad($value, $qtd, '0', STR_PAD_RIGHT);
    }

    /**
     * @param string $hour1
     * @param string $hour2
     * @return string
     */
    static public function percHour(string $hour1,string $hour2): string
    {
        $h1 = explode(':', $hour1);
        $h2 = explode(':', $hour2);

        $result = @number_format(((($h1[0] * 60) + $h1[1]) / (($h2[0] * 60) + $h2[1])) * 100, 2, '.', '');

        return $result;
    }

    /**
     * @param string $url
     * @param int $timeout
     * @param bool $ssl
     * @return false|mixed
     */
    static public function checkURL(string $url,int $timeout = 25,bool $ssl = true)
    {
        if ($url == "") {
            return false;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $ssl);

        curl_exec($curl);  // Executa a sessão do cURL
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl); // Fecha a sessão do cURL

        return $status;

        $ativo = array('200', '301');
        if (in_array($status, $ativo)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $ip
     * @param int $port
     * @return bool
     */
    static public function checkPort(string $ip,int $port): bool
    {
        if ($pf = @fsockopen($ip, $port, $err, $err_string, 1)) {
            fclose($pf);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $value
     * @param string $input
     * @param string $output
     * @param bool $arr
     * @return array|string
     */
    static public function ConvertDate(string $value,string  $input,string $output,bool $arr = false): array|string
    {
        if (($input == "Y-m-d H:i:s") or ($input == "Y-m-d")) {
            $value = explode(' ', $value);
            $_date = $value[0];
            $_hour = $value[1];

            $_date = explode('-', $_date);
            $_ano = $_date[0];
            $_mes = $_date[1];
            $_dia = $_date[2];
        } else if (($input == "Y-m-dTH:i:sZ") or ($input == "Y-m-dTH:i:s")) {
            $value = str_replace('Z', '', $value);
            $value = explode('T', $value);
            $_date = $value[0];
            $_hour = $value[1];

            $_date = explode('-', $_date);
            $_ano = $_date[0];
            $_mes = $_date[1];
            $_dia = $_date[2];
        } else if (($input == "d/m/Y H:i:s") or ($input == "d/m/Y")) {
            $value = explode(' ', $value);
            $_date = $value[0];
            $_hour = $value[1];

            $_date = explode('/', $_date);
            $_ano = $_date[2];
            $_mes = $_date[1];
            $_dia = $_date[0];
        } else if ($input == 'H:m:i às d/m/Y') {
            $value = explode(' às ', $value);
            $_date = $value[1];
            $_hour = $value[0];
            $_date = explode('/', $_date);
            $_ano = $_date[2];
            $_mes = $_date[1];
            $_dia = $_date[0];
        }
        else{
            return false;
        }

        $_result['year'] = $_ano;
        $_result['month'] = $_mes;
        $_result['day'] = $_dia;
        $_result['hour'] = $_hour;
        $_result['formated'] = date($output, strtotime("{$_ano}-{$_mes}-{$_dia} {$_hour}"));

        if ($arr == true) {
            return $_result;
        } else {
            return $_result['formated'];
        }
    }

    /**
     * @param string $date1
     * @param string $date2
     * @param string $format
     * @return float|int|string
     */
    static public function diffDate(string $date1,string $date2,string $format = 'd'): float|int|string
    {
        $d1 = strtotime($date1);
        $d2 = strtotime($date2);

        if ($format == 'd') {
            $result = ($d2 - $d1) / 86400;
        } else if ($format == 'm:s') {
            $result = ($d2 - $d1);
            $h = floor($result / 3600);
            $m = floor(($result - ($h * 3600)) / 60);
            $s = floor($result % 60);
            $result = str_pad($h, 2, '0', STR_PAD_LEFT) . ":" . str_pad($m, 2, '0', STR_PAD_LEFT);
        }

        if ($result < 0)
            $result = $result * -1;

        return $result;
    }

    static function sizeFile($size)
    {
        $m = array(' KB', ' MB', ' GB', ' TB');

        if ($size < 999) {
            $size = 1000;
        }

        for ($i = 0; $size > 999; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $m[$i - 1];
    }

    /**
     * @param string $path
     * @param string $ext
     * @param string $search
     * @return array|false
     */
    static public function listFiles(string $path,string $ext,string $search = ''): false|array
    {
        if (!is_dir($path)) {
            return false;
        }
        $_files = [];
        if ($_handle = opendir($path)) {
            while ($entry = readdir($_handle)) {
                $e = pathinfo($entry, PATHINFO_EXTENSION);
                if (strtolower($ext) == strtolower($e)) {
                    if ($search <> '') {
                        if (strpos($entry, $search) === false) {
                            continue;
                        }
                    }

                    $_stat = stat($path . '/' . $entry);
                    $_files[] = array(
                        'file' => $path . '/' . $entry,
                        'name' => $entry,
                        'size' => self::sizeFile($_stat['size']),
                        'bytes' => $_stat['size'],
                        'date' => date('d/m/Y H:i', $_stat['mtime']),
                    );
                }
            }
            closedir($_handle);
        }

        return $_files;
    }

    /**
     * @param $num
     * @param int $qtd
     * @param string $pre
     * @param string $dec
     * @param string $mil
     * @return string
     */
    static public function numberFormat($num, $qtd = 2, $pre = 'R$', $dec = ',', $mil = '.'): string
    {
        if ((!is_numeric($num)) or ($num == '') or (is_nan($num))) {
            $num = 0;
        }
        return $pre . number_format($num, $qtd, $dec, $mil);
    }

    /**
     * @return string
     */
    static public function keyGen()
    {
        return strtoupper(bin2hex(random_bytes(20)));
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    static function getNum($str): array|string|null
    {
        return preg_replace("/[^0-9]/", "", $str);
    }

    static function cleanArr($_arr)
    {
        return array_filter($_arr, fn($value) => !is_null($value) && $value !== '');
    }

    /**
     * @param bool $end
     * @return string|true
     */
    static function timerCount(bool $end = false): true|string
    {

        if ($end === false) {
            self::$iniTime = microtime(true);
            return true;
        } else {
            self::$endTime = microtime(true);
            $duration = self::$endTime - self::$iniTime;

            $hours = (int)($duration / 60 / 60);
            $minutes = (int)($duration / 60) - $hours * 60;
            $seconds = (int)$duration - $hours * 60 * 60 - $minutes * 60;

            return self::zeroLeft($hours, 2) . ':' . self::zeroLeft($minutes, 2) . ':' . self::zeroLeft($seconds, 2);
        }
    }

    /**
     * @param string $host
     * @param int $tout
     * @return bool
     */
    static public function pingHost(string $host,int $tout = 2): bool
    {
        exec("ping -c $tout $host", $arr, $_output);

        if ($_output == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $value
     * @param $privatekey
     * @param $publickey
     * @param $_encript
     * @return false|string
     */
    public static function encryptVal($value,$privatekey,$publickey,bool $_encript = true): false|string
    {

        $key = hash('sha256', $privatekey);
        $iv = substr(hash('sha256', $publickey), 0, 16);
        if ($_encript === true) {
            $output = openssl_encrypt($value, "AES-256-CBC", $key, 0, $iv);
            $output = base64_encode($output);
        } else {
            $output = openssl_decrypt(base64_decode($value), "AES-256-CBC", $key, 0, $iv);
        }
        return $output;
    }
}
