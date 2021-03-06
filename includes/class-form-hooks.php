<?php
/**
 * Holds all hooks related to AnsPress forms.
 *
 * @link         https://anspress.io
 * @since        4.1.0
 * @license      GPL-3.0+
 * @package      AnsPress
 * @subpackage   Form Hooks
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The form hooks.
 *
 * @since 4.1.0
 */
class AP_Form_Hooks {
	private static $form;

	/**
	 * Register question form.
	 *
	 * @return array
	 * @since 4.1.0
	 */
	public static function question_form() {
		$editing = false;
		$editing_id = ap_sanitize_unslash( 'id', 'r' );

		$form = array(
			'submit_label' => __( 'Submit Question', 'anspress-question-answer' ),
			'fields' => array(
				'post_title' => array(
					'type'  => 'input',
					'label' => __( 'Title', 'anspress-question-answer' ),
					'desc'  => __( 'Question in one sentence', 'anspress-question-answer' ),
					'attr'  => array(
						'autocomplete'   => 'off',
						'placeholder'    => __( 'Question title', 'anspress-question-answer' ),
						'data-action'    => 'suggest_similar_questions',
						'data-loadclass' => 'q-title',
					),
					'min_length' => ap_opt( 'minimum_qtitle_length' ),
					'max_length' => 100,
					'validate'   => 'required,min_string_length,max_string_length,badwords',
					'order'      => 2,
				),
				'post_content' => array(
					'type'       => 'editor',
					'label'      => __( 'Description', 'anspress-question-answer' ),
					'min_length' => ap_opt( 'minimum_question_length' ),
					'validate'   => 'required,min_string_length,badwords',
					'editor_args' => array(
						'quicktags' => ap_opt( 'question_text_editor' ) ? true : false,
					),
				),
			),
		);

		// Add private field checkbox if enabled.
		if ( ap_opt( 'allow_private_posts' ) ) {
			$form['fields']['is_private'] = array(
				'type'  => 'checkbox',
				'label' => __( 'Is private?', 'anspress-question-answer' ),
				'desc'  => __( 'Only visible to admin and moderator.', 'anspress-question-answer' ),
			);
		}

		// Add name fields if anonymous is allowed.
		if ( ! is_user_logged_in() && ap_allow_anonymous() ) {
			$form['fields']['anonymous_name'] = array(
				'label'        => __( 'Your Name', 'anspress-question-answer' ),
				'attr'         => array(
					'placeholder' => __( 'Enter your name to display', 'anspress-question-answer' ),
				),
				'order'        => 20,
				'validate'     => 'max_string_length,badwords',
				'max_length'   => 64,
			);
		}

		$form['fields']['post_id'] = array(
			'type'     => 'input',
			'subtype'  => 'hidden',
			'value'    => $editing_id,
			'sanitize' => 'absint',
		);

		// Add value when editing post.
		if ( ! empty( $editing_id ) ) {
			$question = ap_get_post( $editing_id );

			$form['editing']                         = true;
			$form['editing_id']                      = $editing_id;
			$form['submit_label']                    = __( 'Update Question', 'anspress-question-answer' );
			$form['fields']['post_title']['value']   = $question->post_title;
			$form['fields']['post_content']['value'] = $question->post_content;
			$form['fields']['is_private']['value']   = 'private_post' === $question->post_status ? true : false;

			if ( isset( $form['fields']['anonymous_name'] ) ) {
				$form['fields']['anonymous_name'] = ap_get_post_field( 'anonymous_name', $question );
			}
		}

		/**
		 * Filter for modifying question form `$args`.
		 *
		 * @param 	array $fields 	Ask form fields.
		 * @param 	bool 	$editing 	Currently editing form.
		 * @since  	4.1.0
		 */
		$form = apply_filters( 'ap_question_form_fields', $form, $editing );

		return $form;
	}

