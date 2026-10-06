<?php
/**
 * Image crop field type.
 *
 * Originally written by Anders Thorborg as part of the ACF Image Crop add-on.
 */

defined( 'ABSPATH' ) || exit;

class ICFA_Field_Image_Crop extends acf_field_image {


    /*
    *  initialize
    *
    *  This function will setup the field type data
    *
    *  @type    function
    *  @date    5/03/2014
    *  @since   5.0.0
    *
    *  @param   n/a
    *  @return  n/a
    */

    function initialize() {

        /*
        *  name (string) Single word, no spaces. Underscores allowed
        */

        $this->name = 'image_crop';


        /*
        *  label (string) Multiple words, can include spaces, visible when selecting a field type
        */

        $this->label = __('Image with user-crop', 'image-crop-field-for-acf');


        /*
        *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
        */

        $this->category = 'content';


        /*
        *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
        */

        $this->defaults = array(
            'force_crop' => 'no',
            'crop_type' => 'hard',
            'preview_size' => 'medium',
            'save_format' => 'id',
            'save_in_media_library' => 'yes',
            'target_size' => 'thumbnail',
            'library' => 'all',
            'retina_mode' => 'no',
            'width' => '',
            'height' => ''
        );

        $this->options = get_option( 'acf_image_crop_settings' );

        // add ajax action to be able to retrieve full image size via javascript
        add_action( 'wp_ajax_icfa_get_image_size', array( &$this, 'crop_get_image_size' ) );
        add_action( 'wp_ajax_icfa_perform_crop', array( &$this, 'perform_crop' ) );


        // add filter to media query function to hide cropped images from media library
        add_filter('ajax_query_attachments_args', array($this, 'filterMediaQuery'));

        // Register extra fields on the media settings page on admin_init
        add_action('admin_init', array($this, 'registerSettings'));

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('image_crop', 'error');
        */

        $this->l10n = array(
            'width_should_be'   => __( 'Width should be at least: ','image-crop-field-for-acf' ),
            'height_should_be'  => __( 'Height should be at least: ','image-crop-field-for-acf' ),
            'selected_width'    => __( 'Selected image width: ','image-crop-field-for-acf' ),
            'selected_height'   => __( 'Selected image height: ','image-crop-field-for-acf' ),
            'size_warning'      => __( 'Warning: The selected image is smaller than the required size!','image-crop-field-for-acf' ),
            'crop_error'        => __( 'Sorry, an error occurred when trying to crop your image:', 'image-crop-field-for-acf' )
        );
    }

    // AJAX handler for retrieving full image dimensions from ID
    public function crop_get_image_size()
    {
        $id = $this->verify_ajax_request( 'image_id' );

        $img = wp_get_attachment_image_src( $id, 'full' );
        if ( ! $img ) {
            wp_send_json_error();
        }

        wp_send_json( array(
            'url' => $img[0],
            'width' => $img[1],
            'height' => $img[2]
        ) );
    }

    /*
    *  verify_ajax_request()
    *
    *  Checks the nonce and the user's permission for the AJAX actions, and that the requested
    *  attachment is an image. Ends the request with an error if any check fails.
    *
    *  @param   $id_key (string) the $_POST key holding the attachment ID
    *  @return  (int) the attachment ID
    */
    function verify_ajax_request( $id_key ) {
        check_ajax_referer( 'icfa_image_crop', 'nonce' );

        // Same permission WordPress requires for browsing and uploading media.
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json( $this->crop_error( __( 'You are not allowed to crop images.', 'image-crop-field-for-acf' ) ), 403 );
        }

        $id = isset( $_POST[ $id_key ] ) ? absint( $_POST[ $id_key ] ) : 0;
        if ( ! $id || ! wp_attachment_is_image( $id ) ) {
            wp_send_json( $this->crop_error( __( 'The selected file is not an image.', 'image-crop-field-for-acf' ) ), 400 );
        }

