<?php

class SiteMapPage extends SiteTree
{
}

class SiteMapPage_Controller extends ContentController
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
        $content = $this->owner->Content;

        return $this->customise(new ArrayData(array(
            'Content' => str_replace("<?xml version=\"1.0\"?>\n", '', $sitemap)
        )))->renderWith('Page');
    }

    public function getHierarchicalSitemapHTML() {
        $all = array();
        $allids = array();
        $final = array();
        
		$page = $this->data();
        $siteData = $page->siteData ? $page->siteData : singleton('SiteMapDataService');
		
		$items = $siteData->getItems();
		
		// get just the top level ones; ie parentID = 0;
		
		$topLevel = array();
		foreach ($items as $item) {
			if ($item->ParentID == 0) {
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
