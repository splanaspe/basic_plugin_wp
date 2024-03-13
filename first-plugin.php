<?php

/*
Plugin Name: Post Statistics Plugin
Plugin URI: salvadorplanas.com
Description: Plugin that shows post statistics
Version: 1.0
Author: Tu Nombre
Author URI: Tu URL
License: GPL2
Text Domain: wcpdomain
Domain Path: Languages
*/


// Funcion para añadir el setting page de este plugin
// Creamos una clase para que no haya conflicto en los nombres
class WordCountAndTimePlugin {
    function __construct(){
        //Añadimos el filtro para crear el setting page en el admin
        add_action('admin_menu', array($this, 'adminPage'));

        //Añadimos el filtro para crear los registros del setting en la bd
        add_action('admin_init', array($this, 'settings'));

        // hook que definira el contenido del post
        add_filter('the_content', array($this,'ifWrap'));

        add_action('init', array($this,'languages'));
    }

    function languages(){
        load_plugin_textdomain('wcpdomain',false, dirname(plugin_basename(__FILE__)) . '/Languages' );
    }

    function ifWrap($content){
        if( (is_main_query() AND is_single()) AND 
        (   get_option('wcp_wordcount','1') OR 
            get_option('wcp_charactercount','1') OR 
            get_option('wcp_readtime','1') 
        ) ){
            return $this->createHTML($content);
        } else{
            return $content;
        }
    }

    function createHTML($content){
        $html = '<h3>'. esc_html(get_option('wcp_headline','Post Statistics')) . '</h3><p>';
        
        if(get_option('wcp_wordcount','1') OR get_option('wcp_readtime', '1')){
            $wordCount = str_word_count(strip_tags($content));
        }
        
        if(get_option('wcp_wordcount','1')){
            $html .= esc_html__('This post has','wcpdomain') . ' ' . $wordCount . ' ' . esc_html__('words','wcpdomain') . '. <br>';
        }

        if(get_option('wcp_charactercount','1')){
            $html .= esc_html__('This post has','wcpdomain') . ' '  . strlen(strip_tags($content)) . ' '. esc_html__('characters','wcpdomain') . '. <br>';
        }

        if(get_option('wcp_readtime','1')){
            $html .= esc_html__('This post will take you around','wcpdomain') . ' ' . round($wordCount/225) . ' ' . esc_html__('minute(s) to read it','wcpdomain')  . '. <br>';
        }

        $html .= '</p>';

        if(get_option('wcp_location', '0') == '0'){
            return $html . $content;
        } else {
            return $content . $html;
        }
    }

    function adminPage(){
        // ----- 5 parametros -------
        // 1 - Nombre del settings
        // 2- Nombre de
        // 3 - Permisos
        // 4 - Slug de la pagina de setting del plugin
        // 5 - funcion que configura el plugin
        add_options_page(
            'Word Count Settings',
            esc_html__('Word Count','wcpdomain'),
            'manage_options',
            'word_count_settings_page', 
            array($this,'ourHTML'));
    }

    function ourHTML(){ 
        //Aquí escribimos el código HTMl que definirá los settings del plugin
        ?>

        <div class="wrap">
            <h1> Word Count Settings </h1>
            <form action="options.php" method="POST">
                <?php
                    // Esta funcion es para guardar correctamente los datos
                    settings_fields('wordcountplugin');

                    // Esta funcion de WP mostrara cualquier seccion que hayamos definido
                    do_settings_sections( 'word_count_settings_page' );

                    // BTN de save changes
                    submit_button();
                ?>
            </form>
        </div>

        <?php
    }

    function settings(){
        // Registraremos en esta funcion nuevos registros en la base de datos
        // Tabla wp_options (en la cual hay la info de la web), guardaremos info de las variables que el usuario podrá manipular 

        add_settings_section( 'wcp_first_section', null, null, 'word_count_settings_page');

        // display location
        add_settings_field('wcp_location','Display Location',array($this,'locationHTML'),'word_count_settings_page', 'wcp_first_section');
        register_setting('wordcountplugin','wcp_location',array(
            'sanatize_callback' => array($this, 'sanatizeLocation'), 
            'default' => '0'
        ));

        // headline
        add_settings_field('wcp_headline','Headline Text',array($this,'headlineHTML'),'word_count_settings_page', 'wcp_first_section');
        register_setting('wordcountplugin','wcp_headline',array(
            'sanatize_callback' => 'sanatize_text_field', 
            'default' => 'Post Statistics'
        ));

        // wordcount checkbox
        add_settings_field('wcp_wordcount','Word Count',array($this,'checkboxHTML'),'word_count_settings_page', 'wcp_first_section', array('theName' =>'wcp_wordcount' ));
        register_setting('wordcountplugin','wcp_wordcount',array(
            'sanatize_callback' => 'sanatize_text_field', 
            'default' => '1'
        ));

        // charactercount checkbox
        add_settings_field('wcp_charactercount','Character Count',array($this,'checkboxHTML'),'word_count_settings_page', 'wcp_first_section', array('theName' =>'wcp_charactercount' ));
        register_setting('wordcountplugin','wcp_charactercount',array(
            'sanatize_callback' => 'sanatize_text_field', 
            'default' => '1'
        ));

        // readtime checkbox
        add_settings_field('wcp_readtime','Read Time',array($this,'checkboxHTML'),'word_count_settings_page', 'wcp_first_section', array('theName' =>'wcp_readtime' ));
        register_setting('wordcountplugin','wcp_readtime',array(
            'sanatize_callback' => 'sanatize_text_field', 
            'default' => '1'
        ));

    }

    // Funcion que determina los elementos que tendrá el parametro wcp_location
    function locationHTML(){ ?>
        <select name="wcp_location"> 
            <option value="0" <?php selected( get_option('wcp_location') , '0') ?> > Beginning of the post </option>
            <option value="1" <?php selected( get_option('wcp_location') , '1') ?> > End of the post </option>
        </select>
    <?php
    }

    function headlineHTML(){ ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>"> 
    <?php 
    }

    function checkboxHTML($args){ ?>
        <input type="checkbox" name="<?php echo $args['theName']; ?>" value="1" <?php checked(get_option($args['theName'],'1')); ?>> 
    <?php 
    }

    //Function que comprueba que el valor sea 1 o 0 por seguridad
    function sanatizeLocation($input){
        if( $input != '0' AND $input != '1'){
            add_settings_error( 'wcp_location', 'wcp_location_error','Display location must be either beginning or end' );
            return get_option('wcp_location');
        } 
        return $input;
        
    }
}

// Instanciamos la clase del plugin
$wordCountAndTimePlugin = new WordCountAndTimePlugin();