        return $id;
    }

    function crop_error( $message ) {
        return array(
            'success' => false,
            'error_message' => $message
        );
    }

    function post_int( $key ) {
        return isset( $_POST[ $key ] ) ? absint( round( (float) $_POST[ $key ] ) ) : 0;
    }


    /*
    *  render_field_settings()
    *
    *  Create extra settings for your field. These are visible when editing a field
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $field (array) the $field being edited
    *  @return  n/a
    */

    function render_field_settings( $field ) {

        /*
        *  acf_render_field_setting
        *
        *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
        *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
        *
        *  More than one setting can be added by copy/paste the above code.
        *  Please note that you must also have a matching $defaults value for the field name (font_size)
        */

         // crop_type
        acf_render_field_setting( $field, array(
            'label'         => __('Crop type','image-crop-field-for-acf'),
            'instructions'  => __('Select the type of crop the user should perform','image-crop-field-for-acf'),
            'type'          => 'select',
            'name'          => 'crop_type',
            'layout'        => 'horizontal',
            'class'         => 'crop-type-select',
            'choices'       => array(
                'hard'          => __('Hard crop', 'image-crop-field-for-acf'),
                'min'           => __('Minimal dimensions', 'image-crop-field-for-acf')
            )
        ));

        // target_size
        $sizes = acf_get_image_sizes();
// added
        $instructions = __('Select the target size for this field','image-crop-field-for-acf');
        if ( $this->getOption('retina_mode') ) {
            $instructions .= '<br><br><em><strong>'.__('Retina mode is enabled - user will be required to select an image twice this size','image-crop-field-for-acf').'</strong></em>';
        }
// added END
        $sizes['custom'] = __('Custom size', 'image-crop-field-for-acf');
        acf_render_field_setting( $field, array(
            'label'         => __('Target size','image-crop-field-for-acf'),
            'instructions'  => $instructions,
            'type'          => 'select',
            'name'          => 'target_size',
            'choices'       =>  $sizes
        ));

        // width - conditional: target_size == 'custom'
        acf_render_field_setting( $field, array(
            'label'         => __('Custom target width','image-crop-field-for-acf'),
            'instructions'  => __('Leave blank for no restriction (does not work with hard crop option)','image-crop-field-for-acf'),
            'type'          => 'number',
            'name'          => 'width',
            'append'        => 'px',
            'conditions'    => array(
                'field'     => 'target_size',
                'operator'  => '==',
                'value'     => 'custom'
            )
        ));

        // height - conditional: target_size == 'custom'
        acf_render_field_setting( $field, array(
            'label'         => __('Custom target height','image-crop-field-for-acf'),
            'instructions'  => __('Leave blank for no restriction (does not work with hard crop option)','image-crop-field-for-acf'),
            'type'          => 'number',
            'name'          => 'height',
            'append'        => 'px',
            'conditions'    => array(
                'field'     => 'target_size',
                'operator'  => '==',
                'value'     => 'custom'
            )
        ));

        // force_crop
        acf_render_field_setting( $field, array(
            'label'         => __('Force crop','image-crop-field-for-acf'),
            'instructions'  => __('Force the user to crop the image as soon at it is selected','image-crop-field-for-acf'),
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'name'          => 'force_crop',
            'choices'       =>  array('yes' => __('Yes', 'acf'), 'no' => __('No', 'acf'))
        ));

        // save_in_media_library
        acf_render_field_setting( $field, array(
            'label'         => __('Save cropped image to media library','image-crop-field-for-acf'),
            'instructions'  => __('If the cropped image is not saved in the media library, "Image URL" is the only available return value.','image-crop-field-for-acf'),
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'name'          => 'save_in_media_library',
            'class'         => 'save-in-media-library-select',
            'choices'       =>  array('yes' => __('Yes', 'acf'), 'no' => __('No', 'acf'))
        ));

// only show this setting if the global option for retina mode is not activated
        // retina mode
        if ( !$this->getOption('retina_mode') ) {
            acf_render_field_setting( $field, array(
                'label'         => __('Retina/@2x mode ','image-crop-field-for-acf'),
                'instructions'  => __('Require and crop double the size set for this image. Enable this if you are using plugins like WP Retina 2x.','image-crop-field-for-acf'),
                'type'          => 'radio',
                'layout'        => 'horizontal',
                'name'          => 'retina_mode',
                'choices'       =>  array('yes' => __('Yes', 'acf'), 'no' => __('No', 'acf'))
            ));
        }
        // return_format
        acf_render_field_setting( $field, array(
            'label'         => __('Return Value','acf'),
            'instructions'  => __('Specify the returned value on front end','acf'),
            'type'          => 'radio',
            'name'          => 'save_format',
            'layout'        => 'horizontal',
            'class'         =>  'return-value-select',
            'choices'       => array(
                'object'         => __("Image Array",'acf'),
                'url'           => __("Image URL",'acf'),
                'id'            => __("Image ID",'acf')
            )
        ));

        // library
        acf_render_field_setting( $field, array(
            'label'         => __('Library','acf'),
            'instructions'  => __('Limit the media library choice','acf'),
            'type'          => 'radio',
            'name'          => 'library',
            'layout'        => 'horizontal',
            'choices'       => array(
                'all'           => __('All', 'acf'),
                'uploadedTo'    => __('Uploaded to post', 'acf')
            )
        ));

    }

    /*
    *  render_field_validation_settings()
    *
    *  The parent image field adds min/max dimension, file size and file type settings to the
    *  Validation tab. This field doesn't use them, so don't show them.
    */
    function render_field_validation_settings( $field ) {
    }

    /*
    *  render_field_presentation_settings()
    *
    *  Replaces the parent image field's Presentation tab settings.
    */
    function render_field_presentation_settings( $field ) {

        // preview_size
        acf_render_field_setting( $field, array(
            'label'         => __('Preview Size','acf'),
            'instructions'  => __('Shown when entering data','acf'),
            'type'          => 'select',
            'name'          => 'preview_size',
            'choices'       =>  acf_get_image_sizes()
        ));
    }



    /*
    *  render_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param   $field (array) the $field being rendered
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $field (array) the $field being edited
    *  @return  n/a
    */

    function render_field( $field ) {


       // enqueue
        acf_enqueue_uploader();

        // get data from value
        $imageData = $this->get_image_data($field);

        $url = '';

        $width = 0;
        $height = 0;

        if($field['target_size'] == 'custom'){
            $width = intval($field['width']);
            $height = intval($field['height']);
        }
        else{
            global $_wp_additional_image_sizes;
            $s = $field['target_size'];
            if (isset($_wp_additional_image_sizes[$s])) {
                $width = intval($_wp_additional_image_sizes[$s]['width']);
                $height = intval($_wp_additional_image_sizes[$s]['height']);
            } else {
                $width = intval(get_option($s.'_size_w'));
                $height = intval(get_option($s.'_size_h'));
            }
        }

        // Retina mode
        if($this->getOption('retina_mode') || $field['retina_mode'] == 'yes'){
            $width = $width * 2;
            $height = $height * 2;
        }

        // vars
        $div_atts = array(
            'class'                 => 'acf-image-uploader acf-cf acf-image-crop',
            'data-field-settings'   => json_encode($field),
            'data-crop_type'        => $field['crop_type'],
            'data-target_size'      => $field['target_size'],
            'data-width'            => $width,
            'data-height'           => $height,
            'data-force_crop'       => $field['force_crop'] == 'yes' ? 'true' : 'false',
            'data-save_to_media_library' => $field['save_in_media_library'],
            'data-save_format'      => $field['save_format'],
            'data-preview_size'     => $field['preview_size'],
            'data-library'          => $field['library']
        );
        $input_atts = array(
            'type'                  => 'hidden',
            'name'                  => $field['name'],
            'value'                 => $this->sanitize_value( $field['value'] ),
            'data-name'             => 'id',
            'data-original-image'   => $imageData->original_image,
            'data-cropped-image'    => json_encode($imageData->cropped_image),
            'class'                 => 'acf-image-value'
        );

        // has value?
        if($imageData->original_image){
            $url = $imageData->preview_image_url;
            $div_atts['class'] .= ' has-value';
        }

?>
<div <?php acf_esc_attr_e( $div_atts ); ?>>
    <div class="acf-hidden">
        <input <?php acf_esc_attr_e( $input_atts ); ?>/>
    </div>
    <div class="view show-if-value acf-soh">
        <ul class="acf-hl acf-soh-target">
            <li><a class="acf-icon -pencil dark" data-name="edit" href="#"><i class="acf-sprite-edit"></i></a></li>
            <li><a class="acf-icon -cancel dark" data-name="remove" href="#"><i class="acf-sprite-delete"></i></a></li>
        </ul>
        <img data-name="image" src="<?php echo esc_url( $url ); ?>" alt=""/>
        <div class="crop-section">
            <div class="crop-stage">
                <div class="crop-action">
                    <h4><?php _e('Crop the image','image-crop-field-for-acf'); ?></h4>
                <?php if ($imageData->original_image ): ?>
                    <img class="crop-image" src="<?php echo esc_url( $imageData->original_image_url ); ?>" data-width="<?php echo esc_attr( $imageData->original_image_width ); ?>" data-height="<?php echo esc_attr( $imageData->original_image_height ); ?>" alt="">
                <?php endif ?>
                </div>
                <div class="crop-preview">
                    <h4><?php _e('Preview','image-crop-field-for-acf'); ?></h4>
                    <div class="preview"></div>
                    <div class="crop-controls">
                        <a href="#" class="button button-large cancel-crop-button"><?php _e('Cancel','image-crop-field-for-acf'); ?></a> <a href="#" class="button button-large button-primary perform-crop-button"><?php _e('Crop!','image-crop-field-for-acf'); ?></a>
                    </div>
                </div>
            </div>
            <a href="#" class="button button-large init-crop-button"><?php _e('Crop','image-crop-field-for-acf'); ?></a>
        </div>
    </div>
    <div class="view hide-if-value">
        <p><?php _e('No image selected','acf'); ?> <a data-name="add" class="acf-button button" href="#"><?php _e('Add Image','acf'); ?></a></p>
    </div>
</div>
<?php

    }

    /***
    * Parses the field value into a consistent data object
    ****/
    function get_image_data($field){
        $imageData = new stdClass();
        $imageData->original_image = '';
        $imageData->original_image_width = '';
        $imageData->original_image_height = '';
        $imageData->cropped_image = '';
        $imageData->original_image_url = '';
        $imageData->preview_image_url = '';
        $imageData->image_url = '';

        $data = $this->parse_value( $field['value'] );

        if( ! $data->original_image ){
            // Field has not yet been saved or has no image
            return $imageData;
        }

        // By now, we have at least a saved original image
        $imageData->original_image = $data->original_image;
        $imageAtts = wp_get_attachment_image_src($data->original_image, 'full');
        if ( $imageAtts ) {
            $imageData->original_image_url = $imageAtts[0];
            $imageData->original_image_width = $imageAtts[1];
            $imageData->original_image_height = $imageAtts[2];
        }

        // Set defaults to original image
        $imageData->image_url = $imageData->original_image_url;
        $imageData->preview_image_url = $this->get_image_src($data->original_image, $field['preview_size']);

        // Check if there is a cropped version and set appropriate attributes
        if(is_int($data->cropped_image)){
            // Cropped image was saved to media library ans has an ID
            $imageData->cropped_image = $data->cropped_image;
            $imageData->image_url = $this->get_image_src($data->cropped_image, 'full');
            $imageData->preview_image_url = $this->get_image_src($data->cropped_image, $field['preview_size']);
        }
        elseif(is_object($data->cropped_image)){
            // Cropped image was not saved to media library and is only stored by its URL
            $imageData->cropped_image = $data->cropped_image;
            $imageData->image_url = $this->getAbsoluteImageUrl($data->cropped_image->image);
            $imageData->preview_image_url = $this->getAbsoluteImageUrl($data->cropped_image->preview);
        }
        return $imageData;
    }

    /*
    *  parse_value()
    *
    *  Parses a stored or submitted field value into a validated object with
    *  - original_image: attachment ID, or '' when there is no image
    *  - cropped_image:  attachment ID, an object with "image" and "preview" paths relative
    *                    to the uploads folder, or '' when the image hasn't been cropped
    *  Anything that doesn't match this shape is dropped, so a tampered value can't inject
    *  markup or point outside the uploads folder.
    *
    *  @param   $value (mixed) JSON string, or an attachment ID saved by a regular image field
    *  @return  (object)
    */
    function parse_value( $value ) {
        $data = new stdClass();
        $data->original_image = '';
        $data->cropped_image = '';

        if ( is_numeric( $value ) ) {
            // Field was saved as a regular image field
            $data->original_image = absint( $value );
            return $data;
        }

        if ( ! is_string( $value ) || $value === '' ) {
            return $data;
        }

        $decoded = json_decode( $value );
        if ( ! is_object( $decoded ) ) {
            // Values submitted through a form are slashed
            $decoded = json_decode( wp_unslash( $value ) );
        }

        if ( ! is_object( $decoded ) || ! isset( $decoded->original_image ) || ! is_numeric( $decoded->original_image ) ) {
            // The field has been saved, but has no image
            return $data;
        }

        $data->original_image = absint( $decoded->original_image );
        if ( ! $data->original_image ) {
            return $data;
        }

        $cropped = isset( $decoded->cropped_image ) ? $decoded->cropped_image : '';
        if ( is_numeric( $cropped ) ) {
            $data->cropped_image = absint( $cropped );
        }
        elseif ( is_object( $cropped ) && isset( $cropped->image ) ) {
            $image = $this->sanitize_relative_path( $cropped->image );
            if ( $image ) {
                $preview = isset( $cropped->preview ) ? $this->sanitize_relative_path( $cropped->preview ) : '';
                $data->cropped_image = (object) array(
                    'image' => $image,
                    'preview' => $preview ? $preview : $image
                );
            }
        }

        return $data;
    }

    /*
    *  Encodes a parsed value for storage. Returns '' when there is no image.
    */
    function value_to_string( $data ) {
        if ( ! $data->original_image ) {
            return '';
        }
        return wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    }

    /*
    *  Returns the validated value in its storage format. Values saved by a regular image field
    *  (a plain attachment ID) keep that format until the image is changed or cropped.
    */
    function sanitize_value( $value ) {
        if ( is_numeric( $value ) ) {
            return absint( $value );
        }
        return $this->value_to_string( $this->parse_value( $value ) );
    }

    /*
    *  Validates a cropped image path relative to the uploads folder.
    *  Returns '' for traversal, absolute or stream paths, unexpected characters and non-image files.
    */
    function sanitize_relative_path( $path ) {
        if ( ! is_string( $path ) ) {
            return '';
        }

        $path = ltrim( str_replace( '\\', '/', $path ), '/' );

        if ( $path === '' || validate_file( $path ) !== 0 || strpos( $path, ':' ) !== false || preg_match( '/[\x00-\x1f"\'<>`]/', $path ) ) {
            return '';
        }

        $filetype = wp_check_filetype( $path );
        if ( ! $filetype['type'] || strpos( $filetype['type'], 'image/' ) !== 0 ) {
            return '';
        }

        return $path;
    }


    /*
    *  input_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
    *  Use this action to add CSS + JavaScript to assist your render_field() action.
    *
    *  @type    action (admin_enqueue_scripts)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */



    function input_admin_enqueue_scripts() {
        $dir = plugin_dir_url( __FILE__ );

        wp_register_script('icfa-input', "{$dir}js/input.js", array('acf-input', 'imgareaselect'), ICFA_VERSION);

        wp_register_style('icfa-input', "{$dir}css/input.css", array('acf-input'), ICFA_VERSION);

        // scripts
        wp_enqueue_script(array(
                'icfa-input'
        ));

        wp_localize_script( 'icfa-input', 'icfaImageCrop', array(
            'nonce' => wp_create_nonce( 'icfa_image_crop' )
        ) );

        // styles
        wp_enqueue_style(array(
                'icfa-input',
                'imgareaselect'
        ));


    }

    function perform_crop(){
        $id = $this->verify_ajax_request( 'id' );

        $saveToMediaLibrary = isset($_POST['save_to_media_library']) && $_POST['save_to_media_library'] == 'yes';

        // Only accept registered image sizes for the preview
        $previewSize = isset( $_POST['preview_size'] ) ? (string) wp_unslash( $_POST['preview_size'] ) : '';
        if ( ! in_array( $previewSize, get_intermediate_image_sizes(), true ) ) {
            $previewSize = 'thumbnail';
        }

        $imageData = $this->generate_cropped_image(
            $id,
            $this->post_int( 'x1' ),
            $this->post_int( 'x2' ),
            $this->post_int( 'y1' ),
            $this->post_int( 'y2' ),
            $this->post_int( 'target_width' ),
            $this->post_int( 'target_height' ),
            $saveToMediaLibrary,
            $previewSize
        );
        wp_send_json( $imageData );
    }

    function generate_cropped_image($id, $x1, $x2, $y1, $y2, $targetW, $targetH, $saveToMediaLibrary, $previewSize){
        require_once ABSPATH . "/wp-admin/includes/file.php";
        require_once ABSPATH . "/wp-admin/includes/image.php";

        // Create the variable that will hold the new image data
        $imageData = array();

        // Fetch media library info
        $mediaDir = wp_upload_dir();

        // Get original image info
        $originalImageData = wp_get_attachment_metadata($id);
        $originalFilePath = get_attached_file($id);

        if ( empty( $originalImageData['width'] ) || empty( $originalImageData['height'] ) || ! $originalFilePath ) {
            return $this->crop_error( __( 'The original image could not be found.', 'image-crop-field-for-acf' ) );
        }

        // Keep the selection inside the original image
        $originalWidth = (int) $originalImageData['width'];
        $originalHeight = (int) $originalImageData['height'];
        $x1 = min( $x1, $originalWidth );
        $x2 = min( $x2, $originalWidth );
        $y1 = min( $y1, $originalHeight );
        $y2 = min( $y2, $originalHeight );

        if ( $x2 <= $x1 || $y2 <= $y1 ) {
            return $this->crop_error( __( 'The crop selection is empty.', 'image-crop-field-for-acf' ) );
        }

        // A missing target size means "keep the selection size". Never create an image larger
        // than the original, so a crafted request can't exhaust memory or disk space.
        $targetW = $targetW ? min( $targetW, $originalWidth ) : $x2 - $x1;
        $targetH = $targetH ? min( $targetH, $originalHeight ) : $y2 - $y1;

        // Get image editor from original image path to crop the image
        $image = wp_get_image_editor( $originalFilePath );

        if( is_wp_error( $image ) ){
            return $this->crop_error( $image->get_error_message() );
        }

        // Set quality
        $image->set_quality( apply_filters('acf-image-crop/image-quality', 100) );

        // Crop the image using the provided measurements
        $cropped = $image->crop($x1, $y1, $x2 - $x1, $y2 - $y1, $targetW, $targetH);
        if ( is_wp_error( $cropped ) ) {
            return $this->crop_error( $cropped->get_error_message() );
        }

        // Retrieve original filename and seperate it from its file extension
        $originalFileName = explode('.', basename($originalImageData['file']));

        // Retrieve and remove file extension from array
        $originalFileExtension = array_pop($originalFileName);

        // Generate new base filename
        $targetFileName = implode('.', $originalFileName) . '_' . $targetW . 'x' . $targetH . apply_filters('acf-image-crop/filename_postfix', '_acf_cropped')  . '.' . $originalFileExtension;


        // Generate target path new file using existing media library
        $targetFilePath = $mediaDir['path'] . '/' . wp_unique_filename( $mediaDir['path'], $targetFileName);

        // Get the relative path to save as the actual image url
        $targetRelativePath = str_replace($mediaDir['basedir'] . '/', '', $targetFilePath);

        // Save the image to the target path
        $saved = $image->save($targetFilePath);
        if(is_wp_error($saved)){
            return $this->crop_error( $saved->get_error_message() );
        }

        // If file should be saved to media library, create an attachment for it at get the new attachment ID
        if($saveToMediaLibrary){
            // Generate attachment from created file

            // Get the filetype
            $wp_filetype = wp_check_filetype(basename($targetFilePath), null );
            $attachment = array(
                 'guid' => $mediaDir['url'] . '/' . basename($targetFilePath),
                 'post_mime_type' => $wp_filetype['type'],
                 'post_title' => preg_replace('/\.[^.]+$/', '', basename($targetFilePath)),
                 'post_content' => '',
                 'post_status' => 'inherit'
            );
            $attachmentId = wp_insert_attachment( $attachment, $targetFilePath, 0, true );
            if ( is_wp_error( $attachmentId ) ) {
                return $this->crop_error( $attachmentId->get_error_message() );
            }
            $attachmentData = wp_generate_attachment_metadata( $attachmentId, $targetFilePath );
            wp_update_attachment_metadata( $attachmentId, $attachmentData );
            add_post_meta($attachmentId, 'acf_is_cropped', 'true', true);

            // Add the id to the imageData-array
            $imageData['value'] = $attachmentId;

            // Add the image url
            $imageData['url'] = $this->get_image_src( $attachmentId, 'full' );

            // Add the preview url as well
            $imageData['preview_url'] = $this->get_image_src( $attachmentId, $previewSize );
        }
        // Else we need to return the actual path of the cropped image
        else{
            // Add the image url to the imageData-array
            $imageData['value'] = array('image' => $targetRelativePath);
            $imageData['url'] = $mediaDir['baseurl'] . '/' . $targetRelativePath;

            // Get preview size dimensions
            global $_wp_additional_image_sizes;
            $previewWidth = 0;
            $previewHeight = 0;
            $crop = 0;
            if (isset($_wp_additional_image_sizes[$previewSize])) {
                $previewWidth = intval($_wp_additional_image_sizes[$previewSize]['width']);
                $previewHeight = intval($_wp_additional_image_sizes[$previewSize]['height']);
                $crop = $_wp_additional_image_sizes[$previewSize]['crop'];
            } else {
                $previewWidth = get_option($previewSize.'_size_w');
                $previewHeight = get_option($previewSize.'_size_h');
                $crop = get_option($previewSize.'_crop');
            }

            // Generate preview file path
            $previewFilePath = $mediaDir['path'] . '/' . wp_unique_filename( $mediaDir['path'], 'preview_' . $targetFileName);
            $previewRelativePath = str_replace($mediaDir['basedir'] . '/', '', $previewFilePath);

            // Get image editor from cropped image
            $croppedImage = wp_get_image_editor( $targetFilePath );
            if ( is_wp_error( $croppedImage ) ) {
                return $this->crop_error( $croppedImage->get_error_message() );
            }
            $croppedImage->resize($previewWidth, $previewHeight, $crop);

            // Save the preview
            $croppedImage->save($previewFilePath);

            // Add the preview url
            $imageData['preview_url'] = $mediaDir['baseurl'] . '/' . $previewRelativePath;
            $imageData['value']['preview'] = $previewRelativePath;
        }
        $imageData['success'] = true;
        return $imageData;
    }

    function get_image_src($id, $size = 'thumbnail'){
        $atts = wp_get_attachment_image_src( $id, $size);
        return $atts ? $atts[0] : '';
    }

    function getAbsoluteImageUrl($relativeUrl){
        $mediaDir = wp_upload_dir();
        return $mediaDir['baseurl'] . '/' .  $relativeUrl;
    }

    function getImagePath($relativePath){
        $mediaDir = wp_upload_dir();
        return $mediaDir['basedir'] . '/' .  $relativePath;
    }

    function filterMediaQuery($args){
        // get options
        $options = get_option( 'acf_image_crop_settings' );
        $hide = ( isset($options['hide_cropped']) && $options['hide_cropped'] );

        // If hide option is enabled, do not select items with the acf_is_cropped meta-field
        if($hide){
            if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key' => 'acf_is_cropped',
                'compare' => 'NOT EXISTS'
            );
        }
        return $args;
    }


    function registerSettings(){
        add_settings_section(
            'acf_image_crop_settings',
            __('ACF Image Crop Settings','image-crop-field-for-acf'),
            array($this, 'displayImageCropSettingsSection'),
            'media'
        );

        register_setting(
            'media',                                       // settings page
            'acf_image_crop_settings',                     // option name
// added
// a callback to a new function to set options to false and not to an empty string
// there was an error if you set an option, save and then unset this option and save again
// because the option value could be empty if non of the options is activated
            array($this, 'validateImageCropSettingsSection')
// added END
        );

        add_settings_field(
            'acf_image_crop_hide_cropped',      // id
            __('Hide cropped images from media dialog', 'image-crop-field-for-acf'),              // setting title
            array($this, 'displayHideFromMediaInput'),    // display callback
            'media',                 // settings page
            'acf_image_crop_settings'                  // settings section
        );

        add_settings_field(
            'acf_image_crop_retina_mode',      // id
            __('Enable global retina mode (beta)', 'image-crop-field-for-acf'),              // setting title
            array($this, 'displayRetinaModeInput'),    // display callback
            'media',                 // settings page
            'acf_image_crop_settings'                  // settings section
        );
    }

    function displayHideFromMediaInput(){
        // Get plugin options
        $options = get_option( 'acf_image_crop_settings' );
        $value = ! empty( $options['hide_cropped'] );

        // echo the field
        ?>
    <input name='acf_image_crop_settings[hide_cropped]'
     type='checkbox' <?php echo $value ? 'checked' :  '' ?> value='true' />
        <?php
    }

    function displayRetinaModeInput(){
        // Get plugin options
        $options = get_option( 'acf_image_crop_settings' );
        $value = ! empty( $options['retina_mode'] );

        // echo the field
        ?>
    <input id="acf-image-crop-retina-mode" name='acf_image_crop_settings[retina_mode]'
     type='checkbox' <?php echo $value ? 'checked' :  '' ?> value='true' />
        <?php
    }

    function displayImageCropSettingsSection(){
        echo '';
    }

