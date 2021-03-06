<?php

/** Block direct access */
defined( 'ABSPATH' ) || exit();

/** check if class `WP_Dark_Mode_Hooks` not exists yet */
if ( ! class_exists( 'WP_Dark_Mode_Hooks' ) ) {
	class WP_Dark_Mode_Hooks {

		/**
		 * @var null
		 */
		private static $instance = null;

		/**
		 * WP_Dark_Mode_Hooks constructor.
		 */
		public function __construct() {
			add_filter( 'wp_dark_mode/excludes', [ $this, 'excludes' ] );

			add_action( 'admin_footer', [ $this, 'display_promo' ] );
			add_action( 'wppool_after_settings', [ $this, 'pro_promo' ] );

			//display the dark mode switcher if the dark mode enabled on frontend
            add_action( 'wp_footer', [ $this, 'display_widget' ] );

			//declare custom color css variables
			add_action( 'wp_head', [ $this, 'header_scripts' ], 10 );

			add_action( 'wp_footer', [ $this, 'footer_scripts' ] );
		}

		/**
		 * declare custom color css variables
		 */
		public function header_scripts() {

			//Hide gutenberg block
			if ( is_page() || is_single() ) {
				if ( ! wp_dark_mode_enabled() ) {
					printf( '<style>.wp-block-wp-dark-mode-block-dark-mode-switch{display: none;}</style>' );
				}
			}

			if ( ! wp_dark_mode_enabled() ) {
				return;
			}

			$colors = wp_dark_mode_color_presets();

			$colors = [
				'bg'   => apply_filters( 'wp_dark_mode/bg_color', $colors['bg'] ),
				'text' => apply_filters( 'wp_dark_mode/text_color', $colors['text'] ),
				'link' => apply_filters( 'wp_dark_mode/link_color', $colors['link'] ),
			];

			$includes = wp_dark_mode_get_settings( 'wp_dark_mode_includes_excludes', 'includes' );

			$is_custom_color = wp_dark_mode_is_custom_color();

			// Add custom color init CSS
			if ( $is_custom_color ) { ?>
                <style>
                    :root {
                        --wp-dark-mode-bg: <?php echo $colors['bg']; ?>;
                        --wp-dark-mode-text: <?php echo $colors['text']; ?>;
                        --wp-dark-mode-link: <?php echo $colors['link']; ?>;
                    }

                    <?php if(empty($includes)){ ?>
                        html.wp-dark-mode-active body {
                            background-color: <?php echo $colors['bg']; ?>;
                            color: <?php echo $colors['text']; ?>;
                        }

                    <?php }?>
                </style>
			<?php }

			if ( ! isset( $_REQUEST['elementor-preview'] ) ) { ?>
                <script>
                    (function () {
                        window.wpDarkMode = <?php echo json_encode( wp_dark_mode_localize_array() ); ?>;

                        const is_saved = localStorage.getItem('wp_dark_mode_active');

                        if ((is_saved && is_saved != 0) || (!is_saved && wpDarkMode.default_mode)) {
                            document.querySelector('html').classList.add('wp-dark-mode-active');

                            const isCustomColor = parseInt("<?php echo $is_custom_color ?>");

                            if(!isCustomColor){

                                const userAgent = typeof navigator === 'undefined' ? 'some useragent' : navigator.userAgent.toLowerCase();
                                const isFirefox = userAgent.includes('firefox');

                                var isChromium = userAgent.includes('chrome') || userAgent.includes('chromium');
                                var isSafari = userAgent.includes('safari') && !isChromium;

                                if (isFirefox || isSafari) {
                                    return;
                                }

                                if ('' === '<?php echo $includes; ?>') {
                                    DarkMode.enable();
                                }
                            }
                        }

                    })();
                </script>
				<?php

				if ( $is_custom_color ) { ?>
                    <script>
                        window.customColor = function () {

                            const is_active = document.querySelector('html').classList.contains('wp-dark-mode-active') ? 1 : 0;

                            const elements = document.querySelectorAll(`
                               body,
                               article,
                               nav,
                               header,
                               footer,
                               div,
                               section,
                               p,strong,
                               font,
                               i,
                               a,
                               h1,
                               span,
                               h2,
                               h3,
                               h4,
                               h5,
                               h6,
                               li
                               `);

                            elements.forEach((element) => {

                                if (element.classList.contains('wp-dark-mode-ignore')) {
                                    return;
                                }

                                if ('' !== '<?php echo $includes; ?>') {
                                    if(!element.classList.contains('wp-dark-mode-include')){
                                        return;
                                    }
                                }

                                    const styles = window.getComputedStyle(element, "");

                                if ('A' === element.tagName) {
                                    if (!is_active && 'var(--wp-dark-mode-link)' === element.style.color) {
                                        element.style.removeProperty('color');
                                    } else {
                                        element.style.color = 'var(--wp-dark-mode-link)';
                                    }
                                } else {

                                    if (!is_active && 'var(--wp-dark-mode-text)' === element.style.color) {
                                        element.style.removeProperty('color');
                                    } else {
                                        element.style.color = 'var(--wp-dark-mode-text)';
                                    }
                                }

                                const rgb = styles.getPropertyValue('background-color');
                                const hex = '#' + rgb.substr(4, rgb.indexOf(')') - 4).split(',').map((color) => parseInt(color).toString(16)).join('');

                                if ('#NaN000' !== hex) {

                                    if (!is_active && 'var(--wp-dark-mode-bg)' === element.style.backgroundColor) {
                                        element.style.removeProperty('background-color');
                                    } else {
                                        element.style.backgroundColor = 'var(--wp-dark-mode-bg)';
                                    }

                                }

                            });

                        }
                    </script>
					<?php
				}
			}
		}

		public function footer_scripts() {
			$is_custom_color = wp_dark_mode_is_custom_color();
			$excludes        = wp_dark_mode_get_settings( 'wp_dark_mode_includes_excludes', 'excludes' );
			$includes        = wp_dark_mode_get_settings( 'wp_dark_mode_includes_excludes', 'includes' );

			?>
                <script>
                    ;(function () {
                        const userAgent = typeof navigator === 'undefined' ? 'some useragent' : navigator.userAgent.toLowerCase();
                        const isFirefox = userAgent.includes('firefox');

                        var isChromium = userAgent.includes('chrome') || userAgent.includes('chromium');
                        var isSafari = userAgent.includes('safari') && !isChromium;

                        const isCustomColor = parseInt('<?php echo $is_custom_color ?>');

                        //handle bg image excludes
                        if(!isCustomColor && isSafari) {
                            (function () {
                                const elements = document.querySelectorAll('header, footer, div, section');

                                elements.forEach((element) => {
                                    const bi = window.getComputedStyle(element, false).backgroundImage;
                                    const parallax = element.getAttribute('data-jarallax-original-styles');


                                    if (bi !== 'none' || parallax) {
                                        element.classList.add('wp-dark-mode-ignore');
                                        element.querySelectorAll('*').forEach((child) => child.classList.add('wp-dark-mode-ignore'));
                                    }
                                });
                            })();
                        }

                        //Handle excludes
                        if ('' !== '<?php echo $excludes; ?>') {
                            const elements = document.querySelectorAll('<?php echo $excludes; ?>');

                            elements.forEach((element) => {
                                element.classList.add('wp-dark-mode-ignore');
                                const children = element.querySelectorAll('*');

                                children.forEach((child) => {
                                    child.classList.add('wp-dark-mode-ignore');
                                });
                            });
                        }

                        //handle includes
                        if ('' !== '<?php echo $includes; ?>') {
                            const elements = document.querySelectorAll('<?php echo $includes; ?>');

                            elements.forEach((element) => {
                                element.classList.add('wp-dark-mode-include');
                                const children = element.querySelectorAll('*');

                                children.forEach((child) => {
                                    child.classList.add('wp-dark-mode-include');
                                })
                            });
                        }

                        if (isCustomColor) {
                            const is_active = document.querySelector('html').classList.contains('wp-dark-mode-active') ? 1 : 0;

                            if (is_active) {
                                window.customColor();
                            }
                        }
                    })();
                </script>
			<?php
		}

		/**
		 * display promo popup
		 */
		public function display_promo() {
			if ( $this->is_promo() ) {
				return;
			}

		    if ( wp_dark_mode_is_gutenberg_page() ) {
			    wp_dark_mode()->get_template( 'admin/promo' );
		    }
        }

		/**
		 * Exclude elements
		 *
		 * @param $excludes
		 *
		 * @return string
		 */
		public function excludes( $excludes ) {

			if ( $this->is_promo() ) {
				$selectors = wp_dark_mode_get_settings( 'wp_dark_mode_display', 'excludes', '' );

				if ( ! empty( $selectors ) ) {
					$excludes .= ", $selectors";
				}
			}

			return $excludes;
		}

		public function is_promo(){
			global $wp_dark_mode_license;

			if ( ! $wp_dark_mode_license ) {
				return false;
			}

			return $wp_dark_mode_license->is_valid();
        }

		/**
		 * display the footer widget
		 */
		public function display_widget() {

			if ( ! wp_dark_mode_enabled() ) {
				return false;
			}

			if ( 'on' != wp_dark_mode_get_settings( 'wp_dark_mode_switch', 'show_switcher', 'on' ) ) {
				return false;
			}

			$style = wp_dark_mode_get_settings( 'wp_dark_mode_switch', 'switch_style', 1 );

			global $wp_dark_mode_license;
			if ( ! $wp_dark_mode_license || ! $wp_dark_mode_license->is_valid() ) {
				$style = $style > 2 ? 1 : $style;
			}

			echo do_shortcode( '[wp_dark_mode floating="yes" style="' . $style . '"]' );
		}

		/**
		 * Display promo popup to upgrade to PRO
		 *
		 * @param $section - setting section
		 */
		public function pro_promo() {
			wp_dark_mode()->get_template( 'admin/promo' );
		}

		/**
		 * @return WP_Dark_Mode_Hooks|null
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

WP_Dark_Mode_Hooks::instance();

