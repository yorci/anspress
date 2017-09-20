<?php
/**
 * AnsPress Form object.
 *
 * @package    AnsPress
 * @subpackage Form
 * @since      4.1.0
 * @author     Rahul Aryan<support@anspress.io>
 * @copyright  Copyright (c) 2017, Rahul Aryan
 * @license    http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */

namespace AnsPress;

/**
 * The form class.
 *
 * @since 4.1.0
 */
class Form {
	/**
	 * The form name.
	 *
	 * @var string
	 */
	public $form_name = '';

	/**
	 * The form args.
	 *
	 * @var array
	 */
	public $args = [];

	/**
	 * The fields.
	 *
	 * @var array
	 */
	public $fields = [];

	/**
	 * Is form prepared.
	 *
	 * @var boolean
	 */
	public $prepared = false;

	public $errors = [];
	public $values = null;

	/**
	 * Initialize the class.
	 *
	 * @param string $form_name Name of form.
	 * @param array  $args      Arguments for form.
	 */
	public function __construct( $form_name, $args ) {
		$this->form_name = $form_name;
		$this->args      = wp_parse_args( $args, array(
			'submit_label' => __( 'Submit', 'anspress-question-answer' ),
		));
	}

	/**
	 * Prepare input field.
	 *
	 * @return void
	 */
	public function prepare() {
		$fields = ap_sort_array_by_order( $this->args['fields'] );

		foreach ( (array) $fields as $field_name => $field_args ) {

			if ( empty( $field_args['type'] ) ) {
				$field_args['type'] = 'input';
			}

			$type_class = ucfirst( trim( $field_args['type'] ) );
			$field_class = 'AnsPress\\Form\\Field\\' . $type_class;

			if ( class_exists( $field_class ) ) {
				$this->fields[ $field_name ] = new $field_class( $this->form_name, $field_name, $field_args, $this );
			}
		}

		$this->prepared = true;
		$this->sanitize_validate();
	}

	/**
	 * Generate fields HTML markup.
	 *
	 * @return string
	 */
	public function generate_fields() {
		$html = '';

		if ( false === $this->prepared ) {
			$this->prepare();
		}

		foreach ( (array) $this->fields as $field ) {
			$html .= $field->output();
		}

		return $html;
	}

	/**
	 * Generate form.
	 *
	 * @return void
	 */
	public function generate() {
		echo '<form id="' . $this->form_name . '" name="' . esc_attr( $this->form_name ) . '" method="POST" enctype="multipart/form-data" apform>';

		// Output form errors.
		if ( $this->have_errors() ) {
			echo '<div class="ap-form-errors">';
			foreach ( (array) $this->errors as $code => $msg ) {
				echo '<span class="ap-form-error ecode-' . esc_attr( $code ) . '">' . esc_html( $msg ) . '</span>';
			}
			echo '</div>';
		}

		echo $this->generate_fields(); // xss okay.

		echo '<input type="hidden" name="action" value="ap_ajax">';
		echo '<input type="hidden" name="ap_ajax_action" value="' . esc_attr( $this->form_name ) . '">';
		echo '<input type="hidden" name="ap_form_name" value="' . esc_attr( $this->form_name ) . '" />';
		echo '<input type="submit" name="' . esc_attr( $this->form_name ) . '_submit" value="' . esc_html( $this->args['submit_label'] ) . '" class="ap-btn ap-btn-submit" />';
		echo '<input type="hidden" name="' . esc_attr( $this->form_name ) . '_nonce" value="' . esc_attr( wp_create_nonce( $this->form_name ) ) . '" />';
		echo '<input type="hidden" name="' . esc_attr( $this->form_name ) . '_submit" value="true" />';
		echo '</form>';
	}

