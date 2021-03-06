<?php
/**
 * Template for a single translation row editor in a translation set display.
 */

$user = wp_get_current_user();
$can_reject_self = ( isset( $t->user->user_login ) && $user->user_login === $t->user->user_login && 'waiting' === $t->translation_status );

$more_links = array();
if ( $t->translation_status ) {
	$translation_permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'filters[translation_id]' => $t->id ) );
	$more_links['translation-permalink'] = '<a href="' . esc_url( $translation_permalink ) . '">Permalink to translation</a>';
} else {
	$original_permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[original_id]' => $t->original_id ) );
	$more_links['original-permalink'] = '<a href="' . esc_url( $original_permalink ) . '">Permalink to original</a>';
}

$original_history = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'sort[by]' => 'translation_date_added', 'sort[how]' => 'asc' ) );
$more_links['history'] = '<a href="' . esc_url( $original_history ) . '">Translation History</a>';

/**
 * Allows to modify the more links in the translation editor.
 *
 * @since 2.3.0
 *
 * @param array              $more_links      The links to be output.
 * @param GP_Project         $project         Project object.
 * @param GP_Locale          $locale          Locale object.
 * @param GP_Translation_Set $translation_set Translation Set object.
 * @param GP_Translation     $t               Translation object.
 */
$more_links = apply_filters( 'gp_translation_row_template_more_links', $more_links, $project, $locale, $translation_set, $t );

if ( is_object( $glossary ) ) {
	if ( ! isset( $glossary_entries_terms ) ) {
		$glossary_entries = $glossary->get_entries();
		$glossary_entries_terms = gp_sort_glossary_entries_terms( $glossary_entries );
	}

	$t = map_glossary_entries_to_translation_originals( $t, $glossary, $glossary_entries_terms );
}

