<?php

namespace SitemapPHP;

/**
 * Sitemap
 *
 * This class used for generating Google Sitemap files
 *
 * @package    Sitemap
 * @author     Osman Üngür <osmanungur@gmail.com>
 * @copyright  2009-2011 Osman Üngür
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    Version @package_version@
 * @since      Class available since Version 1.0.0
 * @link       http://github.com/osmanungur/sitemap-php
 */
class Sitemap {

    /**
     * @var \XMLWriter
     */
    private $writer;
    private $path;
    private $filename           = 'sitemap';
    private $indexFilename      = 'sitemaps';
    private $current_item       = 0;
    private $current_sitemap    = 0;
    private $sitemapFiles       = array();
    private $patternFile        = 'sitemap_%s';

    /**
     * Keeps track of external sitemaps that need to be added in the main sitemap file.
     *
     * @var array
     */
    private $externalSitemaps   = [];

    const EXT               = '.xml';
    const SCHEMA            = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const DEFAULT_PRIORITY  = 0.5;
    const ITEM_PER_SITEMAP  = 50000;
    const SEPARATOR         = '_';
    const INDEX_SUFFIX      = 'index';

    /**
     * Returns XMLWriter object instance
     *
     * @return \XMLWriter
     */
    private function getWriter()
    {
        return $this->writer;
    }

    /**
     * Assigns XMLWriter object instance
     *
     * @param \XMLWriter $writer
     */
    private function setWriter(\XMLWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Returns path of sitemaps
     *
     * @return string
     */
    private function getPath()
    {
        return $this->path;
    }

    /**
     * Sets paths of sitemaps
     *
     * @param string $path
     * @return Sitemap
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns filename of sitemap file
     *
     * @return string
     */
    private function getFilename()
    {
        return $this->filename;
    }

    /**
     * Sets filename of sitemap file
     *
     * @param string $filename
     * @return Sitemap
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Returns filename of sitemap index file
     *
     * @return string
     */
    private function getIndexFilename()
    {
        return $this->indexFilename;
    }

    /**
     * Sets filename of sitemap index file
     *
     * @param $indexFilename
     * @return Sitemap
     */
    public function setIndexFilename($indexFilename)
    {
        $this->indexFilename = $indexFilename;
        return $this;
    }

    /**
     * Returns pattern of the sitemaps files
     *
     * @return string
     */
    public function getPatternFile()
    {
        return $this->patternFile;
    }

    /**
     * Sets pattern of the sitemaps files
     *
     * @param $patternFile
     * @return $this
     */
    public function setPatternFile($patternFile)
    {
        $this->patternFile = $patternFile;

        return $this;
    }

    /**
     * Returns current item count
     *
     * @return int
     */
    private function getCurrentItem()
    {
        return $this->current_item;
    }

    /**
     * Resets the current item value
     */
    private function resetCurrentItem()
    {
        $this->current_item = 0;
    }

    /**
     * Increases item counter
     *
     */
    private function incCurrentItem()
    {
        $this->current_item = $this->current_item + 1;
    }

    /**
     * Returns current sitemap file count
     *
     * @return int
     */
    private function getCurrentSitemap()
    {
        return $this->current_sitemap;
    }

    /**
     * Increases sitemap file count
     *
     */
    private function incCurrentSitemap()
    {
        $this->current_sitemap = $this->current_sitemap + 1;
    }

    /**
     * Resets the current sitemap value
     */
    private function resetCurrentSitemap()
    {
        $this->current_sitemap = 0;
    }

    /**
     * Adds a new file to the list of sitemaps files
     *
     * @param $fileName
     */
    private function addSitemapFile($fileName)
    {
        $this->sitemapFiles[] = $fileName;
    }

    /**
     * @param string $location
     */
    public function addExternalSitemap($location)
    {
        $this->externalSitemaps[] = $location;
    }

    /**
     * Prepares sitemap XML document
     */
    public function startSitemap()
    {
        $this->setWriter(new \XMLWriter());
        $this->getWriter()->openURI($this->getFile());
        $this->getWriter()->startDocument('1.0', 'UTF-8');
        $this->getWriter()->setIndent(true);
        $this->getWriter()->startElement('urlset');
        $this->getWriter()->writeAttribute('xmlns', self::SCHEMA);
    }

    /**
     * Retrieves current file
     * @return string
     */
    private function getFile()
    {
        return $this->getPath() . $this->getFilename() . self::SEPARATOR . $this->getCurrentSitemap() . self::EXT;
    }

    /**
     * Adds an item to sitemap
     *
     * @param string $loc URL of the page. This value must be less than 2,048 characters.
     * @param float|string $priority The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
     * @param string $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
     * @param string|int $lastmod The date of last modification of url. Unix timestamp or any English textual datetime description.
     * @return Sitemap
     */
    public function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL)
    {
        // First file
        if ($this->getCurrentItem() == 0) {
            $this->startSitemap();
            // End file because of limit size
        } else if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
            $this->endSitemap();
            $this->incCurrentSitemap();
            $this->startSitemap();
        }
        $this->incCurrentItem();

