<?php
/**
 * Site Class
 *
 * @package Timber
 */

namespace Timber;

use Timber\Core;
use Timber\CoreInterface;

use Timber\Theme;
use Timber\Helper;

/**
 * TimberSite gives you access to information you need about your site.
 * In Multisite setups, you can get info on other sites in your network.
 *
 * @example
 * ```php
 * $context = Timber::get_context();
 * $other_site_id = 2;
 * $context['other_site'] = new TimberSite($other_site_id);
 * Timber::render('index.twig', $context);
 * ```
 * ```twig
 * My site is called {{site.name}}, another site on my network is {{other_site.name}}
 * ```
 * ```html
 * My site is called Jared's blog, another site on my network is Upstatement.com
 * ```
 * @see Core
 * @internal CoreInterface
 */
class Site extends Core implements CoreInterface {

	/**
	 * The admin email address set in the WP admin panel (Settings > General)
	 *
	 * @api
	 * @var string the admin email address set in the WP admin panel
	 */
	public $admin_email;

	/**
	 * Site title (set in Settings > General)
	 *
	 * @var string
	 */
	public $blogname;

	/**
	 *  The "Encoding for pages and feeds"  (set in Settings > Reading)
	 *
	 * @api
	 * @var string
	 */
	public $charset;

	/**
	 * Site tagline (set in Settings > General)
	 *
	 * @api
	 * @var string
	 */
	public $description;

	/**
	 *  The blog id
	 *
	 * @api
	 * @var int the ID of a site in multisite
	 */
	public $id;

	/**
	 * Language code for the current site
	 *
	 * @api
	 * @var string the language setting ex: en-US
	 */
	public $language;

	/**
	 * The site is multisite.
	 *
	 * @api
	 * @var bool true if multisite, false if plain ole' WordPress
	 */
	public $multisite;

	/**
	 *  Site title (set in Settings > General)
	 *
	 * @api
	 * @var string
	 */
	public $name;

	/**
	 * The pingback XML-RPC file URL (xmlrpc.php)
	 *
	 * @api
	 * @var string for people who like trackback spam
	 */
	public $pingback_url;

	/**
	 * The Site address (URL) (set in Settings > General)
	 *
	 * @var string
	 */
	public $siteurl;

	/**
	 * The theme
	 *
	 * @api
	 * @var [TimberTheme](#TimberTheme)
	 */
	public $theme;

	/**
	 * Site title (set in Settings > General)
	 *
	 * @api
	 * @var string
	 */
	public $title;

	/**
	 * The Site address (URL) (set in Settings > General)
	 *
	 * @var string
	 */
	public $url;

	/**
	 * The Site address (URL) (set in Settings > General)
	 *
	 * @var string
	 */
	public $home_url;

	/**
	 * The WordPress address (URL) (set in Settings > General)
	 *
	 * @var string
	 */
	public $site_url;

	/**
	 * The RDF/RSS 1.0 feed URL (/feed/rfd)
	 *
	 * @api
	 * @var string
	 */
	public $rdf;

	/**
	 * The RSS 0.92 feed URL (/feed/rss)
	 *
	 * @api
	 * @var string
	 */
	public $rss;

	/**
	 * The RSS 2.0 feed URL (/feed)
	 *
	 * @api
	 * @var string
	 */
	public $rss2;

	/**
	 * Atom feed URL
	 *
	 * @api
	 * @var string
	 */
	public $atom;

	/**
	 * Constructs a TimberSite object
	 *
	 * @example
	 * ```php
	 * //multisite setup
	 * $site = new TimberSite(1);
	 * $site_two = new TimberSite("My Cool Site");
	 * //non-multisite
	 * $site = new TimberSite();
	 * ```
	 * @param string|int $site_name_or_id A blog ID or a blog slug.
	 */
	public function __construct( $site_name_or_id = null ) {
		if ( is_multisite() ) {
			$blog_id = self::switch_to_blog($site_name_or_id);
			$this->init();
			$this->init_as_multisite($blog_id);
			restore_current_blog();
		} else {
			$this->init();
			$this->init_as_singlesite();
		}
	}

	/**
	 * Switches to the blog requested in the request
	 *
	 * @param string|integer|null $site_name_or_id A blog ID or a blog slug.
	 * @return integer with the ID of the new blog
	 */
	protected static function switch_to_blog( $site_name_or_id ) {
		if ( null === $site_name_or_id ) {
			$site_name_or_id = get_current_blog_id();
		}
		$info = get_blog_details($site_name_or_id);
		switch_to_blog($info->blog_id);
		return $info->blog_id;
	}