	/**
	 * Register answer form.
	 *
	 * @return array
	 * @since 4.1.0
	 */
	public static function answer_form() {
		$editing = false;
		$editing_id = ap_sanitize_unslash( 'id', 'r' );
		$question_id = ap_sanitize_unslash( 'question_id', 'r', get_question_id() );

		$form = array(
			'submit_label' => __( 'Post Answer', 'anspress-question-answer' ),
			'fields'       => array(
				'post_content' => array(
					'type'       => 'editor',
					'label'      => __( 'Description', 'anspress-question-answer' ),
					'min_length' => ap_opt( 'minimum_ans_length' ),
					'validate'   => 'required,min_string_length,badwords',
					'editor_args' => array(
						'quicktags' => ap_opt( 'question_text_editor' ) ? true : false,
					),
				),
				'question_id' => array(
					'label'    => __( 'Question ID', 'anspress-question-answer' ),
					'type'     => 'input',
					'subtype'  => 'hidden',
					'sanitize' => 'absint',
					'validate' => 'required,not_zero',
					'value'    => $question_id,
				),
			),
		);

		// Add private field checkbox if enabled.
		if ( ap_opt( 'allow_private_posts' ) ) {
			$form['fields']['is_private'] = array(
				'type'  => 'checkbox',
				'label'  => __( 'Is private?', 'anspress-question-answer' ),
				'desc'  => __( 'Only visible to admin and moderator.', 'anspress-question-answer' ),
			);
		}

		// Add name fields if anonymous is allowed.
		if ( ! is_user_logged_in() && ap_allow_anonymous() ) {
			$form['fields']['anonymous_name'] = array(
				'label'        => __( 'Your Name', 'anspress-question-answer' ),
				'attr'         => array(
					'placeholder' => __( 'Enter your name to display', 'anspress-question-answer' ),
				),
				'order'        => 20,
				'validate'     => 'max_string_length,badwords',
				'max_length'   => 20,
			);
		}

		$form['fields']['post_id'] = array(
			'type'     => 'input',
			'subtype'  => 'hidden',
			'value'    => $editing_id,
			'sanitize' => 'absint',
		);

		// Add value when editing post.
		if ( ! empty( $editing_id ) ) {
			$answer = ap_get_post( $editing_id );
			$form['editing']                         = true;
			$form['editing_id']                      = $editing_id;
			$form['submit_label']                    = __( 'Update answer', 'anspress-question-answer' );
			$form['fields']['post_content']['value'] = $answer->post_content;
			$form['fields']['is_private']['value']   = 'private_post' === $answer->post_status ? true : false;
			$form['fields']['question_id']['value']  = $answer->post_parent;

			if ( isset( $form['fields']['anonymous_name'] ) ) {
				$form['fields']['anonymous_name'] = ap_get_post_field( 'anonymous_name', $answer );
			}
		}

		/**
		 * Filter for modifying answer form `$args`.
		 *
		 * @param 	array $fields 	Answer form fields.
		 * @param 	bool 	$editing 	Currently editing form.
		 * @since  	4.1.0
		 */
		return apply_filters( 'ap_answer_form_fields', $form, $editing );
	}

	/**
	 * Register comment form.
	 *
	 * @return array
	 * @since 4.1.0
	 */
	public static function comment_form() {
		$form = array(
			'submit_label' => __( 'Submit Comment', 'anspress-question-answer' ),
			'fields' => array(
				'content' => array(
					'type'       => 'textarea',
					'label'      => __( 'Comment', 'anspress-question-answer' ),
					'min_length' => 5,
					'validate'   => 'required,min_string_length,badwords',
					'attr' => array(
						'placeholder' => __( 'Write your comment here..', 'anspress-question-answer' ),
						'rows' => 5,
					),
					'editor_args' => array(
						'quicktags'     => true,
						'textarea_rows' => 5,
					),
				),
			),
		);

		// Add name fields if anonymous is allowed.
		if ( ! is_user_logged_in() ) {
			$form['fields']['author'] = array(
				'label'        => __( 'Your Name', 'anspress-question-answer' ),
				'attr'         => array(
					'placeholder' => __( 'Enter your name to display.', 'anspress-question-answer' ),
				),
				'validate'     => 'required,max_string_length,badwords',
				'max_length'   => 64,
			);

			$form['fields']['email'] = array(
				'label'        => __( 'Your Email', 'anspress-question-answer' ),
				'attr'         => array(
					'placeholder' => __( 'Enter your email to get follow up notifications.', 'anspress-question-answer' ),
				),
				'subtype'      => 'email',
				'validate'     => 'required,is_email',
				'max_length'   => 254,
			);

			$form['fields']['url'] = array(
				'label'        => __( 'Your Website', 'anspress-question-answer' ),
				'attr'         => array(
					'placeholder' => __( 'Enter link to your website.', 'anspress-question-answer' ),
				),
				'subtype'      => 'url',
				'validate'     => 'is_url',
				'max_length'   => 254,
			);
		}

		/**
		 * Filter for modifying comment form `$args`.
		 *
		 * @param 	array $fields 	Comment form fields.
		 * @since  	4.1.0
		 */
		$form = apply_filters( 'ap_comment_form_fields', $form );

		return $form;
	}