	/**
	 * Check if current form is submitted.
	 *
	 * @return boolean
	 */
	public function is_submitted() {
		$nonce = ap_isset_post_value( esc_attr( $this->form_name ) . '_nonce' );

		if ( ap_isset_post_value( esc_attr( $this->form_name ) . '_submit' ) && wp_verify_nonce( $nonce, $this->form_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Find a field object.
	 *
	 * @param string        $field_name   Name of a field to find.
	 * @param boolean|array $fields       List of field where to search.
	 * @return array|boolean
	 */
	public function find( $field_name, $fields = false ) {
		$fields = false === $fields ? $this->fields : $fields;
		$found = wp_filter_object_list( $fields, [ 'original_name' => $field_name ] );

		if ( empty( $found ) ) {
			foreach ( $fields as $field ) {
				if ( ! empty( $field->child ) && ! empty( $field->child->fields ) ) {
					$child_found = $this->find( $field_name, $field->child->fields );

					if ( ! empty( $child_found ) ) {
						$found = $child_found;
						break;
					}
				}
			}
		}
		return is_array( $found ) ? reset( $found ) : $found;
	}

	/**
	 * Add an error to form object.
	 *
	 * @param string $code Error code.
	 * @param string $msg  Error message.
	 * @return void
	 */
	public function add_error( $code, $msg = '' ) {
		$this->errors[ $code ] = $msg;
	}

	/**
	 * Check if form have any error.
	 *
	 * @return boolean
	 */
	public function have_errors() {
		return ! empty( $this->errors ) && is_array( $this->errors );
	}

	/**
	 * Get a value from a path or default value if the path doesn't exist
	 *
	 * @param  string $key     Path.
	 * @param  mixed  $default Default value.
	 * @param  array  $array   Array to search.
	 * @return mixed
	 */
	public function get( $key, $default = null, $array = null ) {
		$keys = explode( '.', (string) $key );

		if ( null === $array ) {
			$array = &$this->args;
		}

		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $array ) ) {
				return $default;
			}

			$array = &$array[ $key ];
		}

		return $array;
	}

	/**
	 * Add new field in form.
	 *
	 * @param string $path Path of new array item. This must include field name at last.
	 * @param mixed  $val  Value to set.
	 *
	 * @return void.
	 */
	public function add_field( $path, $val ) {
		$path = is_string( $path ) ? explode( '.', $path ): $path;
		$loc  = &$this->args['fields'];

		foreach ( (array) $path as $step ) {
			$loc = &$loc[ $step ]['fields'];
		}

		$loc['fields'] = $val;
	}

	/**
	 * Validate and sanitize all fields.
	 *
	 * @param boolean|array $fields Fields to process.
	 * @return void
	 */
	private function sanitize_validate( $fields = false ) {
		if ( ! ap_isset_post_value( $this->form_name . '_submit' ) ) {
			return;
		}

		if ( false === $this->prepared ) {
			$this->prepare();
		}

		if ( false === $fields ) {
			$fields = $this->fields;
		}

		foreach ( (array) $fields as $field ) {
			if ( ! empty( $field->child ) && ! empty( $field->child->fields ) ) {
				$this->sanitize_validate( $field->child->fields );
			}

			$field->sanitize();
			$field->validate();

			if ( true === $field->have_errors() ) {
				$this->add_error( 'fields-error', __( 'Error found in fields, please check and re-submit', 'anspress-question-answer' ) );
			}
		}
	}

	public function get_fields_errors( $fields = false ) {
		$errors = [];

		if ( false === $this->prepared ) {
			$this->prepare();
		}

		if ( false === $fields ) {
			$fields = $this->fields;
		}

		foreach ( (array) $fields as $field ) {
			if ( $field->have_errors() ) {
				$errors[ $field->id() ] = [ 'error' => $field->errors ];
			}

			if ( ! empty( $field->child ) && ! empty( $field->child->fields ) ) {
				$child_errors = $this->get_fields_errors( $field->child->fields );

				if ( ! empty( $child_errors ) ) {
					$errors[ $field->id() ]['child'] = $child_errors;
				}
			}
		}

		return $errors;
	}

	private function field_values( $fields = false ) {
		$values = [];

		if ( false === $this->prepared ) {
			$this->prepare();
		}

		if ( false === $fields ) {
			$fields = $this->fields;
		}

		foreach ( (array) $fields as $field ) {
			$field->pre_get();
			$values[ $field->original_name ] = [ 'value' => $field->value() ];

			if ( ! empty( $field->child ) && ! empty( $field->child->fields ) ) {
				$values[ $field->original_name ]['child'] = $this->field_values( $field->child->fields );
			}
		}

		return $values;
	}

	/**
	 * Get all values of fields.
	 *
	 * @return array|false
	 */
	public function get_values() {
		if ( $this->have_errors() ) {
			return false;
		}

		if ( ! is_null( $this->values ) ) {
			return $this->values;
		}

		$this->values = $this->field_values();
		return $this->values;
	}

	/**
	 * Run all after save methods in fields and child fields.
	 *
	 * @param boolean|array $fields Fields.
	 * @param array         $args   Arguments to be passed to method.
	 * @return void
	 */
	public function after_save( $fields = false, $args = [] ) {
		if ( false === $this->prepared ) {
			$this->prepare();
		}

		if ( false === $fields ) {
			$fields = $this->fields;
		}

		foreach ( (array) $fields as $field ) {
			$field->after_save( $args );

			if ( ! empty( $field->child ) && ! empty( $field->child->fields ) ) {
				$this->after_save( $field->child->fields, $args );
			}
		}

	}

}
