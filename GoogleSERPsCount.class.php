<?php

    /**********************************************************************************
     *  Class: GoogleSERPsCount
     *  Description: Class for retrieve count of Google SERPs for specified domain
     *  Author: Vitaliy S. Orlov, orlov056@gmail.com, http://www.orlov.cv.ua
     *
     *  Usage:
     *  <?php
     *      require_once('GoogleSERPsCount.class.php');
     *      $gr = new \ua\cv\orlov\GoogleSERPsCount();
     *      echo '<pre>';
     *      print_r( $gr->get_res_count('www.microsoft.com') );
     *      echo '<hr>';
     *      print_r( $gr->get_errors() );
     *      echo '</pre>';
     *
     **********************************************************************************/

    namespace ua\cv\orlov;

    class GoogleSERPsCount
    {
        private $errors;
        private $config;

        public function __construct(array $config=array())
        {
            $this->load_default_configs();
            $this->set_configs($config);

            $this->clear_errors();
        }

        private function load_default_configs()
        {
            $this->config = array(
                'sleep_beetween_query' => 1,
                'curl_user_agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506; InfoPath.2; .NET CLR 1.1.4322)',
                'curl_connecttimeout' => 5,
                'curl_timeout' => 10,
                'curl_proxy' => '',
                'cache_allow' => FALSE,
                'cache_timeout' => 12*60*60,
                'cache_folder' =>dirname(__FILE__).'/',
            );
        }

        public function set_configs(array $config)
        {
            foreach ($config as $var=>$val)
            {
                $this->config[$var] = $val;
            }
        }

        public function get_res_count($domain)
        {
            $this->clear_errors();

            $domain = trim($domain);
            $domain = preg_replace('~^http[s]?://~i','',$domain);
            $domain = rtrim($domain,'/');

            $all = $primary = $supplement = 0;

            $all = $this->get_all_res_count($domain);

            if ($all)
            {
                $primary = $this->get_primary_res_count($domain);
                $supplement = $all - $primary;
                $supplement = $supplement<0 ? 0 : $supplement;
            }

            $ret = array(
                'all' => $all,
                'primary' => $primary,
                'supplement' => $supplement,
            );

            return $ret;
        }

        private function get_all_res_count($domain)
        {
            $query = 'site:'.$domain.'/';
            $ret = $this->get_res_by_query($query);
            return $ret;
        }

        private function get_primary_res_count($domain)
        {
            $query = 'site:'.$domain.'/&';
            $ret = $this->get_res_by_query($query);
            return $ret;
        }

        private function get_res_by_query($query)
        {
            $ret = 0;
            $url = 'http://www.google.com/search?hl=en&q='.urlencode($query);

            if ( $html = $this->www_get_cached($url) )
            {
                $ret = $this->parse_res_count_from_html($html);
            }
            else
            {
                $this->add_error('Can\'t get URL from www',
                                __METHOD__,
                                __LINE__
                );
            }
            return intval($ret);
        }

        private function www_get_cached($url)
        {
            if ($this->config['cache_allow'])
            {
                $marker = $this->cache_get_marker($url);
                if ($this->cache_exists($marker))
                {
                    return $this->cache_get($marker);
                }
            }

            $ret = $this->www_get($url);

            if ($this->config['cache_allow'] AND $ret)
            {
                $marker = $this->cache_get_marker($url);
                $this->cache_save($marker, $ret);
            }

            return $ret;
        }

        private function cache_get_marker($url)
        {
            return md5($url);
        }

        private function cache_exists($marker)
        {
            $file_path = $this->get_cache_file_path($marker);
            return file_exists($file_path) AND (filemtime($file_path)+$this->config['cache_timeout']) > time();
        }

        private function cache_get($marker)
        {
            $file_path = $this->get_cache_file_path($marker);
            return file_exists($file_path) ? file_get_contents($file_path) : FALSE;
        }

        private function cache_save($marker, $data)
        {
            $file_path = $this->get_cache_file_path($marker);
            return file_put_contents($file_path, $data);
        }

        private function get_cache_file_path($marker)
        {
            $ret = $this->config['cache_folder']
                   .$marker
                   .'.gr';
            return $ret;
        }

        private function www_get($url)
        {
            $ret = FALSE;

            if (!$ch = curl_init()) die("Couldn't initialize a cURL handle");

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['curl_timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->config['curl_connecttimeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->config['curl_user_agent']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if (!empty($this->config['curl_proxy']))
            {
                curl_setopt($ch, CURLOPT_PROXY, $this->config['curl_proxy']);
            }

            $ret = curl_exec($ch);

            if (curl_error($ch))
            {
                $this->add_error( curl_error($ch),
                                  __METHOD__,
                                  __LINE__
                );
            }
            else
            {
                if (!empty($this->config['sleep_beetween_query']))
                {
                    sleep($this->config['sleep_beetween_query']);
                }
            }

            curl_close($ch);

            return $ret;
        }

        private function parse_res_count_from_html($html)
        {
            $ret = 0;

            if (preg_match('~<div\s+id="resultStats">(.{1,500})</div>~Usix', $html, $regs))
            {
                $res = $regs[1];
                $res = preg_replace('~<nobr>.+</nobr>~i','',$res);
                $res = str_replace(',','',$res);
                $res = preg_replace('~\D+~','',$res);
                $ret = intval($res);
            }
            else
            {
                $this->add_error('Result attribute id ('.$res_attr_id.') not found',
                                 __METHOD__,
                                 __LINE__
                );
            }

            return $ret;
        }


        private function add_error($msg, $method, $line)
        {
            $this->errors[] = $msg.' ['.$method.':'.$line.']';
        }

        private function clear_errors()
        {
            $this->errors = array();
        }

        public function get_errors()
        {
            return $this->errors;
        }

        public function has_errors()
        {
            return !empty($this->errors);
        }


    } // end of class