	/**
	 * Process question form submission.
	 *
	 * @return void
	 * @since 4.1.0
	 */
	public static function submit_question_form() {
		$editing = false;
		$form = anspress()->get_form( 'question' );

		/**
		 * Action triggered before processing question form.
		 *
		 * @since 4.1.0
		 */
		do_action( 'ap_submit_question_form' );

		$values = $form->get_values();

		// Check nonce and is valid form.
		if ( ! $form->is_submitted() ) {
			ap_ajax_json([
				'success' => false,
				'snackbar' => [ 'message' => __( 'Trying to cheat?!', 'anspress-question-answer' ) ],
			] );
		}

		$question_args = array(
			'post_title'		   => $values['post_title']['value'],
			'post_content' 		 => $values['post_content']['value'],
		);

		if ( ! empty( $values['post_id']['value'] ) ) {
			$question_args['ID'] = $values['post_id']['value'];
			$editing = true;
			$_post = ap_get_post( $question_args['ID'] );

			// Check if valid post type and user can edit.
			if ( 'question' !== $_post->post_type || ! ap_user_can_edit_question( $_post ) ) {
				ap_ajax_json( 'something_wrong' );
			}
		}

		// Add default arguments if not editing.
		if ( ! $editing ) {
			$question_args = wp_parse_args( $question_args, array(
				'post_author' 		 => get_current_user_id(),
				'post_name' 		   => '',
				'comment_status' 	 => 'open',
			) );
		}

		// Post status.
		$question_args['post_status'] = ap_new_edit_post_status( false, 'question', $editing );

		if ( $form->have_errors() ) {
			ap_ajax_json([
				'success'       => false,
				'snackbar'      => [ 'message' => __( 'Unable to post question.', 'anspress-question-answer' ) ],
				'form_errors'   => $form->errors,
				'fields_errors' => $form->get_fields_errors(),
			] );
		}

		// Set post parent.
		// @TODO: Check nonce for post parent.
		if ( isset( $values['post_parent'] ) && $values['post_parent']['value'] ) {
			$question_args['post_parent'] = $values['post_parent']['value'];
		}

		// If private override status.
		if ( true === $values['is_private']['value'] ) {
			$question_args['post_status'] = 'private_post';
		}

		// Check if duplicate.
		if ( ! $editing && ap_opt( 'duplicate_check' ) && false !== ap_find_duplicate_post( $question_args['post_content'], 'question' ) ) {
			$form->add_error( 'duplicate-question', __( 'You are trying to post a duplicate question. Please search existing questions before posting a new one.', 'anspress-question-answer' ) );

			ap_ajax_json([
				'success'       => false,
				'snackbar'      => [ 'message' => __( 'Unable to post question.', 'anspress-question-answer' ) ],
				'form_errors'   => $form->errors,
				'fields_errors' => $form->get_fields_errors(),
			] );
		}

		/**
		 * Filter question description before saving.
		 *
		 * @param string $content Post content.
		 * @since unknown
		 * @since @3.0.0 Moved from process-form.php
		 */
		$question_args['post_content'] = apply_filters( 'ap_form_contents_filter', $question_args['post_content'] );

		$question_args['post_name'] = ap_remove_stop_words_post_name( $question_args['post_title'] );

		if ( $editing ) {
			/**
			 * Can be used to modify `$args` before updating question
			 *
			 * @param array $question_args Question arguments.
			 * @since 2.0.1
			 * @since 4.1.0 Moved from includes/ask-form.php.
			 */
			$question_args = apply_filters( 'ap_pre_update_question', $question_args );
		} else {
			/**
			 * Can be used to modify args before inserting question
			 *
			 * @param array $question_args Question arguments.
			 * @since 2.0.1
			 * @since 4.1.0 Moved from includes/ask-form.php.
			 */
			$question_args = apply_filters( 'ap_pre_insert_question', $question_args );
		}

		if ( ! $editing ) {
			$question_args['post_type'] = 'question';
			$post_id = wp_insert_post( $question_args, true );
		} else {
			$post_id = wp_update_post( $question_args, true );
		}

		// If error return and send error message.
		if ( is_wp_error( $post_id ) ) {
			ap_ajax_json([
				'success'       => false,
				'snackbar'      => array(
					'message' => sprintf(
						// Translators: placeholder contain error message.
						__( 'Unable to post question. Error: %s', 'anspress-question-answer' ),
						$post_id->get_error_message()
					),
				),
			] );
		}

		$form->after_save( false, array(
			'post_id' => $post_id,
		) );

		// Clear temporary images.
		if ( $post_id ) {
			ap_clear_unattached_media();
		}

		if ( isset( $question_args['ID'] ) ) {
			$message = __( 'Question updated successfully, you\'ll be redirected in a moment.', 'anspress-question-answer' );
		} else {
			$message = __( 'Your question is posted successfully, you\'ll be redirected in a moment.', 'anspress-question-answer' );
		}

		ap_ajax_json( array(
			'success'  => true,
			'snackbar' => [
				'message' => $message,
			],
			'redirect' => get_permalink( $post_id ),
			'post_id'  => $post_id,
		) );
	}