        // URL element
        $this->getWriter()->startElement('url');
        $this->getWriter()->writeElement('loc', $loc);
        $this->getWriter()->writeElement('priority', $priority);
        if ($changefreq) {
            $this->getWriter()->writeElement('changefreq', $changefreq);
        }
        if ($lastmod) {
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
        }
        $this->getWriter()->endElement();

        return $this;
    }

    /**
     * Prepares given date for sitemap
     *
     * @param string $date Unix timestamp or any English textual datetime description
     * @return string Year-Month-Day formatted date.
     */
    private function getLastModifiedDate($date)
    {
        if (!ctype_digit($date)) {
            $date = strtotime($date);
        }
        return date('Y-m-d', $date);
    }

    /**
     * Finalizes tags of sitemap XML document.
     */
    private function endSitemap()
    {
        if ($this->getWriter() instanceof \XMLWriter) {
            $this->getWriter()->endElement();
            $this->getWriter()->endDocument();
            $this->addSitemapFile($this->getFilename() . self::SEPARATOR . $this->getCurrentSitemap());
        }
    }

    /**
     * Forces to end a sitemap file and resets counters
     */
    public function forceEndSitemap()
    {
        $this->endSitemap();
        $this->resetCurrentItem();
        $this->resetCurrentSitemap();
    }

    /**
     * Writes Google sitemap index for generated sitemap files
     *
     * @param string $loc Accessible URL path of sitemaps
     * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
     */
    public function createSitemapIndex($loc, $lastmod = 'Today')
    {
        // Close last file
        $this->endSitemap();

        // Writer for the index file
        $indexwriter = new \XMLWriter();
        $indexwriter->openURI($this->getPath() . $this->getIndexFilename() . self::EXT);
        $indexwriter->startDocument('1.0', 'UTF-8');
        $indexwriter->setIndent(true);
        $indexwriter->startElement('sitemapindex');
        $indexwriter->writeAttribute('xmlns', self::SCHEMA);

        // Loop over the files
        $files = glob($this->getPath() . $this->getPatternFile() . '*.xml'); // get all sitemaps with the pattern

        $lastModified = $this->getLastModifiedDate($lastmod);

        foreach($files as $file) {
            $location = $loc . str_replace($this->getPath(), '', $file);
            $this->writeSitemap($indexwriter, $location, $lastModified);
        }

        foreach ($this->externalSitemaps as $externalSitemap) {
            $this->writeSitemap($indexwriter, $externalSitemap, $lastModified);
        }

        $indexwriter->endElement();
        $indexwriter->endDocument();
    }

    /**
     * @param \XmlWriter $indexwriter
     * @param string     $location
     * @param string     $lastModified
     */
    private function writeSitemap(\XmlWriter $indexwriter, $location, $lastModified)
    {
        $indexwriter->startElement('sitemap');
        $indexwriter->writeElement('loc', $location);
        $indexwriter->writeElement('lastmod', $lastModified);
        $indexwriter->endElement();
    }
}
