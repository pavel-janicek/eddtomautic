<?php

if (!class_exists('Clvr_EDD_Mautic_Settings')){

    class Clvr_EDD_Mautic_Settings extends Clvr_EDD_Mautic{

        public function __construct()
        {
            add_filter( 'edd_settings_sections_extensions', array($this,'settings_section') );
            add_filter( 'edd_settings_extensions', array($this,'add_settings') );
        }

        public function settings_section($sections){
            $sections[$this->getName().'-settings'] = __( 'Propojení EDD s Mauticem', $this->getName() );
            
			return $sections;
        }
        
        public function add_settings($settings){
            $local_settings = array(
                array(
                    'id' => 'edd_mautic_settings',
                    'name' => '<strong>' . __( 'Mautic Export Settings', 'edd_mautic' ) . '</strong>',
                    'desc' => __( 'Configure the export settings', 'edd_mautic' ),
                    'type' => 'header'
                ),
                array(
                    'id' => 'edd_mautic_mautic_username',
                    'name' =>  __( 'Uživatelské jméno Mautic', 'edd_mautic' ) ,
                    'desc' => __( 'Uživatelské jméno Mautic', 'edd_mautic' ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
                array(
                    'id' => 'edd_mautic_mautic_password',
                    'name' =>  __( 'Heslo Mautic', 'edd_mautic' ) ,
                    'desc' => __( 'Heslo Mautic', 'edd_mautic' ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
                array(
                    'id' => 'edd_mautic_mautic_base_url',
                    'name' =>  __( 'Mautic Base URL', 'edd_mautic' ) ,
                    'desc' => __( 'Enter full address of where Mautic runs:', 'edd_mautic' ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
                array(
                    'id'      => 'edd_mautic_list',
                    'name'    => __( 'Výchozí Segment po objednání', 'edd_mautic'),
                    'desc'    => __( 'Select the segment you wish to subscribe buyers to', 'edd_mautic' ),
                    'type'    => 'select',
                    'options' => $this->getLists()
                ),
                array(
                 'id'      => 'edd_mautic_purchase_list',
                 'name'    => __( 'Výchozí Segment po zaplacení', 'edd_mautic'),
                 'desc'    => __( 'Select the segment you wish to subscribe buyers to', 'edd_mautic' ),
                 'type'    => 'select',
                'options' => $this->getLists()
             ),
           array(
                   'id'       => 'edd_mautic_default_segment',
                   'name'     => __( 'Nepoužívat výchozí segmenty', 'edd_mautic'),
                   'desc'     => __( 'If you select this, you will have to segment users per download', 'edd_mautic' ),
                   'type'    => 'checkbox',
                ),
            );
            if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
				$local_settings = array( $this->getName().'-settings' => $local_settings );
			}
			return array_merge( $settings, $local_settings );
            
        }

        

    }//endclass

}//endif