	/**
	 * Process question form submission.
	 *
	 * @return void
	 * @since 4.1.0
	 */
	public static function submit_answer_form() {
		$editing = false;
		$form = anspress()->get_form( 'answer' );

		/**
		 * Action triggered before processing answer form.
		 *
		 * @since 4.1.0
		 */
		do_action( 'ap_submit_answer_form' );

		$values = $form->get_values();
		$question_id = $values['question_id']['value'];

		// Check nonce and is valid form.
		if ( ! $form->is_submitted() || ! ap_user_can_answer( $question_id ) ) {
			ap_ajax_json([
				'success' => false,
				'snackbar' => [ 'message' => __( 'Trying to cheat?!', 'anspress-question-answer' ) ],
			] );
		}

		$answer_args = array(
			'post_title'		   => $question_id,
			'post_name'		     => $question_id,
			'post_content' 		 => $values['post_content']['value'],
			'post_parent' 		 => $question_id,
		);

		if ( ! empty( $values['post_id']['value'] ) ) {
			$answer_args['ID'] = $values['post_id']['value'];
			$editing = true;
			$_post = ap_get_post( $answer_args['ID'] );

			// Check if valid post type and user can edit.
			if ( 'answer' !== $_post->post_type || ! ap_user_can_edit_answer( $_post ) ) {
				ap_ajax_json( 'something_wrong' );
			}
		}

		// Add default arguments if not editing.
		if ( ! $editing ) {
			$answer_args = wp_parse_args( $answer_args, array(
				'post_author' 		 => get_current_user_id(),
				'post_name' 		   => '',
				'comment_status' 	 => 'open',
			) );
		}

		// Post status.
		$answer_args['post_status'] = ap_new_edit_post_status( false, 'answer', $editing );

		if ( $form->have_errors() ) {
			ap_ajax_json([
				'success'       => false,
				'snackbar'      => [ 'message' => __( 'Unable to post answer.', 'anspress-question-answer' ) ],
				'form_errors'   => $form->errors,
				'fields_errors' => $form->get_fields_errors(),
			] );
		}

		// If private override status.
		if ( true === $values['is_private']['value'] ) {
			$answer_args['post_status'] = 'private_post';
		}

		/**
		 * Filter question description before saving.
		 *
		 * @param string $content Post content.
		 * @since unknown
		 * @since @3.0.0 Moved from process-form.php
		 */
		$answer_args['post_content'] = apply_filters( 'ap_form_contents_filter', $answer_args['post_content'] );

		$answer_args['post_name'] = ap_remove_stop_words_post_name( $answer_args['post_title'] );

		if ( $editing ) {
			/**
			 * Can be used to modify `$args` before updating answer
			 *
			 * @param array $answer_args Answer arguments.
			 * @since 2.0.1
			 * @since 4.1.0 Moved from includes/answer-form.php.
			 */
			$answer_args = apply_filters( 'ap_pre_update_answer', $answer_args );
		} else {
			/**
			 * Can be used to modify args before inserting answer
			 *
			 * @param array $answer_args Answer arguments.
			 * @since 2.0.1
			 * @since 4.1.0 Moved from includes/answer-form.php.
			 */
			$answer_args = apply_filters( 'ap_pre_insert_answer', $answer_args );
		}

		if ( ! $editing ) {
			$answer_args['post_type'] = 'answer';
			$post_id = wp_insert_post( $answer_args, true );
		} else {
			$post_id = wp_update_post( $answer_args, true );
		}

		// If error return and send error message.
		if ( is_wp_error( $post_id ) ) {
			ap_ajax_json([
				'success'       => false,
				'snackbar'      => array(
					'message' => sprintf(
						// Translators: placeholder contain error message.
						__( 'Unable to post answer. Error: %s', 'anspress-question-answer' ),
						$post_id->get_error_message()
					),
				),
			] );
		}

		$form->after_save( false, array(
			'post_id' => $post_id,
		) );

		// Clear temporary images.
		if ( $post_id ) {
			ap_clear_unattached_media();
		}

		if ( ! $editing ) {
			ap_answer_post_ajax_response( $question_id, $post_id );
		}

		if ( isset( $answer_args['ID'] ) ) {
			$message = __( 'Answer updated successfully. Redirecting you to question page.', 'anspress-question-answer' );
		} else {
			$message = __( 'Your answer is posted successfully.', 'anspress-question-answer' );
		}

		ap_ajax_json( array(
			'success'  => true,
			'snackbar' => [
				'message' => $message,
			],
			'redirect' => get_permalink( $question_id ),
			'post_id'  => $post_id,
		) );
	}

