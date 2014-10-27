<?php
	$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;

	if ( ! $entry_id ) return;

	$entry = IB_Educator_Entry::get_instance( $entry_id );

	if ( ! $entry->ID ) return;

	$api = IB_Educator::get_instance();

	// Verify capabilities.
	if ( ! current_user_can( 'edit_ibedu_course', $entry->course_id ) ) {
		exit;
	}

	$quizzes = new WP_Query( array(
		'post_type' => 'ibedu_lesson',
		'meta_query' => array(
			'relation' => 'AND',
			array( 'key' => '_ibedu_quiz', 'value' => 1, 'compare' => '=' ),
			array( 'key' => '_ibedu_course', 'value' => $entry->course_id, 'compare' => '=' )
		),
		'orderby' => 'menu_order',
		'order' => 'ASC',
	) );

	$suggested_grade = 0;
	$num_quizzes = $quizzes->found_posts;
	$student = get_user_by( 'id', $entry->user_id );
	$course = get_post( $entry->course_id );
?>
<div id="ibedu-progress" class="wrap">
	<h2><?php _e( 'Progress', 'ibeducator' ); ?></h2>

	<div class="entry-details">
		<h3><?php _e( 'Entry Details', 'ibeducator' ); ?></h3>
		<div class="form-row">
			<div class="label"><?php _e( 'Student', 'ibeducator' ); ?></div>
			<div class="field">
				<?php
					$username = '';

					if ( $student->first_name && $student->last_name ) {
						$username = $student->first_name . ' ' . $student->last_name;
					} else {
						$username = $student->display_name;
					}

					echo esc_html( $username );
				?>
			</div>
		</div>
		<div class="form-row">
			<div class="label"><?php _e( 'Course', 'ibeducator' ); ?></div>
			<div class="field">
				<?php echo esc_html( $course->post_title ); ?>
			</div>
		</div>
	</div>

	<?php if ( $quizzes->have_posts() ) : ?>
	<div class="quizzes">
		<h3><?php _e( 'Quizzes', 'ibeducator' ); ?></h3>
		<?php while ( $quizzes->have_posts() ) : $quizzes->the_post(); ?>
		<div class="quiz">
			<div class="quiz-title"><?php the_title(); ?><div class="handle"></div></div>
			<?php
				$lesson_id = get_the_ID();
				$questions = $api->get_questions( array( 'lesson_id' => $lesson_id ) );
				$answers = $api->get_student_answers( $lesson_id, $entry_id );
				$quiz_submitted = $api->is_quiz_submitted( $lesson_id, $entry->ID );

				if ( $questions ) {
					?>
					<table class="questions">
						<thead>
							<tr>
								<th><?php _e( 'Correct?', 'ibeducator' ); ?></th>
								<th><?php _e( 'Question', 'ibeducator' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ( $questions as $question ) {
								$answer = null;

								if ( $answers && isset( $answers[ $question->ID ] ) ) {
									$answer = $answers[ $question->ID ];
								}
								?>
								<tr class="question">
									<td class="check-answer">
										<div><?php
											if ( $answer ) {
												if ( 1 == $answer->correct ) echo '<span class="dashicons dashicons-yes"></span>';
												elseif ( -1 == $answer->correct ) echo '<span class="dashicons dashicons-editor-help"></span>';
												else echo '<span class="dashicons dashicons-no-alt"></span>';
											} else {
												echo '<span class="dashicons dashicons-editor-help"></span>';
											}
										?></div>
									</td>
									<td class="question-body">
									<?php
										echo '<div class="question-text">' . esc_html( $question->question ) . '</div>';

										// Answer(s).
										if ( 'multiplechoice' == $question->question_type ) {
											// Output the answer.
											if ( $answer ) {
												if ( 1 == $answer->correct ) {
													echo '<div class="answer">' . __( 'Correct', 'ibeducator' ) . '</div>';
												} else {
													echo '<div class="answer">' . __( 'Wrong', 'ibeducator' ) . '</div>';
												}
											} else {
												echo '<div class="answer">' . __( 'Not answered yet.', 'ibeducator' ) . '</div>';
											}
										} elseif ( 'writtenanswer' == $question->question_type ) {
											if ( $answer ) {
												echo '<div class="answer">' . esc_html( $answer->answer_text ) . '</div>';
											} else {
												echo '<div class="answer">' . __( 'Not answered yet.', 'ibeducator' ) . '</div>';
											}
										}
									?>
									</td>
								</tr>
								<?php
							}
						?>
						</tbody>
					</table>
					<?php
				}

				$grade = $api->get_quiz_grade( $lesson_id, $entry_id );

				if ( $grade ) {
					$suggested_grade += $grade->grade;
				}
			?>
			<div class="quiz-grade">
				<input type="hidden" name="lesson_id" value="<?php echo absint( $lesson_id ); ?>">

				<div class="form-row">
					<div class="label"><?php _e( 'Grade', 'ibeducator' ); ?></div>
					<div class="field">
						<input type="text" name="grade" value="<?php echo ( $grade ) ? floatval( $grade->grade ) : ''; ?>"<?php if ( ! $quiz_submitted ) echo ' disabled="disabled"'; ?>>
						<div class="description"><?php _e( 'Please enter a number between 0 and 100.', 'ibeducator' ); ?></div>
					</div>
				</div>

				<div class="form-buttons">
					<button class="save-quiz-grade button-primary"<?php if ( ! $quiz_submitted ) echo ' disabled="disabled"'; ?>><?php _e( 'Save Grade', 'ibeducator' ); ?></button>
				</div>
			</div>
		</div>
		<?php endwhile; ?>

		<?php wp_reset_postdata(); ?>

		<div class="summary">
			<h3><?php _e( 'Summary', 'ibeducator' ); ?></h3>
			<div class="form-row">
				<div class="label"><?php _e( 'Average Grade', 'ibeducator' ); ?></div>
				<div class="field">
					<?php echo ib_edu_format_grade( $suggested_grade / $num_quizzes ); ?>
				</div>
			</div>
			<div class="form-row">
				<div class="label"><?php _e( 'Final Grade', 'ibeducator' ); ?></div>
				<div class="field">
					<a href="<?php echo admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry_id ); ?>" target="_new"><?php echo ib_edu_format_grade( $entry->grade ); ?></a>
				</div>
			</div>
		</div>
	</div>

	<script>
	(function($) {
		var nonce = '<?php echo wp_create_nonce( "ibedu_edit_progress_{$entry->ID}" ); ?>';

		$('div.quiz-title').on('click', function() {
			$(this).parent().toggleClass('open');
		});

		$('button.save-quiz-grade').on('click', function() {
			var jThis = $(this);

			if (jThis.attr('disabled')) return;
			jThis.attr('disabled', 'disabled');

			var form = jThis.closest('div.quiz-grade');
			var grade = form.find('input[name="grade"]:first').val();
			var lessonId = form.find('input[name="lesson_id"]:first').val();

			$.ajax({
				method: 'post',
				dataType: 'json',
				url: ajaxurl + '?action=ibedu_quiz_grade',
				data: {
					entry_id: <?php echo absint( $entry_id ); ?>,
					lesson_id: lessonId,
					grade: grade,
					_wpnonce: nonce
				},
				success: function(response) {
					var overlayHtml = '', overlay = null;
					
					if (response && response.status && response.status === 'success') {
						overlayHtml = '<div class="ibedu-overlay ibedu-saved"></div>';
					} else {
						overlayHtml = '<div class="ibedu-overlay ibedu-error"></div>';
					}

					overlay = $(overlayHtml).hide();
					form.append(overlay);
					overlay.fadeIn(200, function() {
						setTimeout(function() {
							overlay.fadeOut(200, function() {
								$(this).remove();
								jThis.removeAttr('disabled');
							});
						}, 500);
					});
				}
			});
		});
	})(jQuery);
	</script>
	<?php endif; ?>
</div>