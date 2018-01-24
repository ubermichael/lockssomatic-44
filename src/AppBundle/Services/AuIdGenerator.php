<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Utilities\Encoder;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Description of AuIdGenerator
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuIdGenerator {
    
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function fromContent(Content $content) {
        $plugin = $content->getPlugin();
        if($plugin === null) {
            return null;
        }
        $pluginId = $plugin->getIdentifier();
        $id = str_replace('.', '|', $pluginId);
        $names = $plugin->getDefinitionalProperties();
        sort($names);
        $encoder = new Encoder();
        foreach($names as $name) {
            $value = $content->getProperty($name);
            if( ! $value) {
                throw new Exception("Cannot generate AUID without definitional property {$name}.");
            }
            $id .= '&' . $name . '~' . $encoder->encode($value);
        }
        
        return $id;
    }
    
    public function fromAu(Au $au) {
        $content = $au->getContent()->first();
        if($content === null) {
            return null;
        }
        return $this->fromContent($content);
    }
    
}