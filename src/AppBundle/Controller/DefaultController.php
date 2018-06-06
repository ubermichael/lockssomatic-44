<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Default controller for LOCKSSOMatic. Totally open to the public.
 */
class DefaultController extends Controller {

    /**
     * LOCKSSOMatic home page.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction(Request $request) {
        return [];
    }
    
}