	/**
	 * Executed for multi-blog sites
	 *
	 * @internal
	 * @param integer $site_id A blog ID.
	 */
	protected function init_as_multisite( $site_id ) {
		$info = get_blog_details($site_id);
		$this->import($info);
		$this->ID          = $info->blog_id;
		$this->id          = $this->ID;
		$this->name        = $this->blogname;
		$this->title       = $this->blogname;
		$theme_slug        = get_blog_option($info->blog_id, 'stylesheet');
		$this->theme       = new Theme($theme_slug);
		$this->description = get_blog_option($info->blog_id, 'blogdescription');
		$this->admin_email = get_blog_option($info->blog_id, 'admin_email');
		$this->multisite   = true;
	}

	/**
	 * Executed for single-blog sites
	 *
	 * @internal
	 */
	protected function init_as_singlesite() {
		$this->admin_email = get_bloginfo('admin_email');
		$this->name        = get_bloginfo('name');
		$this->title       = $this->name;
		$this->description = get_bloginfo('description');
		$this->theme       = new Theme();
		$this->multisite   = false;
	}

	/**
	 * Executed for all types of sites: both multisite and "regular"
	 *
	 * @internal
	 */
	protected function init() {
		$this->url          = home_url();
		$this->home_url     = $this->url;
		$this->site_url     = site_url();
		$this->rdf          = get_bloginfo('rdf_url');
		$this->rss          = get_bloginfo('rss_url');
		$this->rss2         = get_bloginfo('rss2_url');
		$this->atom         = get_bloginfo('atom_url');
		$this->language     = get_bloginfo('language');
		$this->charset      = get_bloginfo('charset');
		$this->pingback     = get_bloginfo('pingback_url');
		$this->pingback_url = get_bloginfo('pingback_url');
	}


	/**
	 * Returns the language attributes that you're looking for
	 *
	 * @return string
	 */
	public function language_attributes() {
		return get_language_attributes();
	}

	/**
	 * Returns the field value
	 *
	 * @param string $field Option name.
	 * @return mixed
	 */
	public function __get( $field ) {
		if ( ! isset($this->$field) ) {
			if ( is_multisite() ) {
				$this->$field = get_blog_option($this->ID, $field);
			} else {
				$this->$field = get_option($field);
			}
		}
		return $this->$field;
	}

	/**
	 * Returns the icon
	 *
	 * @return Image
	 */
	public function icon() {
		if ( is_multisite() ) {
			return $this->icon_multisite($this->ID);
		}
		$iid = get_option('site_icon');
		if ( $iid ) {
			return new Image($iid);
		}
	}

	/**
	 * Returns the icon
	 *
	 * @param integer $site_id A blog ID.
	 * @return Image
	 */
	protected function icon_multisite( $site_id ) {
		$image   = null;
		$blog_id = self::switch_to_blog($site_id);
		$iid     = get_blog_option($blog_id, 'site_icon');
		if ( $iid ) {
			$image = new Image($iid);
		}
		restore_current_blog();
		return $image;
	}

	/**
	 * Returns the link to the site's home.
	 *
	 * @example
	 *
	 * ```twig
	 * <a href="{{ site.link }}" title="Home">
	 *     <img src="/wp-content/uploads/logo.png" alt="Logo for some stupid thing" />
	 * </a>
	 * ```
	 * ```html
	 * <a href="http://example.org" title="Home">
	 *     <img src="/wp-content/uploads/logo.png" alt="Logo for some stupid thing" />
	 * </a>
	 * ```
	 * @api
	 * @return string
	 */
	public function link() {
		return $this->url;
	}

	/**
	 * Returns the link
	 *
	 * @deprecated 0.21.9
	 * @internal
	 * @return string
	 */
	public function get_link() {
		Helper::warn('{{site.get_link}} is deprecated, use {{site.link}}');
		return $this->link();
	}


	/**
	 * Returns the meta value
	 *
	 * @ignore
	 * @param string $field Property to get.
	 */
	public function meta( $field ) {
		return $this->__get($field);
	}

	/**
	 * Update the option
	 *
	 * @ignore
	 * @param string $key Option name. Expected to not be SQL-escaped.
	 * @param mixed  $value Option value. Must be serializable if non-scalar.
	 * Expected to not be SQL-escaped.
	 */
	public function update( $key, $value ) {
		$value = apply_filters('timber_site_set_meta', $value, $key, $this->ID, $this);
		if ( is_multisite() ) {
			update_blog_option($this->ID, $key, $value);
		} else {
			update_option($key, $value);
		}
		$this->$key = $value;
	}

	/**
	 * Returns the link
	 *
	 * @deprecated 1.0.4
	 * @see TimberSite::link
	 * @return string
	 */
	public function url() {
		return $this->link();
	}

	/**
	 * Returns the link
	 *
	 * @deprecated 0.21.9
	 * @internal
	 * @return string
	 */
	public function get_url() {
		Helper::warn('{{site.get_url}} is deprecated, use {{site.link}} instead');
		return $this->link();
	}

}
