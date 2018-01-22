<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AuProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some plugin properties.
 * 
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadAuProperty extends Fixture implements DependentFixtureInterface {
    
    public function load(ObjectManager $em) {
        $property1 = new AuProperty();
        $property1->setPropertyKey("test_1");
        $property1->setPropertyValue("Test Property");
        $property1->setAu($this->getReference('au.1'));
        $em->persist($property1);
        
        $property2 = new AuProperty();
        $property2->setPropertyKey("test_property");
        $property2->setPropertyValue("Test Property Again!?");
        $property2->setAu($this->getReference('au.1'));
        $em->persist($property2);
        
        $property3 = new AuProperty();
        $property3->setPropertyKey("test_property_other");
        $property3->setPropertyValue("mares eat oats");
        $property3->setAu($this->getReference('au.1'));
        $em->persist($property3);
        
        $property4 = new AuProperty();
        $property4->setPropertyKey("test_parent");
        $property4->setAu($this->getReference('au.1'));
        $em->persist($property4);
        
        $property4a = new AuProperty();
        $property4a->setPropertyKey("test_child_1");
        $property4a->setPropertyValue("Bobby Tables");
        $property4a->setAu($this->getReference('au.1'));
        $property4a->setParent($property4);
        $em->persist($property4a);
        
        $property4b = new AuProperty();
        $property4b->setPropertyKey("test_child_2");
        $property4b->setPropertyValue("mary and jane");
        $property4b->setAu($this->getReference('au.1'));
        $property4b->setParent($property4);
        $em->persist($property4b);
        
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadAu::class,
        ];
    }

}