<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * Some controller testing
 */

class  SiteMapControllerTest extends FunctionalTest {

    private $sitemap = 'sitemap-test.xml';

    public function setUp() {
        parent::setUp();
        for ($i=0; $i<100; $i++) {
            $page = new Page(
                array(
                    'title' => "Page $i"
                )
            );
            $page->write();
            $page->publish('Stage', 'Live');
        }
        $sitemap = new SiteMapPage();
        $sitemap->Title = 'SiteMap';
        $sitemap->write();
    }

    public function tearDown() {
        parent::tearDown();
        if (file_exists(ASSETS_PATH . "/{$this->sitemap}")) {
            unlink(ASSETS_PATH . "/{$this->sitemap}");
        }

        $pages = Page::get();
        foreach ($pages as $page) {
            $page->delete();
        }
        $pages = SiteMapPage::get();
        foreach ($pages as $page) {
            $page->delete();
        }
    }

    public function testSiteMapGeneration() {
        $sitemap = ASSETS_PATH . "/{$this->sitemap}";
        $controller = new SiteMapXMLController();
        $controller->generateSiteMap($sitemap);

        $contents = file_get_contents($sitemap);
        $found = strpos($contents, 'urlset');
        $this->assertnotNull($found);

        $xml = new SimpleXMLElement($contents);
        $this->assertEquals(count($xml->url), 101);

        $this->assertTrue(file_exists($sitemap));
    }

    public function testHTMLGeneration() {
        $controller = new SiteMapPage_Controller();
        $html = $controller->getHierarchicalSitemapHTML();

        $found = strpos($html, '<div>');
        $this->assertnotNull($found);

        $found = strpos($html, '<li>');
        $this->assertnotNull($found);
    }
}
