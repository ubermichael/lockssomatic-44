<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deposit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some deposits.
 */
class LoadDeposit extends Fixture implements DependentFixtureInterface {

    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     */
    public function load(ObjectManager $em) {
        $deposit1 = new Deposit();
        $deposit1->setUuid('3E40ACE2-7F1A-4AD5-8622-416EC740D9A1');
        $deposit1->setTitle('Deposit 1');
        $deposit1->setContentProvider($this->getReference('provider.1'));
        $deposit1->setUrl('http://example.com/path/to/first');
        $deposit1->setSize(100);
        $deposit1->setChecksumType('sha1');
        $deposit1->setChecksumValue('abc123');
        $deposit1->setAu($this->getReference('au.1'));
        $deposit1->setProperty('title', 'Title 1');
        $deposit1->setProperty('publisher', 'Publisher');
        $this->setReference('deposit.1', $deposit1);
        $em->persist($deposit1);

        $deposit2 = new Deposit();
        $deposit2->setUuid('3E40ACE2-7F2A-4AD5-8622-426EC740D9A2');
        $deposit2->setTitle('Deposit 2');
        $deposit2->setContentProvider($this->getReference('provider.1'));
        $deposit2->setUrl('http://example.com/path/to/second');
        $deposit2->setSize(200);
        $deposit2->setChecksumType('sha1');
        $deposit2->setChecksumValue('abc223');
        $deposit2->setAu($this->getReference('au.1'));
        $deposit2->setProperty('title', 'Title 2');
        $deposit2->setProperty('publisher', 'Publisher');
        $this->setReference('deposit.2', $deposit2);
        $em->persist($deposit2);

        $deposit3 = new Deposit();
        $deposit3->setUuid('3E40ACE3-7F3A-4AD5-8633-436EC740D9A3');
        $deposit3->setTitle('Deposit 3');
        $deposit3->setContentProvider($this->getReference('provider.1'));
        $deposit3->setUrl('http://example.com/path/to/mars');
        $deposit3->setSize(300);
        $deposit3->setChecksumType('sha1');
        $deposit3->setChecksumValue('abc323');
        $deposit3->setAu($this->getReference('au.1'));
        $deposit3->setProperty('title', 'Title 3');
        $deposit3->setProperty('publisher', 'Publisher');
        $this->setReference('deposit.3', $deposit3);
        $em->persist($deposit3);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadContentProvider::class,
            LoadAu::class,
        ];
    }

}
