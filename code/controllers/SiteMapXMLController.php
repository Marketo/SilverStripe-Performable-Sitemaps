<?php

/**
 * Redirect xml sitemap requests
 *
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 */
class SiteMapXMLController extends Controller
{
    private static $allowed_actions = [
        'index',
    ];

    private static $base_url = '';

    private static $match_host = '';

    private static $allowed_sub_hosts = [];

    private static $sitemap_name = 'sitemap.xml';

    private static $protocol = 'https';

    /**
     * @param SS_HTTPRequest $request
     */
    public function index(SS_HTTPRequest $request)
    {
        $sitemap = ASSETS_PATH . '/' . $this->config()->sitemap_name;
        $subhosts = $this->config()->allowed_sub_hosts;
        $matchHost = $this->config()->match_host;

        $generateAsHost = null;

        if (is_array($subhosts) && in_array($_SERVER['HTTP_HOST'], $subhosts) ||
            $matchHost && substr($_SERVER['HTTP_HOST'], -strlen($matchHost)) == $matchHost) {
            $generateAsHost = $_SERVER['HTTP_HOST'];
            $sitemap = ASSETS_PATH . '/' . $generateAsHost . '.' . $this->config()->sitemap_name;
        }

        if (file_exists($sitemap) && !isset($_GET['flush'])) {
            $now = time();
            $fileAge = filemtime($sitemap);
            $age = ($now - $fileAge);
            if ($age > 86400) {
                unlink($sitemap);
                $this->generateSiteMap($sitemap, $generateAsHost);
            }
        } else {
            $this->generateSiteMap($sitemap, $generateAsHost);
        }
        header("Content-Type:text/xml");
        readfile($sitemap);
        exit;
    }

    /**
     * @param string $sitemap SiteMap File
     * @param string $siteURL Base URL
     */
    public function generateSiteMap($sitemap = 'sitemap.xml', $siteURL = null)
    {
        $observeMenu = Config::inst()->get('SiteMap', 'ObserveShowInMenus');
        $observeSearch = Config::inst()->get('SiteMap', 'ObserveShowInSearch');
        /** @var SiteDataService $siteData */
        $siteData = singleton('SiteDataService');
        $siteData->additionalFields[] = 'ShowInSearch';
        $pages = $siteData->getItems();

        $xml = new SimpleXMLElement('<urlset></urlset>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute(
            'xsi:schemaLocation',
            'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
        );

        if (!$siteURL) {
            $siteURL = self::config()->get('base_url');
            if (!$siteURL) {
                $siteURL = Director::absoluteBaseURL();
            }
        }

        $siteURL = rtrim($siteURL, '/') . '/';

        if (!strpos($siteURL, ':/')) {
            $siteURL = $this->config()->protocol . '://' . $siteURL;
        }

        /** @var DataObjectNode $page */
        foreach ($pages as $page) {
            if ($observeSearch && !$page->ShowInSearch) {
                continue;
            }
            if ($observeMenu && !$page->ShowInMenus) {
                continue;
            }
            $url = $xml->addChild('url');
            $url->addChild('loc', $siteURL . $page->Link);
            $url->addChild('changefreq', $page->ChangeFreq);
            $url->addChild('priority', $page->Priority);
        }

        file_put_contents($sitemap, $xml->asXML());
    }
}
