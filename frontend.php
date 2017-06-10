<?php

class HYFlickrFrontend
{

    private $phpFlickr;
    private $options;
    const PERMALINK = "fotos/";
    const PAGE_ID = 12923;

    public function __construct()
    {
        add_action('init', array($this, 'addRewriteTags'), 10, 0);
        add_action('init', array($this, 'addRewriteRules'), 10, 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueueStylesheets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_shortcode('hyflickr', array($this, 'shortcode'));
    }

    function enqueueStylesheets()
    {
        wp_enqueue_style('hyflickr-style', plugins_url('res/style.css', __FILE__));
        wp_enqueue_style('hyflickr-swipebox-style', plugins_url('res/jquery.fancybox.min.css', __FILE__));
    }

    function enqueueScripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('afg_swipebox_script', plugins_url("res/jquery.fancybox.min.js", __FILE__), array('jquery'));
        wp_enqueue_script('afg_swipebox_js', plugins_url("res/script.js", __FILE__), array('jquery'));
    }

    function initFlickr()
    {
        global $wpdb;
        $this->options = get_option(HYFlickrOptions::NAME);
        $this->phpFlickr = new phpFlickr($this->options[HYFlickrOptions::API_KEY], $this->options[HYFlickrOptions::API_SECRET] ? $this->options[HYFlickrOptions::API_SECRET] : null);
        $this->phpFlickr->setToken($this->options[HYFlickrOptions::API_TOKEN] ? $this->options[HYFlickrOptions::API_TOKEN] : "");
        $connectionStr = sprintf("mysql://%s:%s@%s/%s", DB_USER, DB_PASSWORD, DB_HOST, DB_NAME);
        $this->phpFlickr->enableCache("db", $connectionStr, 1800, $wpdb->prefix . "hyflickr_cache");
    }

    function addQueryVars($vars)
    {
        $vars[] = "hyflickr_page";
        $vars[] = "hyflickr_photoset";
        return $vars;
    }

    function addRewriteTags()
    {
        add_rewrite_tag('%hyflickr_page%', '([0-9]{1,10})');
        add_rewrite_tag('%hyflickr_photoset%', '([^&]+)');
    }

    function addRewriteRules()
    {
        add_rewrite_rule('^' . self::PERMALINK . 'album-([^/]*)/([^/]*)/?', 'index.php?page_id=' . self::PAGE_ID . '&hyflickr_page=$matches[2]&hyflickr_photoset=$matches[1]', 'top');
        add_rewrite_rule('^' . self::PERMALINK . 'album-([^/]*)/?', 'index.php?page_id=' . self::PAGE_ID . '&hyflickr_photoset=$matches[1]', 'top');
        add_rewrite_rule('^' . self::PERMALINK . '([^/]*)/?', 'index.php?page_id=' . self::PAGE_ID . '&hyflickr_page=$matches[1]', 'top');
    }

    function shortcode()
    {
        $this->initFlickr();

        if (get_query_var('hyflickr_photoset')) {
            return $this->showAlbumDetail(get_query_var('hyflickr_photoset'));
        }
        return $this->showAlbums();
    }

    function showAlbums()
    {
        $page = get_query_var('hyflickr_page') ? get_query_var('hyflickr_page') : 1;
        $response = $this->phpFlickr->photosets_getList($this->options[HYFlickrOptions::API_USER], $page, 16);
        
        if ($response['pages'] > 1 && $response['perpage'] > count($response['photoset'])) {
            $response['pages'] = 1;
            $response['total'] = count($response['photoset']);
        }

        $returnVal = '<ul class="hyflickr-gallery hyflickr-albums">';
        foreach ($response['photoset'] as $photoset) {
            $photo = $photoset;
            $photo['id'] = $photo['primary'];
            $photoUrl = $this->phpFlickr->buildPhotoURL($photo, 'small');
            $title = $photoset['title']['_content'];
            $url = $url = $this->constructUrl(array('album-' . $photoset['id']));

            $returnVal .= '<li><a href="' . $url . '">';
            $returnVal .= '<div class="overlay"><span class="title">' . $title . '</span></div>';
            $returnVal .= '<img src="' . $photoUrl . '" />';
            $returnVal .= '</a></li>';
        }
        $returnVal .= '</ul>';

        $returnVal .= '<div class="clear"></div>';

        $returnVal .= $this->generatePagination($response['page'], $response['pages']);

        $returnVal .= '<div class="clear"></div>';

        return $returnVal;
    }

    function showAlbumDetail($photosetId)
    {
        global $wp;
        $page = get_query_var('hyflickr_page') ? get_query_var('hyflickr_page') : 1;

        $photoset = $this->phpFlickr->photosets_getPhotos($photosetId, 'original_format', null, 20, $page)['photoset'];

        $photos = $photoset['photo'];
        $returnVal = '<h2 class="hyflickr-title">' . $photoset['title'] . '</h2>';
        $returnVal .= '<a class="hyflickr-back" href="/' . self::PERMALINK . '">Terug naar overzicht</a>';

        $returnVal .= '<div class="clear"></div>';

        $returnVal .= '<ul class="hyflickr-gallery hyflickr-albums">';
        if ($photos) {
            foreach ($photos as $photo) {
                $downloadPhotoUrl = $this->phpFlickr->buildPhotoURL($photo, 'original');
                $largePhotoUrl = $this->phpFlickr->buildPhotoURL($photo, 'large_1600');
                $photoUrl = $this->phpFlickr->buildPhotoURL($photo, 'small');

                $returnVal .= '<li><a href="' . $largePhotoUrl . '" data-download="' . $downloadPhotoUrl . '" data-fancybox="gallery" class="gallery-box">';
                $returnVal .= '<div class="overlay"></div>';
                $returnVal .= '<img src="' . $photoUrl . '" />';
                $returnVal .= '</a></li>';
            }
        }
        $returnVal .= '</ul>';

        $returnVal .= '<div class="clear"></div>';

        $returnVal .= $this->generatePagination($photoset['page'], $photoset['pages']);

        $returnVal .= '<div class="clear"></div>';

        return $returnVal;
    }

    private function generatePagination($current, $total)
    {
        if ($current - 3 < 1) {
            $min = 1;
            $max = min($total, 7);
        } else if ($current + 3 > $total) {
            $min = max($total - 6, 1);
            $max = $total;
        } else {
            $min = $current - 3;
            $max = $current + 3 > $total ? $total : $current + 3;
        }

        $returnVal = '<ul class="hyflickr-pagination">';
        for ($i = $min; $i <= $max; $i++) {
            if (get_query_var('hyflickr_photoset')) {
                $url = $this->constructUrl(array('album-' . get_query_var('hyflickr_photoset'), $i));
            } else {
                $url = $this->constructUrl(array($i));
            }
            if ($i == $current) {
                $returnVal .= '<li class="current"><a href="#">';
                $returnVal .= $i;
                $returnVal .= '</a></li>';
            } else {
                $returnVal .= '<li><a href="' . $url . '">';
                $returnVal .= $i;
                $returnVal .= '</a></li>';
            }
        }
        $returnVal .= '</ul>';

        return $returnVal;
    }

    private function constructUrl($params = array()) {
        $url = self::PERMALINK;
        foreach ($params as $param) {
            $url .= $param . "/";
        }
        return "/" . $url;
    }

}