?>
<tr class="editor <?php echo gp_translation_row_classes( $t ); ?>" id="editor-<?php echo esc_attr( $t->row_id ); ?>" row="<?php echo esc_attr( $t->row_id ); ?>">
	<td colspan="<?php echo $can_approve ? 5 : 4 ?>">
		<div class="editor-panel">
			<div class="editor-panel__left">
				<div class="panel-header">
					<?php
					$status = sprintf(
						'<span class="panel-header__bubble%s">%s</span>',
						$t->translation_status ? ' panel-header__bubble--' . $t->translation_status : '',
						display_status( $t->translation_status )
					);

					$warnings_count = wporg_gp_count_warnings( $t );
					$warnings_info  = '';
					if ( $warnings_count ) {
						$warnings_info = ' <span class="panel-header__bubble panel-header__bubble--warning">' . sprintf(
							_n( '%s warning', '%s warnings', $warnings_count ),
							number_format_i18n( $warnings_count )
						) . '</span>';
					}
					?>
					<h3>Original <?php echo $status . $warnings_info; ?></h3>
					<div class="panel-header-actions">
						<button type="button" class="panel-header-actions__cancel with-tooltip" aria-label="Close current editor">
							<span class="screen-reader-text">Close</span><span aria-hidden="true" class="dashicons dashicons-no-alt"></span>
						</button>
						<button type="button" class="panel-header-actions__previous with-tooltip" aria-label="Open previous editor">
							<span class="screen-reader-text">Previous</span><span aria-hidden="true" class="dashicons dashicons-arrow-up-alt2"></span>
						</button>
						<button type="button" class="panel-header-actions__next with-tooltip" aria-label="Open next editor">
							<span class="screen-reader-text">Next</span><span aria-hidden="true" class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
						<div class="button-menu">
							<button type="button" class="button-menu__toggle with-tooltip" aria-label="Show contextual links">
								<span class="screen-reader-text">Links</span><span aria-hidden="true" class="dashicons dashicons-menu-alt"></span>
							</button>
							<ul class="button-menu__dropdown">
								<?php foreach ( $more_links as $link ) : ?>
									<li><?php echo $link; ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="panel-content">
					<?php
					$singular = isset( $t->singular_glossary_markup ) ? $t->singular_glossary_markup : esc_translation( $t->singular );
					$plural   = isset( $t->plural_glossary_markup ) ? $t->plural_glossary_markup : esc_translation( $t->plural );
					?>
					<div class="source-string strings">
						<?php if ( ! $t->plural ) : ?>
							<div class="source-string__singular">
								<span class="original"><?php echo prepare_original( $singular ); ?></span>
								<span aria-hidden="true" class="original-raw"><?php echo esc_translation( $t->singular ); ?></span>
							</div>
						<?php else: ?>
							<div class="source-string__singular">
								<small>Singular:</small>
								<span class="original"><?php echo $singular; ?></span>
								<span aria-hidden="true" class="original-raw"><?php echo esc_translation( $t->singular ); ?></span>
							</div>
							<div class="source-string__plural">
								<small>Plural:</small>
								<span class="original"><?php echo $plural; ?></span>
								<span aria-hidden="true" class="original-raw"><?php echo esc_translation( $t->plural ); ?></span>
							</div>
						<?php endif; ?>
					</div>

					<div class="source-details">
						<?php if ( $t->context ): ?>
							<details open class="source-details__context">
								<summary>Context</summary>
								<span class="context bubble"><?php echo esc_translation( $t->context ); ?></span>
							</details>
						<?php endif; ?>
						<?php if ( $t->extracted_comments ) :
							$comments = trim( preg_replace( '/^translators:/ ', '', $t->extracted_comments ) );
							?>
							<details open class="source-details__comment">
								<summary><?php _e( 'Comment', 'glotpress' ); ?></summary>
								<p><?php echo make_clickable( esc_translation( $comments ) ); ?></p>
							</details>
						<?php endif; ?>
						<?php if ( $t->references ) : ?>
							<details class="source-details__references">
								<summary>References</summary>
								<?php wporg_references( $project, $t ); ?>
							</details>
						<?php endif; ?>
					</div>

					<div class="translation-wrapper">
						<?php if ( $t->plural && $locale->nplurals > 1 ) : ?>
							<div class="translation-form-wrapper">
								<span>Form:</span>
								<ul class="translation-form-list">
									<?php if ( 2 === (int) $locale->nplurals && 'n != 1' === $locale->plural_expression ) : ?>
										<li>
											<button class="translation-form-list__tab translation-form-list__tab--active with-tooltip"
													type="button"
													aria-label="Translation for singular form"
													data-plural-index="0">
												Singular
											</button>
										</li>
										<li>
											<button class="translation-form-list__tab with-tooltip"
													type="button"
													aria-label="Translation for plural form"
													data-plural-index="1">
												Plural
											</button>
										</li>
									<?php else : ?>
										<?php foreach( range( 0, $locale->nplurals - 1 ) as $plural_index ):
											$plural_string = implode(', ', $locale->numbers_for_index( $plural_index ) );
											?>
											<li>
												<button
														class="translation-form-list__tab with-tooltip<?php echo ( 0 === $plural_index ) ? ' translation-form-list__tab--active' : ''; ?>"
														data-plural-index="<?php echo $plural_index; ?>"
														aria-label="<?php printf('This plural form is used for numbers like: %s', $plural_string ); ?>"
														type="button">
													<?php echo $plural_string; ?>
												</button>
											</li>
										<?php endforeach; ?>
									<?php endif; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ( ! $t->plural ) : ?>
							<?php wporg_gp_translate_textarea( $t, [ $can_edit, $can_approve_translation ] ); ?>
						<?php else : ?>
							<?php foreach( range( 0, $locale->nplurals - 1 ) as $plural_index ): ?>
								<?php wporg_gp_translate_textarea( $t, [ $can_edit, $can_approve ], $plural_index ); ?>
							<?php endforeach; ?>
						<?php endif; ?>

						<div class="translation-actions">
							<?php if ( $can_edit ) : ?>
								<div class="translation-actions__primary">
									<button class="translation-actions__save with-tooltip"
											type="button"
											aria-label="<?php echo $can_approve_translation ? 'Save and approve translation' : 'Suggest new translation'; ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>">
										<?php echo $can_approve_translation ? 'Save' : 'Suggest'; ?>
									</button>
								</div>
								<div class="translation-actions__secondary">
									<button type="button" class="translation-actions__copy with-tooltip" aria-label="Copy original">
										<span class="screen-reader-text">Copy</span><span aria-hidden="true" class="dashicons dashicons-admin-page"></span>
									</button>
									<button type="button" class="translation-actions__ltr with-tooltip" aria-label="Switch to LTR">
										<span class="screen-reader-text">LTR</span><span aria-hidden="true" class="dashicons dashicons-editor-ltr"></span>
									</button>
									<button type="button" class="translation-actions__rtl with-tooltip" aria-label="Switch to RTL">
										<span class="screen-reader-text">RTL</span><span aria-hidden="true" class="dashicons dashicons-editor-rtl"></span>
									</button>
									<button type="button" class="translation-actions__help with-tooltip" aria-label="Show help">
										<span class="screen-reader-text">Help</span><span aria-hidden="true" class="dashicons dashicons-editor-help"></span>
									</button>
								</div>
							<?php elseif ( is_user_logged_in() ) : ?>
								You are not allowed to edit this translation.
							<?php else : ?>
								<p class="info">
									<?php
									printf(
										'You <a href="%s">have to log in</a> to edit this translation.',
										esc_url( wp_login_url( gp_url_current() ) )
									);
									?>
								</p>
							<?php endif; ?>
						</div>
					</div>

					<?php
					if ( has_action( 'wporg_translate_suggestions' ) ) {
						?>
						<div class="suggestions-wrapper">
							<?php do_action( 'wporg_translate_suggestions', $t ); ?>
						</div>
						<?php
					}
					?>
				</div>
			</div>

			<div class="editor-panel__right">
				<div class="panel-header">
					<h3><?php _e( 'Meta', 'glotpress' ); ?></h3>
				</div>
				<div class="panel-content">
					<div class="meta">

						<?php if ( $t->translation_status ): ?>
							<div class="status-actions">
							<?php if ( $can_approve_translation ) : ?>
								<?php if ( 'current' !== $t->translation_status ) : ?>
									<button class="approve" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-current_' . $t->id ) ); ?>"><strong>+</strong> <?php _e( 'Approve', 'glotpress' ); ?></button>
								<?php endif; ?>
								<?php if ( 'rejected' !== $t->translation_status ) : ?>
									<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject', 'glotpress' ); ?></button>
								<?php endif; ?>
								<?php if ( 'fuzzy' !== $t->translation_status ) : ?>
									<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>"><strong>~</strong> <?php _e( 'Fuzzy', 'glotpress' ); ?></button>
								<?php endif; ?>
							<?php elseif ( $can_reject_self ): ?>
								<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject Suggestion', 'glotpress' ); ?></button>
								<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>"><strong>~</strong> <?php _e( 'Fuzzy', 'glotpress' ); ?></button>
							<?php endif; ?>
							</div>
						<?php endif; ?>

						<dl>
							<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
							<dd>
								<?php echo display_status( $t->translation_status ); ?>
							</dd>
						</dl>

						<?php if ( $t->translation_added && $t->translation_added != '0000-00-00 00:00:00' ): ?>
							<dl>
								<dt><?php _e( 'Date added:', 'glotpress' ); ?></dt>
								<dd><?php echo $t->translation_added; ?> GMT</dd>
							</dl>
						<?php endif; ?>
						<?php if ( $t->user ) : ?>
							<dl>
								<dt><?php _e( 'Translated by:', 'glotpress' ); ?></dt>
								<dd><?php gp_link_user( $t->user ); ?></dd>
							</dl>
						<?php endif; ?>
						<?php if ( $t->user_last_modified && ( ! $t->user || $t->user->ID !== $t->user_last_modified->ID ) ) : ?>
							<dl>
								<dt><?php
									if ( 'current' === $t->translation_status ) {
										_e( 'Approved by:', 'glotpress' );
									} elseif ( 'rejected' === $t->translation_status ) {
										_e( 'Rejected by:', 'glotpress' );
									} else {
										_e( 'Last updated by:', 'glotpress' );
									}
									?>
								</dt>
								<dd><?php gp_link_user( $t->user_last_modified ); ?></dd>
							</dl>
						<?php endif; ?>

						<dl>
							<dt><?php _e( 'Priority of the original:', 'glotpress' ); ?></dt>
							<?php if ( $can_write ): ?>
								<dd><?php
									echo gp_select(
										'priority-' . $t->original_id,
										GP::$original->get_static( 'priorities' ),
										$t->priority,
										array(
											'class'      => 'priority',
											'tabindex'   => '-1',
											'data-nonce' => wp_create_nonce( 'set-priority_' . $t->original_id ),
										)
									);
									?></dd>
							<?php else : ?>
								<dd><?php echo gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority, 'unknown' ); // WPCS: XSS ok. ?></dd>
							<?php endif; ?>
						</dl>
					</div>

					<?php do_action( 'wporg_translate_meta', $t ); ?>
				</div>
			</div>
		</div>
	</td>
</tr>
