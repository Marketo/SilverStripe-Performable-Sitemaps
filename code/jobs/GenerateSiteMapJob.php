<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A job for generating a XML sitemap
 */
class GenerateSiteMapJob extends AbstractQueuedJob {
    /**
     * @var int
     * Rerun the job each day
     */
    private static $regenerate_time = 86400;

    public function __construct() {
        $this->currentStep = 0;
    }

    public function getJobType() {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return 'A job for generating a XML sitemap';
    }

    /**
     * Return a signature for this queued job
     *
     * @return string
     */
    public function getSignature() {
        return md5(get_class($this));
    }

    public function process() {
        $sitemap = singleton('SiteMapXMLController');
        $sitemap->generateSiteMap(ASSETS_PATH . '/sitemap.xml');
        $this->completeJob();
        return;
    }

    /**
     * Setup the next cron job
     */
    protected function completeJob() {
        $this->isComplete = true;
        $nextgeneration = new GenerateSiteMapJob();
        singleton('QueuedJobService')
            ->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
    }
}
