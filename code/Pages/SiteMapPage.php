<?php

class SiteMapPage extends Page
{
}

class SiteMapPage_Controller extends Page_Controller
{
    /**
     * Returns content with the sitemap appended to it for a SiteMap page
     * @return ViewableData
     */
    public function index()
    {
        $results = array();

        $page = $this->data();
        $sitemap = $this->getHierarchicalSitemapHTML();

		return array('Content' => str_replace("<?xml version=\"1.0\"?>\n", '', $sitemap));
		
    }

    public function getHierarchicalSitemapHTML() {
        $all = array();
        $allids = array();
        $final = array();
        
		$page = $this->data();
        $siteData = $page->siteData ? $page->siteData : singleton('SiteDataService');
		
		$items = $siteData->getItems();
		
		// get just the top level ones; ie parentID = 0;
		
		$topLevel = array();
		foreach ($items as $item) {
			if ($item->ParentID == 0 && $item->ShowInMenus) {
				$topLevel[] = $item;
			}
		}
		
		$sitemapXML = new SimpleXMLElement('<div></div>');
        $sitemapXML->addAttribute('id', 'SitemapList');
		
		foreach ($topLevel as $item) {
			$sitemapXML = $this->processPageToHierarchicalHTML($sitemapXML, $item);
		}
		
		return $sitemapXML->asXML();
    }

	protected $processed = array();

    protected function processPageToHierarchicalHTML($xml, $page) {
        if ($page->ID && !isset($this->processed[$page->ID])) {
            // add stuff
            $ul = $xml->addChild('ul');
            $li = $ul->addChild('li');
            $li->a = $page->Title;
            $li->a->addAttribute('href', $page->Link);
            // check if they have children
			$kids = $page->Children();
			
            if (count($kids)) {
                foreach ($kids as $subPage) {
                    $this->processPageToHierarchicalHTML(
						$li,
						$subPage
					);
                }
            }
            // add page id to processed
            $this->processed[$page->ID] = true;
        }
        return $xml;
    }
}
