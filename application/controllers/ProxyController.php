<?php

class ProxyController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->getHelper('viewRenderer')->setNoRender();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        // On forme la chaine de paramètres
        $params = '';
        foreach ($this->_request->getParams() as $key => $value) {
            if (!in_array($key, ['url', 'controller', 'action', 'module'])) {
                $params .= $key.'='.str_replace(' ', '+', $value).'&';
            }
        }

        if ('' !== $params && '0' !== $params) {
            $params = '?'.$params;
        }

        // Website url to open
        $daurl = $this->_request->url.$params;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $daurl);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);

        // are we under a proxy?
        if (1 == getenv('PREVARISC_PROXY_ENABLED')) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, getenv('PREVARISC_PROXY_PROTOCOL'));
            curl_setopt($ch, CURLOPT_PROXYPORT, (int) getenv('PREVARISC_PROXY_PORT'));
            curl_setopt($ch, CURLOPT_PROXY, getenv('PREVARISC_PROXY_HOST'));
            if (getenv('PREVARISC_PROXY_USERNAME')) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, getenv('PREVARISC_PROXY_USERNAME').':'.getenv('PREVARISC_PROXY_PASSWORD'));
            }
        }

        $data = curl_exec($ch);

        if (false === $data) {
            $body = curl_error($ch);
        } else {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($data, 0, $header_size);
            $headers = explode("\r\n", $header);
            $body = substr($data, $header_size);

            curl_close($ch);

            foreach ($headers as $header) {
                if (
                    $header
                    && 0 !== preg_match('/^Content-Type/i', $header)
                ) {
                    $this->_response->setRawHeader($header);
                }
            }
        }

        $this->_response->setBody($body);
    }
}
