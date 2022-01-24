<?php

namespace Yoast\WP\SEO\Exceptions\Validation;

/**
 * Invalid empty string validation exception class.
 */
class Invalid_Empty_String_Exception extends Validation_Exception {

	/**
	 * Constructs an invalid empty string validation exception instance.
	 *
	 * @param string $value The value that is not empty.
	 */
	public function __construct( $value ) {
		parent::__construct( \sprintf(
		/* translators: %s expands to a user input string. */
			\esc_html__( '%s is not empty', 'wordpress-seo' ),
			'<strong>' . \esc_html( $value ) . '</strong>'
		) );
	}
}
