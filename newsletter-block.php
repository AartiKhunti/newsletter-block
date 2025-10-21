<?php
/**
 * Plugin Name: Newsletter Signup Block
 * Description: Gutenberg block for newsletter signup form with REST API integration.
 * Version: 1.0
 * Author: Aarti Khunti
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register Gutenberg block
function nb_register_block() {
    wp_register_script(
        'newsletter-block-script',
        plugins_url( 'build/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
    );

    register_block_type( 'custom/newsletter-signup', array(
        'editor_script'   => 'newsletter-block-script',
        'render_callback' => 'nb_render_frontend_form',
    ) );
}
add_action( 'init', 'nb_register_block' );

// Render the frontend form
function nb_render_frontend_form() {
    ob_start(); ?>
    <section class="newsletter-wrapper">
        <div class="newsletter-content">
            <h2>Sign up to the Penguin<br>newsletter</h2>
            <p>For the latest books, recommendations, author interviews<br>and more</p>

            <form id="newsletter-signup" class="newsletter-form" novalidate>
                <div class="nb-row two-cols">
                    <div class="nb-field">
                        <label for="firstName" class="screen-reader-text">First name</label>
                        <input id="firstName" type="text" name="firstName" placeholder="First name" required aria-describedby="firstName-error" />
                        <div id="firstName-error" class="nb-field-error" aria-live="polite"></div>
                    </div>

                    <div class="nb-field">
                        <label for="surname" class="screen-reader-text">Surname</label>
                        <input id="surname" type="text" name="surname" placeholder="Surname" required aria-describedby="surname-error" />
                        <div id="surname-error" class="nb-field-error" aria-live="polite"></div>
                    </div>
                </div>

                <div class="nb-row">
                    <div class="nb-field full">
                        <label for="emailAddress" class="screen-reader-text">Email address</label>
                        <input id="emailAddress" type="email" name="emailAddress" placeholder="Your email" required aria-describedby="emailAddress-error" />
                        <div id="emailAddress-error" class="nb-field-error" aria-live="polite"></div>
                    </div>
                </div>

                <div class="nb-row">
                    <button type="submit">Sign up</button>
                </div>

                <p class="policy-note">
                    By signing up, I confirm that I'm over 16. To find out what personal data we collect and how we use it, please visit our 
                    <b><u>Privacy Policy</u></b>.
                </p>

                <div class="nb-message" aria-live="polite"></div>
            </form>
        </div>

        <div class="newsletter-image">
            <img src="<?php echo esc_url( plugins_url( 'assets/image/newsletter-right-img.png', __FILE__ ) ); ?>" alt="Books and newsletters background" />
        </div>
    </section>
    <?php
    return ob_get_clean();
}

// Register REST API endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'newsletter/v1', '/subscribe', array(
        'methods'  => 'POST',
        'callback' => 'nb_handle_subscription',
        'permission_callback' => '__return_true',
    ) );
});

// Handle the REST API form submission
function nb_handle_subscription( WP_REST_Request $request ) {
    $first  = sanitize_text_field( $request->get_param('firstName') );
    $last   = sanitize_text_field( $request->get_param('surname') );
    $email  = sanitize_email( $request->get_param('emailAddress') );

    if ( empty( $first ) || empty( $last ) || empty( $email ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error'   => 'Please fill in all required fields.'
        ), 400 );
    }

    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error'   => 'Please enter a valid email address.'
        ), 400 );
    }

    $subject = 'Thanks for subscribing!';

    $message = '
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Thanks for Subscribing</title>
    </head>
    <body style="font-family: Arial, sans-serif; background-color: #f8f8f8; padding: 30px; color: #333;">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 700px; margin: auto; background-color: #eceef2; border-radius: 8px; overflow: hidden;">
        <tr>
          <td style="padding: 30px; text-align: left;">
            <h2 style="font-size: 18px; color: #000;">Hi ' . esc_html($first) . ',</h2>
            <p style="font-size: 16px; line-height: 24px; color: #444;">
              Thanks for joining our newsletter!<br>
              We’ll keep you updated with the latest news.
            </p>
            <p style="font-size: 16px; color: #000;">Best regards,<br><b>eClerx Team</b></p>
          </td>
        </tr>
      </table>
    </body>
    </html>
    ';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    $mail_sent = wp_mail( $email, $subject, $message, $headers );

    if ( ! $mail_sent ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error'   => 'Something went wrong while sending the confirmation email. Please try again later.'
        ), 500 );
    }

    return new WP_REST_Response( array(
        'success' => true,
        'message' => 'Thanks for subscribing! A confirmation email has been sent.'
    ), 200 );
}

// Enqueue frontend JS and CSS
function nb_enqueue_frontend_assets() {
    if ( ! is_admin() ) {
        wp_enqueue_script(
            'newsletter-form',
            plugins_url( 'assets/form.js', __FILE__ ),
            array(),
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/form.js' ),
            true
        );

        wp_localize_script( 'newsletter-form', 'nbData', array(
            'restUrl' => esc_url( rest_url( 'newsletter/v1/subscribe' ) )
        ) );

        wp_enqueue_style(
            'newsletter-style',
            plugins_url( 'assets/style.css', __FILE__ ),
            array(),
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/style.css' )
        );

        wp_enqueue_style(
            'newsletter-google-fonts',
            'https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Roboto:wght@400;500;700&family=Noto+Sans:wght@400;500;700&display=swap',
            array(),
            null
        );
    }
}
add_action( 'wp_enqueue_scripts', 'nb_enqueue_frontend_assets' );