// added
// a function that sets theoptions value to false
    function validateImageCropSettingsSection($input) {
        return array(
            'hide_cropped' => ! empty( $input['hide_cropped'] ),
            'retina_mode' => ! empty( $input['retina_mode'] )
        );
    }
// added END




    /*
    *  input_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is created.
    *  Use this action to add CSS and JavaScript to assist your render_field() action.
    *
    *  @type    action (admin_head)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */

    /*

    function input_admin_head() {



    }

    */




    /*
    *  input_form_data()
    *
    *  This function is called once on the 'input' page between the head and footer
    *  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and
    *  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
    *  seen on comments / user edit forms on the front end. This function will always be called, and includes
    *  $args that related to the current screen such as $args['post_id']
    *
    *  @type    function
    *  @date    6/03/2014
    *  @since   5.0.0
    *
    *  @param   $args (array)
    *  @return  n/a
    */

    /*

    function input_form_data( $args ) {



    }

    */


    /*
    *  input_admin_footer()
    *
    *  This action is called in the admin_footer action on the edit screen where your field is created.
    *  Use this action to add CSS and JavaScript to assist your render_field() action.
    *
    *  @type    action (admin_footer)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */

    /*

    function input_admin_footer() {



    }

    */


    /*
    *  field_group_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
    *  Use this action to add CSS + JavaScript to assist your render_field_options() action.
    *
    *  @type    action (admin_enqueue_scripts)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */



    function field_group_admin_enqueue_scripts() {

        $dir = plugin_dir_url( __FILE__ );

        wp_register_script('icfa-options', "{$dir}js/options.js", array('jquery'), ICFA_VERSION);
        wp_enqueue_script( 'icfa-options');

        wp_register_style('icfa-options', "{$dir}css/options.css", array(), ICFA_VERSION);
        wp_enqueue_style( 'icfa-options');
    }




    /*
    *  field_group_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is edited.
    *  Use this action to add CSS and JavaScript to assist your render_field_options() action.
    *
    *  @type    action (admin_head)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */

    /*

    function field_group_admin_head() {

    }

    */


    /*
    *  load_value()
    *
    *  This filter is applied to the $value after it is loaded from the db
    *
    *  @type    filter
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $value (mixed) the value found in the database
    *  @param   $post_id (mixed) the $post_id from which the value was loaded
    *  @param   $field (array) the field array holding all the field options
    *  @return  $value
    */

    /*

    function load_value( $value, $post_id, $field ) {

        return $value;

    }

    */


    /*
    *  update_value()
    *
    *  Overrides the parent image field, which expects an attachment ID. Only stores values that
    *  pass parse_value(), re-encoded, so tampered JSON never reaches the database.
    *  ACF passes slashed values on to update_metadata(), which unslashes them, so slash the result.
    */
    function update_value( $value, $post_id, $field ) {
        return wp_slash( $this->sanitize_value( $value ) );
    }




    /*
    *  format_value()
    *
    *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
    *
    *  @type    filter
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $value (mixed) the value which was loaded from the database
    *  @param   $post_id (mixed) the $post_id from which the value was loaded
    *  @param   $field (array) the field array holding all the field options
    *
    *  @return  $value (mixed) the modified value
    */



    function format_value( $value, $post_id, $field ) {

        $data = $this->parse_value( $value );

        // validate
        if( ! $data->original_image ){
            return false;
        }

        // An image that hasn't been cropped (or was migrated from a standard image field) uses the original
        $cropped = $data->cropped_image ? $data->cropped_image : $data->original_image;

        // format
        if( $field['save_format'] == 'url' )
        {
            if(is_object($cropped)){
                return $this->getAbsoluteImageUrl($cropped->image);
            }
            return wp_get_attachment_url( $cropped );
        }
        elseif( $field['save_format'] == 'object' )
        {
            if(is_object($cropped)){
                // Cropped image is not saved to media directory. Get data from original image instead
                $value = $this->getImageArray($data->original_image);
                if ( ! $value ) {
                    return false;
                }

                // Replace URL with cropped version
                $value['url'] = $this->getAbsoluteImageUrl($cropped->image);

                // Calculate and replace sizes
                $imagePath = $this->getImagePath($cropped->image);
                $dimensions = is_file($imagePath) ? getimagesize($imagePath) : false;
                if ( $dimensions ) {
                    $value['width'] = $dimensions[0];
                    $value['height'] = $dimensions[1];
                }
            }
            else{
                $value = $this->getImageArray($cropped);
                if ( ! $value ) {
                    return false;
                }
            }

            // Add original image info
            $value['original_image'] = $this->getImageArray($data->original_image);
            return $value;
        }

        // Image ID (or the relative paths when the crop isn't in the media library)
        return $cropped;

    }

    function getOption($key){
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    function getImageArray($id){
        $attachment = get_post( $id );
        // validate
        if( !$attachment )
        {
            return false;
        }


        // create array to hold value data
        $src = wp_get_attachment_image_src( $attachment->ID, 'full' );

        $imageArray = array(
            'id' => $attachment->ID,
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'title' => $attachment->post_title,
            'caption' => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'mime_type' => $attachment->post_mime_type,
            'url' => $src ? $src[0] : '',
            'width' => $src ? $src[1] : 0,
            'height' => $src ? $src[2] : 0,
            'sizes' => array(),
        );


        // find all image sizes
        $image_sizes = get_intermediate_image_sizes();

        if( $image_sizes )
        {
            foreach( $image_sizes as $image_size )
            {
                // find src
                $src = wp_get_attachment_image_src( $attachment->ID, $image_size );

                if ( ! $src ) {
                    continue;
                }

                // add src
                $imageArray[ 'sizes' ][ $image_size ] = $src[0];
                $imageArray[ 'sizes' ][ $image_size . '-width' ] = $src[1];
                $imageArray[ 'sizes' ][ $image_size . '-height' ] = $src[2];
            }
        }
        return $imageArray;
    }



    /*
    *  validate_value()
    *
    *  This filter is used to perform validation on the value prior to saving.
    *  All values are validated regardless of the field's required setting. This allows you to validate and return
    *  messages to the user if the value is not correct
    *
    *  @type    filter
    *  @date    11/02/2014
    *  @since   5.0.0
    *
    *  @param   $valid (boolean) validation status based on the value and the field's required setting
    *  @param   $value (mixed) the $_POST value
    *  @param   $field (array) the field array holding all the field options
    *  @param   $input (string) the corresponding input name for $_POST value
    *  @return  $valid
    */



    function validate_value( $valid, $value, $field, $input ){
        return $valid;
    }




    /*
    *  delete_value()
    *
    *  This action is fired after a value has been deleted from the db.
    *  Please note that saving a blank value is treated as an update, not a delete
    *
    *  @type    action
    *  @date    6/03/2014
    *  @since   5.0.0
    *
    *  @param   $post_id (mixed) the $post_id from which the value was deleted
    *  @param   $key (string) the $meta_key which the value was deleted
    *  @return  n/a
    */

    /*

    function delete_value( $post_id, $key ) {



    }

    */


    /*
    *  load_field()
    *
    *  This filter is applied to the $field after it is loaded from the database
    *
    *  @type    filter
    *  @date    23/01/2013
    *  @since   3.6.0
    *
    *  @param   $field (array) the field array holding all the field options
    *  @return  $field
    */

    /*

    function load_field( $field ) {

        return $field;

    }

    */


    /*
    *  update_field()
    *
    *  This filter is applied to the $field before it is saved to the database
    *
    *  @type    filter
    *  @date    23/01/2013
    *  @since   3.6.0
    *
    *  @param   $field (array) the field array holding all the field options
    *  @return  $field
    */

    /*

    function update_field( $field ) {

        return $field;

    }

    */


    /*
    *  delete_field()
    *
    *  This action is fired after a field is deleted from the database
    *
    *  @type    action
    *  @date    11/02/2014
    *  @since   5.0.0
    *
    *  @param   $field (array) the field array holding all the field options
    *  @return  n/a
    */

    /*

    function delete_field( $field ) {



    }

    */


}
