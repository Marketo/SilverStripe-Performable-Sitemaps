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
        $siteData = $page->siteData ? $page->siteData : singleton('SiteMapDataService');
        $sitemap = $siteData->getHierarchicalHTMLList();
        $content = $this->owner->Content;

        return $this->customise(new ArrayData(array(
            'Content' => $content . $sitemap
        )))->renderWith('Page');
    }
}
