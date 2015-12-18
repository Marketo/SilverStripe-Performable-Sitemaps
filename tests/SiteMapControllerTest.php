<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * Some controller testing
 */

class  SiteMapControllerTest extends FunctionalTest {

    private $sitemap = 'sitemap-test.xml';

    public function tearDown() {
        parent::tearDown();
        if (file_exists(ASSETS_PATH . "/{$this->sitemap}")) {
            unlink(ASSETS_PATH . "/{$this->sitemap}");
        }
    }

    public function testSiteMapGeneration() {
        $sitemap = ASSETS_PATH . "/{$this->sitemap}";
        $controller = new SiteMapXMLController();
        $controller->generateSiteMap($sitemap);

        $this->assertTrue(file_exists($sitemap));
    }
}
