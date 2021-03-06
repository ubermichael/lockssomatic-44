<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use App\Entity\Box;
use Exception;
use Laminas\Soap\Client;

/**
 * Class LockssClient.
 *
 * @see https://docs.laminas.dev/laminas-soap/client/
 */
class LockssClient {

    /**
     * @var array
     */
    private $options;

    /**
     * @var Box
     */
    private $box;

    private function __construct() {
    }

    /**
     * @param string $service
     *
     * @return LockssClient
     */
    public static function create(Box $box) {
        $lockssClient = new LockssClient();
        $lockssClient->box = $box;
        $lockssClient->options = [
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
            'encoding' => 'utf-8',
            'soap_version' => SOAP_1_1,
        ];

        return $lockssClient;
    }

    public function generateWsdl($name) {
        return "http://{$this->box->getHostname()}:{$this->box->getWebservicePort()}/ws/{$name}?wsdl";
    }

    public function getOption($name) {
        if (isset($this->options[$name])) {
            return $name;
        }
        return null;
    }

    public function setOption($name, $value) : void {
        $this->options[$name] = $value;
    }

    public function isDaemonReady() {
        $wsdl = $this->generateWsdl('DaemonStatusService');
        $client = new Client($wsdl, $this->options);
        return $client->isDaemonReady();
    }

    public function call($method, $params = [], $serviceName = 'DaemonStatusService') {
        $readyResponse = $this->isDaemonReady();
        if (true !== $readyResponse->return) {
            throw new Exception("Daemon on {$this->box->getHostname()} reports not ready.");
        }

        $wsdl = $this->generateWsdl($serviceName);
        $client = new Client($wsdl, $this->options);
        try {
            return $client->{$method}($params);
        } catch(Exception $e) {
            throw new Exception("Calling $method in $wsdl threw error: {$e->getMessage()}");
        }
    }
}
