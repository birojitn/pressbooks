<?php
/**
 * Contains all procedures which will be run on 'wpmu_new_blog' hook, register_activation_hook, and
 * register_deactivation_hook
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class Activation {

	/**
	 * @var int Current blog id (defaults to 1, main blog)
	 */
	private $blog_id = 1;

	/**
	 * @var int Current user id (defaults to 1, admin)
	 */
	private $user_id = 1;

	/**
	 * @var array The set of default WP options to set up on activation
	 */
	private $opts = [
		'pressbooks_theme_migration' => 2,
		'show_on_front' => 'page',
		'rewrite_rules' => '',
	];

	/**
	 * @var Activation
	 */
	protected static $instance = null;

	/**
	 * @var Taxonomy
	 */
	protected $taxonomy;

	/**
	 * @since 5.0.0
	 *
	 * @return Activation
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$taxonomy = Taxonomy::init();
			self::$instance = new self( $taxonomy );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @since 5.0.0
	 *
	 * @param Activation $obj
	 */
	static public function hooks( Activation $obj ) {
		register_activation_hook( realpath( __DIR__ . '/../pressbooks.php' ), [ $obj, 'registerActivationHook' ] );
		add_action( 'wpmu_new_blog', [ $obj, 'wpmuNewBlog' ], 9, 2 );
		add_action( 'wp_login', [ $obj, 'forcePbColors' ], 10, 2 );
		add_action( 'profile_update', [ $obj, 'forcePbColors' ] );
		add_action( 'user_register', [ $obj, 'forcePbColors' ] );
	}

	/**
	 * @param Taxonomy $taxonomy
	 */
	function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Activation hook
	 *
	 * @see register_activation_hook()
	 */
	public function registerActivationHook() {

		// Apply Pressbooks color scheme
		update_user_option( get_current_user_id(), 'admin_color', 'pb_colors', true );

		// Prevent overwriting customizations if Pressbooks has been disabled
		if ( ! get_site_option( 'pressbooks-activated' ) ) {

			/**
			 * Allow the default description of the root blog to be customized.
			 *
			 * @since 3.9.7
			 *
			 * @param string $value Default description ('Simple Book Publishing').
			 */
			update_blog_option( 1, 'blogdescription', apply_filters( 'pb_root_description', __( 'Simple Book Publishing', 'pressbooks' ) ) );

			// Configure root blog theme (PB_ROOT_THEME, defined as 'pressbooks-publisher' by default).
			update_blog_option( 1, 'template', PB_ROOT_THEME );
			update_blog_option( 1, 'stylesheet', PB_ROOT_THEME );
			// Remove widgets from root blog.
			delete_blog_option( 1, 'sidebars_widgets' );

			// Add "activated" key to enable check above
			add_site_option( 'pressbooks-activated', true );

		}
	}


	/**
	 * Runs activation function and sets up default WP options for new blog,
	 * a.k.a. when a registered user creates a new blog
	 *
	 * @param int $blog_id
	 * @param int $user_id
	 *
	 * @see add_action( 'wpmu_new_blog', ... )
	 */
	public function wpmuNewBlog( $blog_id, $user_id ) {

		$this->blog_id = (int) $blog_id;
		$this->user_id = (int) $user_id;

		switch_to_blog( $this->blog_id );
		if ( ! $this->isBookSetup() ) {

			$this->wpmuActivate();
			array_walk(
				$this->opts, function ( $v, $k ) {
					if ( empty( $v ) ) {
						delete_option( $k );
					} else {
						update_option( $k, $v );
					}
				}
			);

			wp_cache_flush();
		}

		// Set current versions to skip redundant upgrade routines
		update_option( 'pressbooks_metadata_version', \Pressbooks\Metadata::VERSION );
		update_option( 'pressbooks_taxonomy_version', \Pressbooks\Taxonomy::VERSION );
		foreach ( ( new \Pressbooks\Modules\ThemeOptions\ThemeOptions() )->getTabs() as $slug => $theme_options_class ) {
			update_option( "pressbooks_theme_options_{$slug}_version", $theme_options_class::VERSION, false );
		}

		flush_rewrite_rules( false );

		/**
		 * @since 4.3.0
		 */
		do_action( 'pb_new_blog' );

		/**
		 * @deprecated 4.3.0 Use pb_new_blog instead.
		 */
		do_action( 'pressbooks_new_blog' );

		restore_current_blog();

		if ( is_user_logged_in() ) {
			( new \Pressbooks\Catalog() )->deleteCache();
			/**
			 * Determine whether or not to redirect the user to the new book's dashboard.
			 *
			 * @since 4.1.0
			 *
			 * @param bool $value Whether or not to redirect the user
			 */
			if ( apply_filters( 'pb_redirect_to_new_book', true ) ) {
				\Pressbooks\Redirect\location( get_admin_url( $this->blog_id ) );
			}
		}

	}


	/**
	 * Determine if book is set up or not to avoid duplication
	 * (i.e. if activation functions have run and default options set)
	 *
	 * @return bool
	 */
	private function isBookSetup() {

		$act = get_option( 'pb_activated' );
		$pof = get_option( 'page_on_front' );
		$pop = get_option( 'page_for_posts' );
		if ( empty( $act ) ) {
			return false;
		}
		if ( ( get_option( 'template' ) !== 'pressbooks-book' ) || ( get_option( 'stylesheet' ) !== 'pressbooks-book' ) ) {
			return false;
		}
		if ( ( get_option( 'show_on_front' ) !== 'page' ) || ( ( ! is_int( $pof ) ) || ( ! get_post( $pof ) ) ) || ( ( ! is_int( $pop ) ) || ( ! get_post( $pop ) ) ) ) {
			return false;
		}
		if ( ( count( get_all_category_ids() ) < 3 ) || ( wp_count_posts()->publish < 3 ) || ( wp_count_posts( 'page' )->publish < 3 ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Set up default terms for Front Matter and Back Matter
	 * Insert default part, chapter, front matter, and back matter
	 * Insert default pages (Authors, Cover, TOC, About, Buy, and Access Denied)
	 * Remove content generated by wp_install_defaults
	 * Anything which needs to run on blog activation must go in this function
	 */
	private function wpmuActivate() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$this->taxonomy->insertTerms();

		$posts = [
			// Parts, Chapters, Front-Matter, Back-Matter
			'main-body' => [
				'post_title' => __( 'Main Body', 'pressbooks' ),
				'post_name' => 'main-body',
				'post_type' => 'part',
				'menu_order' => 1,
			],
			'introduction' => [
				'post_title' => __( 'Introduction', 'pressbooks' ),
				'post_name' => 'introduction',
				'post_content' => __( 'This is where you can write your introduction.', 'pressbooks' ),
				'post_type' => 'front-matter',
				'menu_order' => 1,
			],
			'chapter-1' => [
				'post_title' => __( 'Chapter 1', 'pressbooks' ),
				'post_name' => 'chapter-1',
				'post_content' => __( 'This is the first chapter in the main body of the text. You can change the text, rename the chapter, add new chapters, and add new parts.', 'pressbooks' ),
				'post_type' => 'chapter',
				'menu_order' => 1,
			],
			'appendix' => [
				'post_title' => __( 'Appendix', 'pressbooks' ),
				'post_name' => 'appendix',
				'post_content' => __( 'This is where you can add appendices or other back matter.', 'pressbooks' ),
				'post_type' => 'back-matter',
				'menu_order' => 1,
			],
			// Pages
			'authors' => [
				'post_title' => __( 'Authors', 'pressbooks' ),
				'post_name' => 'authors',
				'post_type' => 'page',
			],
			'cover' => [
				'post_title' => __( 'Cover', 'pressbooks' ),
				'post_name' => 'cover',
				'post_type' => 'page',
			],
			'table-of-contents' => [
				'post_title' => __( 'Table of Contents', 'pressbooks' ),
				'post_name' => 'table-of-contents',
				'post_type' => 'page',
			],
			'about' => [
				'post_title' => __( 'About', 'pressbooks' ),
				'post_name' => 'about',
				'post_type' => 'page',
			],
			'buy' => [
				'post_title' => __( 'Buy', 'pressbooks' ),
				'post_name' => 'buy',
				'post_type' => 'page',
			],
			'access-denied' => [
				'post_title' => __( 'Access Denied', 'pressbooks' ),
				'post_name' => 'access-denied',
				'post_content' => __( 'This book is private, and accessible only to registered users. If you have an account you can login <a href="/wp-login.php">here</a>. You can also set up your own Pressbooks book at: <a href="http://pressbooks.com">Pressbooks.com</a>.', 'pressbooks' ),
				'post_type' => 'page',
			],
			// Custom CSS (deprecated)
			'custom-css-epub' => [
				'post_title' => __( 'Custom CSS for Ebook', 'pressbooks' ),
				'post_name' => 'epub',
				'post_type' => 'custom-css',
			],
			'custom-css-prince' => [
				'post_title' => __( 'Custom CSS for PDF', 'pressbooks' ),
				'post_name' => 'prince',
				'post_type' => 'custom-css',
			],
			'custom-css-web' => [
				'post_title' => __( 'Custom CSS for Web', 'pressbooks' ),
				'post_name' => 'web',
				'post_type' => 'custom-css',
			],
			// Book Information
			[
				'post_title' => __( 'Book Information', 'pressbooks' ),
				'post_name' => 'book-information',
				'post_type' => 'metadata',
			],
		];

		/**
		 * Filter the default content for new books.
		 *
		 * @since 4.1.0
		 *
		 * @param array $posts The default book content.
		 */
		$posts = apply_filters( 'pb_default_book_content', $posts );

		$part = [
			'post_status' => 'publish',
			'comment_status' => 'closed',
			'post_author' => $this->user_id,
		];
		$post = [
			'post_status' => 'publish',
			'comment_status' => 'open',
			'post_author' => $this->user_id,
		];
		$page = [
			'post_status' => 'publish',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_content' => '<!-- Here be dragons. -->',
			'post_author' => $this->user_id,
			'tags_input' => __( 'Default Data', 'pressbooks' ),
		];
		$meta = [
			'post_status' => 'publish',
			'comment_status' => 'closed',
			'post_author' => $this->user_id,
		];

		/**
		 * Allow the default description of a new book to be customized.
		 *
		 * @since 3.9.7
		 *
		 * @param string $value Default description ('Simple Book Publishing').
		 */
		update_option( 'blogdescription', apply_filters( 'pb_book_description', __( 'Simple Book Publishing', 'pressbooks' ) ) );

		$parent_part = 0;
		$intro = 0;
		$appendix = 0;
		$chapter1 = 0;

		foreach ( $posts as $item ) {

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_name = %s AND post_status = 'publish' ", [
						$item['post_title'],
						$item['post_type'],
						$item['post_name'],
					]
				)
			);
			if ( empty( $exists ) ) {
				if ( 'page' === $item['post_type'] ) {
					$data = array_merge( $item, $page );
				} elseif ( 'part' === $item['post_type'] ) {
					$data = array_merge( $item, $part );
				} elseif ( 'metadata' === $item['post_type'] ) {
					$data = array_merge( $item, $meta );
				} else {
					$data = array_merge( $item, $post );
				}

				$newpost = wp_insert_post( $data, true );
				if ( ! is_wp_error( $newpost ) ) {
					switch ( $item['post_name'] ) {
						case 'cover':
							$this->opts['page_on_front'] = (int) $newpost;
							break;
						case 'table-of-contents':
							$this->opts['page_for_posts'] = (int) $newpost;
							break;
					}

					if ( 'part' === $item['post_type'] ) {
						$parent_part = $newpost;
					} elseif ( 'chapter' === $item['post_type'] ) {
						$my_post = [];
						$my_post['ID'] = $newpost;
						$my_post['post_parent'] = $parent_part;
						wp_update_post( $my_post );
						// Apply 'standard' chapter type to 'chapter 1' post
						wp_set_object_terms( $newpost, 'standard', 'chapter-type' );
					} elseif ( 'front-matter' === $item['post_type'] ) {
						// Apply 'introduction' front matter type to 'introduction' post
						wp_set_object_terms( $newpost, 'introduction', 'front-matter-type' );
					} elseif ( 'back-matter' === $item['post_type'] ) {
						// Apply 'appendix' front matter type to 'appendix' post
						wp_set_object_terms( $newpost, 'appendix', 'back-matter-type' );
					} elseif ( 'metadata' === $item['post_type'] ) {
						$metadata_id = $newpost;
						if ( 0 !== get_current_user_id() ) {
							// Add initial contributor
							$user_info = get_userdata( get_current_user_id() );
							$contributors = new Contributors();
							$term = $contributors->addBlogUser( $user_info->ID );
							if ( $term !== false ) {
								$contributors->link( $term['term_id'], $metadata_id, 'pb_authors' );
							}
						}
						$locale = get_option( 'WPLANG' );
						if ( ! empty( $locale ) ) {
							$locale = array_search( $locale, \Pressbooks\L10n\wplang_codes(), true );
						} elseif ( get_site_option( 'WPLANG' ) ) {
							$locale = array_search( get_site_option( 'WPLANG' ), \Pressbooks\L10n\wplang_codes(), true );
						} else {
							$locale = 'en';
						}
						update_post_meta( $metadata_id, 'pb_title', get_option( 'blogname' ) );
						update_post_meta( $metadata_id, 'pb_language', $locale );
						update_post_meta( $metadata_id, 'pb_cover_image', \Pressbooks\Image\default_cover_url() );
					}
				} else {
					trigger_error( $newpost->get_error_message(), E_USER_ERROR );
				}
			}
		}

		// Custom Styles
		Container::get( 'Styles' )->initPosts();

		// Remove content generated by wp_install_defaults
		if ( ! wp_delete_post( 1, true ) ) {
			return;
		}
		if ( ! wp_delete_post( 2, true ) ) {
			return;
		}
		if ( ! wp_delete_comment( 1, true ) ) {
			return;
		}

		$this->opts['pb_activated'] = time();
		refresh_blog_details( $this->blog_id );
	}

	/**
	 * Never let a user change [ Your Profile > Admin Color Scheme ]
	 *
	 * @param int $id
	 * @param object $user (optional)
	 */
	public function forcePbColors( $id, $user = null ) {

		if ( is_numeric( $id ) ) {
			$user_id = $id;
		} elseif ( $user instanceof \WP_User ) {
			$user_id = $user->ID;
		} else {
			return;
		}

		update_user_option( $user_id, 'admin_color', 'pb_colors', true );
	}

}
