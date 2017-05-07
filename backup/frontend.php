<?php

class HYFlickrFrontend {

	private $phpFlickr;
	private $options;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStylesheets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
		add_shortcode( 'hyflickr', array( $this, 'shortcode' ) );

	}

	function enqueueStylesheets() {
		wp_enqueue_style( 'hyflickr-style', plugins_url( 'style.css', __FILE__ ) );
		wp_enqueue_style( 'hyflickr-swipebox-style', plugins_url( 'fancybox/jquery.fancybox.min.css', __FILE__ ) );
	}

	function enqueueScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'afg_swipebox_script', plugins_url( "fancybox/jquery.fancybox.min.js", __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'afg_swipebox_js', plugins_url( "script.js", __FILE__ ), array( 'jquery' ) );
	}

	function initFlickr() {
		global $wpdb;
		$this->options   = get_option( HYFlickrOptions::NAME );
		$this->phpFlickr = new phpFlickr( $this->options[ HYFlickrOptions::API_KEY ], $this->options[ HYFlickrOptions::API_SECRET ] ? $this->options[ HYFlickrOptions::API_SECRET ] : null );
		$this->phpFlickr->setToken( $this->options[ HYFlickrOptions::API_TOKEN ] ? $this->options[ HYFlickrOptions::API_TOKEN ] : "" );
		$connectionStr = sprintf( "mysql://%s:%s@%s/%s", DB_USER, DB_PASSWORD, DB_HOST, DB_NAME );
		$this->phpFlickr->enableCache("db", $connectionStr, 900, $wpdb->prefix . "hyflickr_cache");
	}

	function shortcode() {
		$this->initFlickr();

		if ( isset( $_GET['photoset'] ) ) {
			return $this->showAlbumDetail( $_GET['photoset'] );
		} else {
			return $this->showAlbums();
		}
	}

	function showAlbums() {
		$page     = isset( $_GET['hyflickr_page'] ) ? $_GET['hyflickr_page'] : 1;
		$response = $this->phpFlickr->photosets_getList( $this->options[ HYFlickrOptions::API_USER ], $page, 20 );

		$returnVal = '<ul class="hyflickr-gallery hyflickr-albums">';
		foreach ( $response['photoset'] as $photoset ) {
			$photo       = $photoset;
			$photo['id'] = $photo['primary'];
			$photoUrl    = $this->phpFlickr->buildPhotoURL( $photo, 'small' );
			$title       = $photoset['title']['_content'];
			$url         = add_query_arg( 'photoset', $photoset['id'] );

			$returnVal .= '<li><a href="' . $url . '">';
			$returnVal .= '<div class="overlay"><span class="title">' . $title . '</span></div>';
			$returnVal .= '<img src="' . $photoUrl . '" />';
			$returnVal .= '</a></li>';

		}
		
		$returnVal .= '<div class="clear"></div>';

		$returnVal .= $this->generatePagination( $response['page'], $response['pages'] );
		
		$returnVal .= '<div class="clear"></div>';

		return $returnVal;
	}

	function showAlbumDetail( $photosetId ) {
		$page      = isset( $_GET['hyflickr_page'] ) ? $_GET['hyflickr_page'] : 1;

		$photoset = $this->phpFlickr->photosets_getPhotos( $photosetId, 'original_format', null, 20, $page )['photoset'];

		$photos    = $photoset['photo'];
		$returnVal = '<h2 class="hyflickr-title">' . $photoset['title'] . '</h2>';
		$returnVal .= '<a class="hyflickr-back" href=' . remove_query_arg(array('hyflickr_page', 'photoset')) . '>Terug naar overzicht</a>';
		
		$returnVal .= '<div class="clear"></div>';
		
		$returnVal .= '<ul class="hyflickr-gallery hyflickr-albums">';
		foreach ( $photos as $photo ) {
			$downloadPhotoUrl    = $this->phpFlickr->buildPhotoURL( $photo, 'original' );
			$largePhotoUrl = $this->phpFlickr->buildPhotoURL( $photo, 'large_1600' );
			$photoUrl      = $this->phpFlickr->buildPhotoURL( $photo, 'small' );

			$returnVal .= '<li><a href="' . $largePhotoUrl . '" data-download="' . $downloadPhotoUrl . '" data-fancybox="gallery" class="gallery-box">';
			$returnVal .= '<div class="overlay"></div>';
			$returnVal .= '<img src="' . $photoUrl . '" />';
			$returnVal .= '</a></li>';
		}
		$returnVal .= '</ul>';
		
		$returnVal .= '<div class="clear"></div>';

		$returnVal .= $this->generatePagination( $photoset['page'], $photoset['pages'] );
		
		$returnVal .= '<div class="clear"></div>';

		return $returnVal;
	}

	function generatePagination( $current, $total ) {
		$min       = $current - 3 < 1 ? 1 : $current - 3;
		if ($current - 3 < 1) {
            $min       = 1;
            $max       = min($total - 1, 7);
        } else if ($current + 3 > $total) {
            $min       = max($total - 6, 1);
            $max       = $total;
		} else {
    		$min       = $current - 3;
    		$max       = $current + 3 > $total ? $total : $current + 3;
		}
		
		$returnVal = '<ul class="hyflickr-pagination">';
		for ( $i = $min; $i <= $max; $i ++ ) {
			$url = add_query_arg( 'hyflickr_page', $i );
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


}