<?php
class Bgprocess {
    
    function __construct()
    {
        $this->ci =& get_instance();
    }

    function do_async($url, $params)
    {
        try {
            $post_string = '';
            foreach ($params as $key => $value):
                $post_string .= $key . '='.json_encode($value).'&'; 
            endforeach;
            rtrim($post_string, '&');
    
            $parts = parse_url($url);

            /**
             * use ssl & port 443 for secure servers
             * user otherwise for localhost and non-secure servers
             */
            if (ENVIRONMENT == 'production') {
                // for secure server
                $fp = fsockopen('ssl://' . $parts['host'], isset($parts['port']) ? $parts['port'] : 443, $errno, $errstr, 30);
            } else {
                // for localhost and non-secure server
                $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
            }
    
            $out = "POST ".$parts['path']." HTTP/1.1\r\n";
            $out.= "Host: ".$parts['host']."\r\n";
            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out.= "Content-Length: ".strlen($post_string)."\r\n";
            $out.= "Connection: Close\r\n\r\n";
    
            if (isset($post_string)) 
                $out.= $post_string;
    
            fwrite($fp, $out);
            fclose($fp);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
