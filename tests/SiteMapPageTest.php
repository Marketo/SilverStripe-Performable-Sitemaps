<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * Some basic testing for the SiteMapPage
 */

class  SiteMapPageTest extends SapphireTest {

	protected static $fixture_file = 'SiteMapPageTest.yml';

    public function setup() {
        parent::setup();

        $this->loadFixture('SilverStripe-Performable-Sitemaps/tests/SiteMapPageTest.yml');
    }

    public function testPage() {
		$page = $this->objFromFixture('SiteMapPage', 'Page');

        $this->assertEquals($page->Title, 'SiteMap');
    }
}