	/**
	 * Process comment form.
	 *
	 * @return void
	 * @since 4.1.0
	 */
	public static function submit_comment_form() {
		$editing = false;
		$form = anspress()->get_form( 'comment' );

		/**
		 * Action triggered before processing comment form.
		 *
		 * @since 4.1.0
		 */
		do_action( 'ap_submit_comment_form' );

		$values = $form->get_values();
		$post_id = ap_sanitize_unslash( 'post_id', 'r' );

		// Check nonce and is valid form.
		if ( ! $form->is_submitted() || ! ap_user_can_comment( $post_id ) ) {
			ap_ajax_json([
				'success' => false,
				'snackbar' => [ 'message' => __( 'Trying to cheat?!', 'anspress-question-answer' ) ],
			] );
		}

		if ( $form->have_errors() ) {
			ap_ajax_json([
				'success'       => false,
				'snackbar'      => [ 'message' => __( 'Unable to post comment.', 'anspress-question-answer' ) ],
				'form_errors'   => $form->errors,
				'fields_errors' => $form->get_fields_errors(),
			] );
		}

		$comment_id = ap_sanitize_unslash( 'comment_id', 'r' );
		if ( ! empty( $comment_id ) ) {
			$comment = get_comment( $comment_id );

			if ( 'anspress' !== $comment->comment_type || ! ap_user_can_edit_comment( $comment_id ) ) {
				ap_ajax_json([
					'success'       => false,
					'snackbar'      => [ 'message' => __( 'You cannot edit this comment.', 'anspress-question-answer' ) ],
				] );
			}

			// Check if content is changed.
			if ( $values['content']['value'] === $comment->comment_content ) {
				ap_ajax_json( [
					'success'  => false,
					'snackbar' => [ 'message' => __( 'There is no change in your comment.', 'anspress-question-answer' ) ],
				] );
			}

			$updated = wp_update_comment( array(
				'comment_ID'      => $comment_id,
				'comment_content' => $values['content']['value'],
			) );

			if ( ! is_wp_error( $updated ) ) {
				/**
				 * Fires immediately after a comment is updated in the database.
				 *
				 * @since 4.1.2
				 *
				 * @param int   $comment_id The comment ID.
				 */
				do_action( 'ap_edit_comment', $comment_id );

				$c = get_comment( $comment_id );
				$count = get_comment_count( $c->comment_post_ID );

				$result = array(
					'success'       => true,
					'comment'       => ap_comment_ajax_data( $c ),
					'action' 		    => 'edit-comment',
					'commentsCount' => [ 'text' => sprintf( _n( '%d Comment', '%d Comments', $count['all'], 'anspress-question-answer' ), $count['all'] ), 'number' => $count['all'], 'unapproved' => $count['awaiting_moderation'] ],
					'snackbar'      => [ 'message' => __( 'Comment updated successfully', 'anspress-question-answer' ) ],
				);

				ap_ajax_json( $result );
			}

			ap_ajax_json( array(
				'success'  => false,
				'snackbar' => [ 'message' => $updated->get_error_message() ],
			) );
		}

		$_post = ap_get_post( $post_id );

		$type = 'question' === $_post->post_type ? __( 'question', 'anspress-question-answer' ) : __( 'answer', 'anspress-question-answer' );

		// Check if not restricted post type.
		if ( in_array( $_post->post_status, [ 'draft', 'pending', 'trash' ], true ) ) {
			ap_ajax_json( array(
				'success'  => false,
				'snackbar' => array(
					'message' => sprintf(
						// Translators: %s contain post type name.
						__( 'Commenting is not allowed on draft, pending or deleted %s', 'anspress-question-answer' ),
						$type
					),
				),
			));
		}

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$author  = wp_slash( $user->display_name );
			$email   = wp_slash( $user->user_email );
			$url     = wp_slash( $user->user_url );
		} else {
			$user_id = 0;
			$author  = $values['author']['value'];
			$email   = $values['email']['value'];
			$url     = $values['url']['value'];
		}

