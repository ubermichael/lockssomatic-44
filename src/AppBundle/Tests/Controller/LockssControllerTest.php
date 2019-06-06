<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadAu;
use AppBundle\DataFixtures\ORM\LoadBox;
use AppBundle\DataFixtures\ORM\LoadContentOwner;
use AppBundle\DataFixtures\ORM\LoadContentProvider;
use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\DataFixtures\ORM\LoadPlugin;
use AppBundle\Entity\Box;
use AppBundle\Entity\Pln;
use AppBundle\Services\FilePaths;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LockssControllerTest extends BaseTestCase {

    public function getFixtures() {
        return [
            LoadPln::class,
            LoadBox::class,
            LoadPlugin::class,
            LoadContentOwner::class,
            LoadContentProvider::class,
            LoadAu::class,
        ];
    }

    protected function setup() : void {
        parent::setUp();
    }

    public function testLockss() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testLockss404() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testLockssBadIp() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        $client = $this->makeClient([], [
            'REMOTE_ADDR' => '11.0.0.0',
        ]);
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testLockssGoodIp() {
        $box = new Box();
        $box->setHostname('bananarama');
        $box->setIpAddress('10.0.0.12');
        $box->setProtocol('TCP');
        $box->setPort('1234');
        $box->setWebServicePort('11234');
        $box->setSendNotifications(false);
        $box->setActive(false);
        $box->setPln($this->em->find(Pln::class, 1));
        $this->em->persist($box);
        $this->em->flush();

        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        $client = $this->makeClient([], [
            'REMOTE_ADDR' => '10.0.0.12',
        ]);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testTitleDb() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getTitleDbPath')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/titledbs/1/1/titledb_1.xml');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testTitleDb404() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getTitleDbPath')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/titledbs/1/1/titledb_1.xml');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testManifest() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getManifestPath')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/manifests/1/1/manifest_1.html');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testManifest404() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getManifestPath')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);
        $client->request('GET', '/plnconfigs/1/manifests/1/1/manifest_1.html');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testKeystore() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.keystore');
        file_put_contents($url, $data);

        $pln = $this->em->find(Pln::class, 1);
        $pln->setKeystore($url); // anything works here.
        $this->em->flush();

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn(dirname($url));

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);

        $client->request('GET', '/plnconfigs/1/plugins/lockssomatic.keystore');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/x-java-keystore', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testKeystore404Null() {
        $pln = $this->em->find(Pln::class, 1);
        $pln->setKeystore(null);
        $this->em->flush();

        $client = $this->makeClient();
        $client->request('GET', '/plnconfigs/1/plugins/lockssomatic.keystore');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPluginList() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.html');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsManifestFile')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);

        $client->request('GET', '/plnconfigs/1/plugins/index.html');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testPluginList404() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.html');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsManifestFile')->willReturn($url);

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);

        $client->request('GET', '/plnconfigs/1/plugins/index.html');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPlugin() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.jar');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn('vfs://root');

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);

        $client->request('GET', '/plnconfigs/1/plugins/dummy.jar');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/java-archive', $response->headers->get('Content-Type', null, true));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(strlen($data), $response->getFile()->getSize());
        $this->assertEquals($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testPlugin404() {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.jar');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn('vfs://root');

        $client = $this->makeClient();
        $client->getContainer()->set(FilePaths::class, $fp);

        $client->request('GET', '/plnconfigs/1/plugins/dummy.jar');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

}
