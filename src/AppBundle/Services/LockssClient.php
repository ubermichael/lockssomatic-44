<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapCommon\Cache;
use BeSimple\SoapCommon\Helper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ReflectionClass;

/**
 * Description of LockssClient
 */
class LockssClient {

    /**
     * Default options for SOAP clients.
     */
    const SOAP_OPTS = array(
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => Cache::TYPE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => true,
        'exceptions' => true,
        'user_agent' => 'LOCKSSOMatic 1.0',
        'authentication' => SOAP_AUTHENTICATION_BASIC,
    );

    const GUZZLE_OPTS = array(
        'allow_redirects' => true,
        'headers' => array(
            'User-Agent' => 'LOCKSSOMatic 1.0; http://pkp.sfu.ca',
        ),
        'decode_content' => false,
    );

    // getAuStatus
    // isDaemonReady
    // queryRepositories
    // queryRepositorySpaces
    const STATUS_SERVICE = 'ws/DaemonStatusService?wsdl';
    // hash
    const HASHER_SERVICE = 'ws/HasherService?wsdl';
    // isUrlCached
    // fetchFile
    const CONTENT_SERVICE = 'ws/ContentService?wsdl';

    /**
     * @var AuIdGenerator
     */
    private $auManager;

    /**
     * @var array
     */
    private $errors;

    public function __construct(AuManager $auManager) {
        $this->auManager = $auManager;
        $this->errors = array();
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->errors[] = implode(':', ['Error', $errstr]);
    }

    public function exceptionHandler(Exception $e) {
        $reflection = new ReflectionClass($e);
        $this->errors[] = implode(':', [$reflection->getShortName(), $e->getCode(), $e->getMessage()]);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearErrors() {
        $this->errors = array();
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }

    public function call(Box $box, $service, $method, $params = array(), $soapOptions = array()) {
        set_error_handler(array($this, 'errorHandler'), E_ALL);
        set_exception_handler(array($this, 'exceptionHandler'));

        $wsdl = "{$box->getWebServiceProtocol()}://{$box->getIpAddress()}:{$box->getWebServicePort()}/{$service}";
        $options = array_merge(self::SOAP_OPTS, $soapOptions, array(
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
        ));

        $client = null;
        $response = null;
        try {
            $client = @new SoapClient($wsdl, $options);
            if ($client) {
                $response = $client->$method($params);
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
        restore_error_handler();
        set_error_handler('var_dump', 0);
        @trigger_error('');

        restore_error_handler();
        restore_exception_handler();
        if ($response) {
            return $response->return;
        }
        return null;
    }

    public function isDaemonReady(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'isDaemonReady');
    }

    public function getAuStatus(Box $box, Au $au) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->fromAu($au);
        return $this->call($box, self::STATUS_SERVICE, 'getAuStatus', array(
                    'auId' => $auid,
        ));
    }

    public function getAuUrls(Box $box, Au $au) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->fromAu($au);
        return $this->call($box, self::STATUS_SERVICE, 'getAuUrls', array(
                    'auId' => $auid,
        ));
    }

    public function queryRepositories(Box $box) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        return $this->call($box, self::STATUS_SERVICE, 'queryRepositories', array(
                    'repositoryQuery' => 'SELECT *',
        ));
    }

    public function queryRepositorySpaces(Box $box) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        return $this->call($box, self::STATUS_SERVICE, 'queryRepositorySpaces', array(
                    'repositorySpaceQuery' => 'SELECT *',
        ));
    }

    /**
     * Fetches the hash of a content URL from a box.
     *
     * May return null if the item hasn't been preserved or if the box isn't
     * responding.
     *
     * @param Box $box
     * @param Deposit $deposit
     * @return string|null
     */
    public function hash(Box $box, Deposit $deposit) {
        if( ! $this->isUrlCached($box, $deposit)) {
            return;
        }
        $auid = $this->auManager->fromAu($deposit->getAu(), true);
        $response = $this->call($box, self::HASHER_SERVICE, 'hash', array(
                    'hasherParams' => array(
                        'recordFilterStream' => true,
                        'hashType' => 'V3File',
                        'algorithm' => $deposit->getChecksumType(),
                        'url' => $deposit->getUrl(),
                        'auId' => $auid,
                    ),
        ));

        $block = $response->blockFileDataHandler;
        $lines = array_values(array_filter(explode("\n", $block), function($s){
            return strlen($s) > 0 && $s[0] !== '#';
        }));
        if(count($lines) !== 1) {
            return null;
        }
        list($checksum, $url) = preg_split("/\s+/", $lines[0]);

        return strtoupper($checksum);
    }

    public function isUrlCached(Box $box, Deposit $deposit) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->fromAu($deposit->getAu());
        return $this->call($box, self::CONTENT_SERVICE, 'isUrlCached', array(
                    'url' => $deposit->getUrl(),
                    'auId' => $auid,
                        ), array(
                    'attachment_type' => Helper::ATTACHMENTS_TYPE_MTOM,
        ));
    }

    /**
     * Download a content item from a lockss box.
     *
     * This can't use the normal SOAP api because the SOAP libraries all
     * try to store the data in memory rather than streaming it to a temporary
     * file.
     *
     * @param Box $box
     * @param Deposit $deposit
     * @return resource
     */
    public function fetchFile(Box $box, Deposit $deposit) {
        if (!$this->isDaemonReady($box)) {
            return;
        }

        $client = new Client();
        $baseUrl = "http://{$box->getHostname()}:{$box->getPln()->getContentPort()}/ServeContent";
        $fh = tmpfile();
        $options = array_merge(self::GUZZLE_OPTS, array(
            'query' => [
                'url' => $deposit->getUrl()
            ],
        ));

        $response = $client->get($baseUrl, $options);
        $body = $response->getBody();
        while(($data = $body->read(64 * 1024))) {
            fwrite($fh, $data);
        }
        rewind($fh);
        return $fh;
    }

}