		$commentdata = array(
			'comment_post_ID' 		   => $_post->ID,
			'comment_author' 		     => wp_slash( $author ),
			'comment_author_email' 	 => wp_slash( $email ),
			'comment_author_url' 	   => wp_slash( $url ),
			'comment_content' 		   => trim( $values['content']['value'] ),
			'comment_type' 			     => 'anspress',
			'comment_parent' 		     => 0,
			'user_id' 				       => $user_id,
		);

		/**
		 * Filter comment content before inserting to DB.
		 *
		 * @param bool 		$apply_filter  Apply this filter.
		 * @param string 	$content 		   Un-filtered comment content.
		 * @since 3.0.0
		 */
		$commentdata = apply_filters( 'ap_pre_insert_comment', $commentdata );

		// Insert new comment and get the comment ID.
		$comment_id = wp_new_comment( $commentdata, true );

		if ( ! is_wp_error( $comment_id ) && false !== $comment_id ) {
			$c = get_comment( $comment_id );
			do_action( 'ap_after_new_comment', $c );

			$count = get_comment_count( $c->comment_post_ID );

			$result = array(
				'success'    => true,
				'comment'      => ap_comment_ajax_data( $c ),
				'action' 		  => 'new-comment',
				'commentsCount' => array(
					'text'        => sprintf(
						// Translators: %d contains count of comments.
						_n( '%d Comment', '%d Comments', $count['all'], 'anspress-question-answer' ),
						$count['all']
					),
					'number'      => $count['all'],
					'unapproved'  => $count['awaiting_moderation'],
				),
				'snackbar'   => [ 'message' => __( 'Comment successfully posted', 'anspress-question-answer' ) ],
			);

			ap_ajax_json( $result );
		}

		// Lastly output error message.
		ap_ajax_json( array(
			'success' => false,
			'snackbar' => [ 'message' => $comment_id->get_error_message() ],
		) );
	}
}
