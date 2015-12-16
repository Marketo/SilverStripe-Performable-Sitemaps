<?php

class SiteMapPage extends SiteTree {
    
}

class SiteMapPage_Controller extends ContentController {
    /**
     * Returns content with the sitemap appended to it for a SiteMap page
     * @return ViewableData
     */
    public function index() {
        $results = array();

        $page = $this->data();
        $sitemap = $this->getHierarchicalSitemapHTML();
        $content = $this->owner->Content;

        return $this->customise(new ArrayData(array(
            'Content' => $content . $sitemap
        )))->renderWith('Page');
    }

    public function getHierarchicalSitemapHTML() {
        $all = array();
        $allids = array();
        $final = array();

        $siteData = singleton('SiteMapDataService');
        $public = $siteData->getNodes();
        foreach ($public as $row) {
            if (!isset($allids[$row['ID']])) {
                $allids[$row['ID']] = true;
                $all[] = $row;
            }
        }

        $siteData->processRows($final, $all, $allids, 0);
        foreach ($final as $id => $page) {
            if (!isset($page[$id]) && $id > 0) {
                $siteData->allPages[$id] = $page;
            }
        }
        $sitemapXML = new SimpleXMLElement('<div></div>');
        $sitemapXML->addAttribute('id', 'SitemapList');
        foreach ($siteData->allPages as $id => $page) {
            $sitemapXML = $this->processPageToHierarchicalHTML($sitemapXML, $page);
        }
        return $sitemapXML->asXML();
    }

    protected function processPageToHierarchicalHTML($xml, $page) {
        $siteData = singleton('SiteMapDataService');
        if (isset($page['ID']) && !isset($siteData->processed[$page['ID']])) {
            // add stuff
            $ul = $xml->addChild('ul');
            $li = $ul->addChild('li');
            $li->a = $page['Title'];
            $li->a->addAttribute('href', $page['URLSegment']);
            // check if they have children
            if (isset($page['kids'])) {
                foreach ($page['kids'] as $index => $subPage) {
                    if (isset($siteData->allPages[$subPage])) {
                        $this->processPageToHierarchicalHTML(
                            $li,
                            $siteData->allPages[$subPage]
                        );
                    }
                }
            }
            // add page id to processed
            $siteData->processed[$page['ID']] = true;
        }
        return $xml;
    }
}
