<?php
if (!class_exists('Clvr_EDD_Mautic')){

    class Clvr_EDD_Mautic{
        const PAID = 'paid';
        const ORDER = 'order';
        protected $name = 'eddtomautic';
        private $settings;

        public function __construct()
        {
            require 'class_edd_mautic_settings.php';
            $this->settings = new Clvr_EDD_Mautic_Settings();
            add_action( 'add_meta_boxes', array($this,'add_metabox') );
            add_filter( 'edd_metabox_fields_save', array($this,'save_metabox') );
            add_shortcode('mautic_segment', array($this,'eddtomautic_segment_plus_shortcode'));
            add_shortcode('mautic_asset', array($this,'eddtomautic_asset_plus_shortcode'));
            add_action( 'edd_complete_purchase', array($this,'edd_mautic_send_after_complete_payment'));
            add_action( 'edd_insert_payment',  array($this,'edd_mautic_send_after_payment'), 10, 2 );
        }

        public function getName(){
            return $this->name;
        }

        public function getApiContext($context){
            $initAuth   = new Mautic\Auth\ApiAuth();
            $settings = self::get_options();
            $auth       = $initAuth->newAuth($settings,'BasicAuth');
            $apiUrl     = self::get_base_url();
            $api        = new Mautic\MauticApi();
            $contextApi = $api->newApi($context, $auth, $apiUrl);
            return $contextApi;
        }

        public function add_metabox() {
            $id = $this->getName();
            if ( current_user_can( 'edit_product', get_the_ID() ) ) {
                add_meta_box( 'edd_' . $id, 'Segmenty po objednání', array($this,'render_metabox') , 'download', 'side' );
                add_meta_box( 'edd_payment_' . $id, 'Segmenty po zaplacení', array($this,'render_payment_metabox') , 'download', 'side' );
                add_meta_box( 'edd_unsubscribe_' . $id, 'Odhlásit segmenty po objednání', array($this,'render_unsubscribe_metabox') , 'download', 'side' );
                add_meta_box( '_edd_payment_unsubscribe_' . $id, 'Odhlásit segmenty po zaplacení', array($this,'render_payment_unsubscribe_metabox') , 'download', 'side' );
            }
        }

        public function getLists(){
            try{
                $contactApi = $this->getApiContext('contacts');
            }catch(Exception $e){
                return [
                    -1 => 'Selhal jsem na NewApi callu'
                    ];
            }
            try{
                $allLists = $contactApi->getSegments();
                foreach ($allLists as $listitem){
                    if (isset($listitem['id']) and isset($listitem['name'])){
                        $lists[$listitem['id']] = $listitem['name'];
                    }
                }
                if (empty($lists)){
                    return [
                        -1 => 'Mautic vratil prazdne seznamy'
                        ];
                }
                $lists[-1] = 'Nechci využívat segment pro tuto akci';
                return $lists;

            }catch(Exception $ee){
                return [
                    -1 => 'Selhal jsem na poslednim catch'
                    ];
            }

        }

        public static function get_options(){
            global $edd_options;
            $username = $edd_options['edd_mautic_mautic_username'];
            $password = $edd_options['edd_mautic_mautic_password'];
            $settings = [
                'userName' => $username,
                'password' => $password
              ];
              return $settings;
        }

        public static function get_base_url() {
            global $edd_options;
            return $edd_options['edd_mautic_mautic_base_url'];
        }

        public function render_metabox() {

            $id = $this->getName();

            echo '<p>' . __( 'Select the segments you wish buyers to be subscribed to when purchasing.', 'eddtomautic' ) . '</p>';

            $checked = (array) get_post_meta( $post->ID, '_edd_' . esc_attr( $id ), true );
            foreach( self::getLists() as $list_id => $list_name ) {
                echo '<label>';
                    echo '<input type="checkbox" name="_edd_' . esc_attr( $id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
                    echo '&nbsp;' . $list_name;
                echo '</label><br/>';


            }
        }

        public function render_payment_metabox() {

                global $post;

                $id = $this->getName();

                echo '<p>' . __( 'Zapsat do segmentů po zaplacení', 'eddtomautic' ) . '</p>';

                $checked = (array) get_post_meta( $post->ID, '_edd_payment_' . esc_attr( $id ), true );
                foreach( self::getLists() as $list_id => $list_name ) {
                    echo '<label>';
                        echo '<input type="checkbox" name="_edd_payment_' . esc_attr( $id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
                        echo '&nbsp;' . $list_name;
                    echo '</label><br/>';


                }
            }

        public function render_unsubscribe_metabox(){

            global $post;

            $id = $this->getName();
            echo '<p>' . __( 'Upon purchasing, the user will be removed from below selected segments:', 'eddtomautic' ) . '</p>';
            $checked = (array) get_post_meta( $post->ID, '_edd_unsubscribe_' . esc_attr( $id ), true );
            foreach( self::getLists() as $list_id => $list_name ) {
                echo '<label>';
                    echo '<input type="checkbox" name="_edd_unsubscribe_' . esc_attr( $id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
                    echo '&nbsp;' . $list_name;
                echo '</label><br/>';
            }
        }

        public function render_payment_unsubscribe_metabox(){

            global $post;
            $id = $this->getName();
            echo '<p>' . __( 'Upon purchasing, the user will be removed from below selected segments:', 'eddtomautic' ) . '</p>';
            $checked = (array) get_post_meta( $post->ID, '_edd_payment_unsubscribe_' . esc_attr( $id ), true );
            foreach( self::getLists() as $list_id => $list_name ) {
                echo '<label>';
                    echo '<input type="checkbox" name="_edd_payment_unsubscribe_' . esc_attr( $id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
                    echo '&nbsp;' . $list_name;
                echo '</label><br/>';
            }
        }

        public function save_metabox( $fields ) {


            $id = $this->getName();

            $fields[] = '_edd_' . esc_attr( $id );
            $fields[] = '_edd_payment_' . esc_attr( $id );
        $fields[] = '_edd_unsubscribe_' . esc_attr( $id );
            $fields[] = '_edd_payment_unsubscribe_' . esc_attr( $id );
            return $fields;
        }

        public function eddtomautic_subscribe_user($user_id,$segment_id){
            return $this->manipulate_segment($user_id,$segment_id);
        }

        public function eddtomautic_unsubscribe_user($user_id,$segment_id){
            return $this->manipulate_segment($user_id,$segment_id,false);
        }

        public function manipulate_segment($user_id,$segment_id,$subscribe=true){
            $segmentApi = $this->getApiContext('segments');
            if ($subscribe){
                $response = $segmentApi->addContact($segment_id, $user_id);
              }else{
                $response = $segmentApi->removeContact($segment_id, $user_id);
              }

              return $response;
        }

        public function eddtomautic_segment_plus_shortcode($atts = [], $content = null, $tag = ''){

            // normalize attribute keys, lowercase
            $atts = array_change_key_case((array)$atts, CASE_LOWER);
            // override default attributes with user attributes
            $wporg_atts = shortcode_atts([
                                             'id' => 1,
                                                                                 'count' => 0,
                                         ], $atts, $tag);
            $segmentApi = $this->getApiContext('leads');
            $allLists = $segmentApi->getLists();
            $segment_alias = $allLists[$wporg_atts['id']]['alias'];
            $search_filter = "segment:" .$segment_alias;
            $contactsApi = $this->getApiContext('contacts');
            $asset_download = $contactsApi->getList($search_filter,0,0);
            $super_total = $asset_download['total'] + $wporg_atts['count'];

            // start output
             $o = '';
            $o = $o .$super_total;
            // enclosing tags


             return $o;
        }

        public function eddtomautic_asset_plus_shortcode($atts = [], $content = null, $tag = ''){

            // normalize attribute keys, lowercase
            $atts = array_change_key_case((array)$atts, CASE_LOWER);
            // override default attributes with user attributes
            $wporg_atts = shortcode_atts([
                                             'id' => 1,
                                                                                 'count' => 0,
                                         ], $atts, $tag
                        );
            $assetApi = $this->getApiContext('assets');
            $aset = $assetApi->get($wporg_atts['id']);
            $asset_download = $aset['asset']['downloadCount'];
            $super_total = $asset_download + $wporg_atts['count'];
            // start output
            $o = '';
                $o = $o .$super_total;
                // enclosing tags


            return $o;
        }

        public function edd_mautic_send_after_complete_payment( $payment_id ){


            global $edd_options;
            $contactApi = $this->getApiContext('contacts');
            $user_info = edd_get_payment_meta_user_info( $payment_id );
        $date = new DateTime();
        $mauticUser = array(
                           'firstname' => isset($user_info['first_name']) ? $user_info['first_name'] : '',
                           'lastname'	=> isset($user_info['lasr_name']) ? $name[1] : '',
                           'email'		=> $user_info['email'],
                           'lastActive' => $date->format('c'),
                       );
        $user_id = get_post_meta($payment_id,'eddtomautic_user_id',true);
        if(!$user_id){
            $result = $contactApi->create($mauticUser);
            $user_id = $result['contact']['id'];
        }


        if (isset($result['error']))
                       {
                           //echo('onUserAfterSave::leadApi::create - response: ' . $result['error']['code'] . ": " . $result['error']['message']);
                       }

           $subscribe_list = $this->eddtomautic_sanitize_lists($payment_id, false, true);

           foreach ($subscribe_list as $list_key=>$subscribe_segment_id){

                 $expectation = $this->eddtomautic_subscribe_user($user_id,$subscribe_segment_id);

           }

           $unsubscribe_list = $this->eddtomautic_sanitize_lists($payment_id, true, true);
           if(!empty($unsubscribe_list)){
             foreach($unsubscribe_list as $segment_key => $segment_id){
              $result_unsubscribe = $this->eddtomautic_unsubscribe_user($user_id,$segment_id);
           }
        }

    }


    public function edd_mautic_send_after_payment($payment_id){

        global $edd_options;
        $contactApi = $this->getApiContext('contacts');
        $user_info = edd_get_payment_meta_user_info( $payment_id );
        $date = new DateTime();
        $mauticUser = array(
                           'firstname' => isset($user_info['first_name']) ? $user_info['first_name'] : '',
                           'lastname'	=> isset($user_info['lasr_name']) ? $name[1] : '',
                           'email'		=> $user_info['email'],
                           'lastActive' => $date->format('c'),
                       );
        $user_id = get_post_meta($payment_id,'eddtomautic_user_id',true);
        if(!$user_id){
            $result = $contactApi->create($mauticUser);
            $user_id = $result['contact']['id'];
        }


        if (isset($result['error']))
                       {
                           //echo('onUserAfterSave::leadApi::create - response: ' . $result['error']['code'] . ": " . $result['error']['message']);
                       }

           $subscribe_list = $this->eddtomautic_sanitize_lists($payment_id, false);

           foreach ($subscribe_list as $list_key=>$subscribe_segment_id){

                 $expectation = $this->eddtomautic_subscribe_user($user_id,$subscribe_segment_id);

           }

           $unsubscribe_list = $this->eddtomautic_sanitize_lists($payment_id, true);
           if(!empty($unsubscribe_list)){
             foreach($unsubscribe_list as $segment_key => $segment_id){
              $result_unsubscribe = $this->eddtomautic_unsubscribe_user($user_id,$segment_id);
           }
        }

    }

    public function eddtomautic_sanitize_lists($payment_id,$unsubscribe,$paid=false){

        global $edd_options;

       if ($unsubscribe){
             if($paid){
                 $first_part = '_edd_payment_unsubscribe_';
             }else {
                 $first_part = '_edd_unsubscribe_';
             }
       }else{
             if($paid){
                 $first_part = '_edd_payment_';
             }else {
                 $first_part = '_edd_';
             }

       }
       $meta = get_post_meta ($payment_id, '_edd_payment_meta', true);
         $downloads = $meta['downloads'];
         $download_ids = array();
         foreach ($downloads as $download){
             $download_ids[] = $download['id'];

         }
         $id = $this->getName();
         $desired_meta_field = $first_part . $id;
         foreach ($download_ids as $download_id){
             $lists[]     =get_post_meta( $download_id, $desired_meta_field, true );

         }
         if ((!$edd_options['edd_mautic_default_segment']) && (!$unsubscribe) && (!$paid)){
             $lists[] = $edd_options['edd_mautic_list'];
         }
             if($paid && (!$unsubscribe) && (!$edd_options['edd_mautic_default_segment'])){
                 $lists[] = $edd_options['edd_mautic_purchase_list'];
             }
         $sanitized_list = array();
         $i = 0;
         foreach ($lists as $list_item){
             if (is_array($list_item)){
                 foreach($list_item as $key=>$value){
                     $sanitized_list[]=$value;
                 }
             }else{
                 $sanitized_list[]=$list_item;

             }
         }
         $sanitized_list = array_unique($sanitized_list);
         return $sanitized_list;
     }


    } //end class

} // end if
