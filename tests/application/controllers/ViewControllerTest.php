<?php

class ViewControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();
    }

    private function addHash($imagesNum = 1)
    {
        $hash = new Unsee_Hash();

        for ($x = 1; $x <= $imagesNum; $x++) {
            $image = new Unsee_Image();
            $image->setFile(TEST_DATA_PATH . '/images/good/1mb.jpg');
            $hash->addImage($image);
            unset($image);
        }

        $hash->save();

        return $hash;
    }

    public function testViewOwner($numImages = 1)
    {
        $hash = $this->addHash($numImages);
        try {
            $this->dispatch('/view/index/hash/' . $hash->hash . '/');
        } catch (Exception $e) {
            print_r($e);
            die();
        }

        $this->assertResponseCode(200);
        $this->assertController('view');
        $this->assertXpathCount('//img[contains(@src,"/image/")]', $numImages);

        return $hash;
    }

    public function testViewOwnerMulti()
    {
        return $this->testViewOwner(3);
    }

    public function testViewAnon()
    {
        $hash = $this->testViewOwner();

        $this->setUp();
        $_SERVER['HTTP_USER_AGENT'] = 'anonymous';

        $this->dispatch('/view/index/hash/' . $hash->hash . '/');
        $this->assertController('view');
        $this->assertResponseCode(200);
        return $hash;
    }

    public function testDeleted()
    {
        $hash = $this->testViewAnon();
        $this->dispatch('/view/index/hash/' . $hash->hash . '/');
        $this->assertResponseCode(310);
        $this->assertController('view');
    }

    public function testImageOutput()
    {
        $hash = $this->testViewAnon();
        $this->dispatch('/view/index/hash/' . $hash->hash . '/');
        $this->assertResponseCode(310);
        $this->assertController('view');
    }

    public function testNoExif()
    {
        // TOOD: Implement
        // die(1);
    }
}
