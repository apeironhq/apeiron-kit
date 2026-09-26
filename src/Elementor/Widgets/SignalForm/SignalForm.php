<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SignalForm;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\SignalForm\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\SignalForm\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SignalForm extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	public function get_name() {
		return 'apeiron-signal-form';
	}

	public function get_title() {
		return __( 'Form WhatsApp', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-form';
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-signal-form';

		return array_values( array_unique( $styles ) );
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-signal-form-js';

		return array_values( array_unique( $scripts ) );
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings     = (array) $this->get_settings_for_display();
		$raw_settings = (array) $this->get_settings();
		$fields       = is_array( $settings['fields'] ?? null ) ? $settings['fields'] : [];
		$raw_fields   = is_array( $raw_settings['fields'] ?? null ) ? $raw_settings['fields'] : [];

		if ( empty( $fields ) ) {
			if ( $this->is_elementor_editor_preview() ) {
				$message = __( 'Tambahkan minimal satu field agar Form WhatsApp dapat ditampilkan.', 'apeiron-kit' );
				require __DIR__ . '/Partials/empty-state.php';
			}
			return;
		}

		$prepared_fields = $this->prepare_fields( $fields, $raw_fields );

		if ( empty( $prepared_fields ) ) {
			if ( $this->is_elementor_editor_preview() ) {
				$message = __( 'Field belum valid. Periksa label atau token pada pengaturan Form WhatsApp.', 'apeiron-kit' );
				require __DIR__ . '/Partials/empty-state.php';
			}
			return;
		}

		$phone_number = $this->get_setting_string( $settings, $raw_settings, 'phone_number', '628123456789' );
		$form_title   = $this->get_setting_string( $settings, $raw_settings, 'form_title', __( 'Konfirmasi Kehadiran', 'apeiron-kit' ) );
		$button_text  = $this->get_setting_string( $settings, $raw_settings, 'button_text', __( 'Kirim via WhatsApp', 'apeiron-kit' ) );
		$data         = [
			'phone'               => preg_replace( '/\D/', '', $phone_number ),
			'template'            => $this->get_setting_string( $settings, $raw_settings, 'message_template', 'Halo, saya [nama] akan hadir bersama [jumlah] orang. [konfirmasi]. Pesan: [ucapan]' ),
			'fields'              => array_map(
				static function ( array $field ): array {
					return [
						'key'   => $field['_apeiron_key'] ?? '',
						'name'  => $field['name'] ?? '',
						'label' => $field['label'] ?? '',
					];
				},
				$prepared_fields
			),
			'successMessage'      => $this->get_setting_string( $settings, $raw_settings, 'success_message', __( 'WhatsApp akan terbuka untuk mengirim pesan.', 'apeiron-kit' ) ),
			'validationMessage'   => $this->get_setting_string( $settings, $raw_settings, 'validation_message', __( 'Lengkapi field yang wajib diisi.', 'apeiron-kit' ) ),
			'invalidTemplateMessage' => $this->get_setting_string( $settings, $raw_settings, 'invalid_template_message', __( 'Ada token pesan yang belum cocok dengan field form.', 'apeiron-kit' ) ),
			'invalidPhoneMessage' => $this->get_setting_string( $settings, $raw_settings, 'invalid_phone_message', __( 'Nomor WhatsApp tujuan belum valid.', 'apeiron-kit' ) ),
		];

		/**
		 * Filter the browser configuration before it is serialized into markup.
		 *
		 * @since 1.1.0
		 *
		 * @param array $data     SignalForm runtime configuration.
		 * @param array $settings Resolved Elementor settings.
		 * @param self  $widget   Widget instance.
		 */
		$data = (array) apply_filters( 'apeiron_signal_form_render_context', $data, $settings, $this );
		$title_id = 'apeiron-signal-form-title-' . sanitize_key( (string) $this->get_id() );

		require __DIR__ . '/Partials/signal-form.php';
	}

	private function prepare_fields( array $fields, array $raw_fields = [] ): array {
		$prepared = [];
		$used     = [];

		foreach ( $fields as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$raw_field = is_array( $raw_fields[ $index ] ?? null ) ? $raw_fields[ $index ] : [];
			$key_field = $field;
			if ( is_scalar( $raw_field['label'] ?? null ) ) {
				$key_field['label'] = (string) $raw_field['label'];
			}
			$field['label'] = $this->get_repeater_text( $field, $raw_field, 'label' );
			$field['placeholder'] = $this->get_repeater_text( $field, $raw_field, 'placeholder' );

			$key = $this->resolve_field_key( $key_field, (int) $index, $used );

			$field['_apeiron_key'] = $key;
			$field['label']        = trim( is_scalar( $field['label'] ?? null ) ? (string) $field['label'] : '' );
			$field['label']        = '' !== $field['label'] ? $field['label'] : ucwords( str_replace( [ '_', '-' ], ' ', $key ) );
			$field['type']         = is_string( $field['type'] ?? null ) ? $field['type'] : 'text';
			$field['required']     = 'yes' === (string) ( $field['required'] ?? 'yes' ) ? 'yes' : 'no';

			$prepared[] = $field;
		}

		return $prepared;
	}

	private function resolve_field_key( array $field, int $index, array &$used ): string {
		$candidate = trim( (string) ( $field['name'] ?? '' ) );

		if ( '' === $candidate ) {
			$candidate = trim( (string) ( $field['label'] ?? '' ) );
		}

		$candidate = preg_replace( '/\s+/', '_', strtolower( $candidate ) );
		$key       = sanitize_key( (string) $candidate );

		if ( '' === $key ) {
			$key = 'field_' . ( $index + 1 );
		}

		$base   = $key;
		$suffix = 2;

		while ( isset( $used[ $key ] ) ) {
			$key = $base . '_' . $suffix;
			$suffix++;
		}

		$used[ $key ] = true;

		return $key;
	}

	private function get_field_id( array $field ): string {
		$key = sanitize_key( (string) ( $field['_apeiron_key'] ?? $field['name'] ?? 'field' ) );

		return 'apeiron-signal-field-' . sanitize_key( (string) $this->get_id() ) . '-' . ( '' !== $key ? $key : 'field' );
	}

	private function get_setting_string( array $settings, array $raw_settings, string $key, string $fallback = '' ): string {
		$value = is_scalar( $settings[ $key ] ?? null ) ? (string) $settings[ $key ] : '';
		if ( '' !== trim( $value ) || empty( $raw_settings['__dynamic__'][ $key ] ) ) {
			return $value;
		}

		$raw_value = is_scalar( $raw_settings[ $key ] ?? null ) ? (string) $raw_settings[ $key ] : '';

		return '' !== trim( $raw_value ) ? $raw_value : $fallback;
	}

	private function get_repeater_text( array $field, array $raw_field, string $key ): string {
		$value = is_scalar( $field[ $key ] ?? null ) ? (string) $field[ $key ] : '';
		if ( '' !== trim( $value ) || empty( $raw_field['__dynamic__'][ $key ] ) ) {
			return $value;
		}

		return is_scalar( $raw_field[ $key ] ?? null ) ? (string) $raw_field[ $key ] : '';
	}

	private function render_input( array $field ): string {
		$name        = sanitize_key( $field['_apeiron_key'] ?? $field['name'] ?? '' );
		$label       = is_scalar( $field['label'] ?? null ) ? (string) $field['label'] : $name;
		$placeholder = esc_attr( is_scalar( $field['placeholder'] ?? null ) ? (string) $field['placeholder'] : '' );
		$required    = 'yes' === ( $field['required'] ?? 'yes' ) ? ' required aria-required="true"' : '';
		$field_id    = $this->get_field_id( $field );

		if ( $field['type'] === 'textarea' ) {
			return sprintf(
				'<textarea id="%1$s" name="%2$s" placeholder="%3$s"%4$s></textarea>',
				esc_attr( $field_id ),
				esc_attr( $name ),
				$placeholder,
				$required
			);
		}

		$allowed_types = [ 'text', 'email', 'tel', 'number', 'url', 'date' ];
		$type          = in_array( $field['type'] ?? 'text', $allowed_types, true ) ? $field['type'] : 'text';
		$input_mode    = '';

		if ( 'number' === $type ) {
			$input_mode = ' inputmode="numeric"';
			$input_mode .= $this->render_number_attribute( 'min', $field['number_min'] ?? 1 );
			$input_mode .= $this->render_number_attribute( 'max', $field['number_max'] ?? '' );
			$input_mode .= $this->render_number_attribute( 'step', $field['number_step'] ?? 1 );
		} elseif ( 'tel' === $type ) {
			$input_mode = ' inputmode="tel" autocomplete="tel"';
		} elseif ( 'email' === $type ) {
			$input_mode = ' autocomplete="email"';
		} elseif ( 'url' === $type ) {
			$input_mode = ' inputmode="url"';
		} elseif ( false !== stripos( (string) $name, 'nama' ) || false !== stripos( (string) $label, 'nama' ) ) {
			$input_mode = ' autocomplete="name"';
		}

		return sprintf(
			'<input id="%1$s" type="%2$s" name="%3$s" placeholder="%4$s"%5$s%6$s />',
			esc_attr( $field_id ),
			esc_attr( $type ),
			esc_attr( $name ),
			$placeholder,
			$required,
			$input_mode
		);
	}

	private function render_number_attribute( string $attribute, $value ): string {
		if ( '' === $value || null === $value ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		return sprintf( ' %1$s="%2$s"', esc_attr( $attribute ), esc_attr( (string) $value ) );
	}

	private function render_confirmation_select( array $settings ): void {
		$options    = is_array( $settings['confirmation_options'] ?? null ) ? $settings['confirmation_options'] : [];
		$raw_settings = (array) $this->get_settings();
		$raw_options  = is_array( $raw_settings['confirmation_options'] ?? null ) ? $raw_settings['confirmation_options'] : [];
		$label      = $this->get_setting_string( $settings, $raw_settings, 'confirmation_label', __( 'Konfirmasi Kehadiran', 'apeiron-kit' ) );
		$field_name = 'konfirmasi';
		$field_id   = 'apeiron-confirmation-' . sanitize_key( (string) $this->get_id() );

		if ( empty( $options ) ) {
			return;
		}

		foreach ( $options as $index => &$option ) {
			if ( is_array( $option ) ) {
				$raw_option = is_array( $raw_options[ $index ] ?? null ) ? $raw_options[ $index ] : [];
				$option['option_text'] = $this->get_repeater_text( $option, $raw_option, 'option_text' );
			}
		}
		unset( $option );

		$options = array_values(
			array_filter(
				$options,
				static function ( $option ): bool {
					if ( ! is_array( $option ) ) {
						return false;
					}
					return '' !== trim( (string) ( $option['option_text'] ?? '' ) );
				}
			)
		);

		if ( empty( $options ) ) {
			return;
		}

		?>
		<label class="apeiron-signal-form__field apeiron-signal-form__confirmation" for="<?php echo esc_attr( $field_id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" class="apeiron-signal-form__select" required aria-required="true">
				<option value="" selected disabled><?php esc_html_e( 'Pilih konfirmasi', 'apeiron-kit' ); ?></option>
				<?php foreach ( $options as $option ) : ?>
					<?php
					$option_text = is_scalar( $option['option_text'] ?? null ) ? (string) $option['option_text'] : '';
					?>
					<option value="<?php echo esc_attr( $option_text ); ?>">
						<?php echo esc_html( $option_text ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}
}
