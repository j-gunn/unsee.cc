<?php

class Unsee_Hash extends Unsee_Redis
{

    public static $_ttlTypes = array(-1 => 'now', 0 => 'first', 3600 => 'hour', 86400 => 'day', 604800 => 'week');

    public function __construct($key = null)
    {

        if (empty($key)) {
            $key = (string) (new Unsee_Hash_String());
        }

        parent::__construct($key);

        $this->timestamp = time();
        $this->ttl = self::$_ttlTypes[0];
        $this->views = 0;
        $this->strip_exif = 1;
        $this->comment = Zend_Registry::get('config')->image_comment;
        $this->sess = $this->getCurrentSession();
    }

    public function getImages()
    {
        // read files in directory
        $storage = Zend_Registry::get('config')->storagePath;
        $files = glob($storage . $this->key . '/*');
        $imageDocs = array();
        foreach ($files as $file) {
            $imageDocs[] = new Unsee_Image(basename($file));
        }

        return $imageDocs;
    }

    private function getCurrentSession()
    {
        return md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }

    public function isOwner()
    {
        return $this->getCurrentSession() === $this->sess;
    }

    public function isViewable()
    {
        if ($this->ttl === 'first' && !$this->views) {
            // Single-view image hasn't been viewed yet
            return true;
        } elseif ($this->getTtlSeconds() > 0) {
            // Long-living image, still not outdated
            return true;
        } else {
            // Dead
            return false;
        }
    }

    public function getTtlSeconds()
    {
        // Converting ttl into strtotime acceptable string
        switch ($this->ttl) {
            // Date in past for right now
            case 'now':
                $ttl = '-1 day';
                break;
            // Delete on first view, use zero
            case 'first':
                return false;
            // almost strtotime-ready otherwise (time value)
            default:
                $ttl = '+1 ' . $this->ttl;
                break;
        }

        // Get time to die
        return strtotime($ttl, $this->timestamp) - time();
    }

    public function getTtlWords()
    {
        $secondsLeft = $this->getTtlSeconds();
        $lang = Zend_Registry::get('Zend_Translate');

        if ($secondsLeft < 60) {
            return $lang->translate('moment');
        }

        $times = array();
        $timeStrings = array();
        $foundNonEmpty = false;

        $times['day'] = strtotime('+1 day', 0);
        $times['hour'] = $times['day'] / 24;
        $times['minute'] = $times['hour'] / 60;

        foreach ($times as $timeFrame => &$seconds) {
            // Days/hours/minutes left
            $itemsLeft = floor($secondsLeft / $seconds);

            // Recalculate number of seconds left - minus seconds in current day/hour/minute
            $secondsLeft -= $itemsLeft * $seconds;
            // Trying to translate the number correctly
            $modRes = $itemsLeft % 10;
            if ($modRes === 1) {
                $timeFrame .= '_one';
            } elseif ($modRes > 1 && $modRes < 5) {
                $timeFrame .= '_couple';
            } else {
                $timeFrame .= '_many';
            }

            if ($itemsLeft || $foundNonEmpty) {
                $foundNonEmpty = true;
                $timeStrings[] = $itemsLeft . ' ' . $lang->translate($timeFrame);
            }
        }

        // Use last element anyway
        $deleteTime = array_pop($timeStrings);

        // If it's not the only one - prepend others
        if ($timeStrings) {
            $deleteTime = implode(', ', $timeStrings) . ' ' . $lang->translate('and') . ' ' . $deleteTime;
        }

        return $deleteTime;
    }
}
