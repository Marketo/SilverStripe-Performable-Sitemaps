<?php

/**
 * Redirect xml sitemap requests
 * 
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 */
class SiteMapXMLController extends Controller
{
    private static $allowed_actions = array(
        'index'
    );

    public function index(SS_HTTPRequest $request)
    {
        $url = $request->getVar('url');
        $sitemap = ASSETS_PATH . $url;
        if (file_exists($sitemap) && !isset($_GET['flush'])) {
            $now = time();
            $fileAge = filemtime($sitemap);
            $age = ($now - $fileAge);
            if ($age > 86400) {
                unlink($sitemap);
                $this->generateSiteMap($sitemap);
            }
        } else {
            $this->generateSiteMap($sitemap);
        }
        header("Content-Type:text/xml");
        readfile($sitemap);
        exit;
    }

    public function generateSiteMap($sitemap = 'sitemap.xml')
    {
        $siteData = singleton('SiteMapDataService');
        $pages = $siteData->getItems();

        $xml = new SimpleXMLElement('<urlset></urlset>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute(
            'xsi:schemaLocation',
            'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
        );
        $siteURL = Director::absoluteBaseURL();
        foreach ($pages as $page) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $siteURL . $page->Link);
            $url->addChild('changefreq', $page->ChangeFreq);
            $url->addChild('priority', $page->Priority);
        }

        file_put_contents($sitemap, $xml->asXML());
    }
}
