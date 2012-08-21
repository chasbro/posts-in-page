<?php
/**
    Plugin name: Posts in Page
    Author: dgilfoy, ivycat, sewmyheadon
    Description: Easily add one or more posts to any page using simple shortcodes. Supports categories, tags, custom post types, custom taxonomies, and more.
    Version: 1.0.4
    
   Shortcode usage:
    [ic_add_posts]  - Add all posts to a page (limit to what number posts in WordPress is set to).  This essentially makes the page look like a blog.
    [ic_add_posts post_type='post_type' ids='1,2,3'] - show posts with certain IDs (currently only one post type per category)
    [ic_add_posts id='1'] - show a single post with the given ID ( must give post type if not post )
    [ic_add_posts showposts='5'] - limit number of posts (or override default setting)
    [ic_add_posts orderby='title' order='ASC'] - orderby title - supports all WordPress orderby variables.  Order is optional, WP default 
    [ic_add_posts category='category-slug']  - Show posts within a specific category.  Uses slugs, and can have multiple categories separate by commas. category-1,category2, etc (no spaces.)
    [ic_add_posts post_type='post-type'] - Show posts that belong to a specific post type (only one post type right now)
    [ic_add_posts tax='taxonomy' term='term'] - limit posts to those that exist in a taxonomy and have a specific term.  Both are required for either one to work.
    [ic_add_posts template='template-in-theme-dir.php'] - In case you want to style your markup, add meta data, etc.  Each shortcode can reference a different template.  These templates must exist in the theme directory.
    Or any combination above.
**/

class AddPostsToPage{
    
    protected $args;
    
    public function __construct(){
        add_shortcode( 'ic_add_posts', array( &$this, 'posts_in_page' ) );
        add_shortcode( 'ic_add_post', array( &$this, 'post_in_page' ) );
    }
    
    public function posts_in_page( $atts ){
        extract( shortcode_atts( array(
            'category' => false,
            'post_type' => false,
            'tax' => false,
            'term' => false,
            'showposts' => 10,
            'tag' => false,
            'template' => false,
            'ids' => false,
            'orderby' => false,
            'order' => false
        ), $atts ) );
        self::set_args( $atts );
        return self::output_posts();
    }
    
    protected function output_posts(){
        $page_posts = new WP_Query( $this->args );
        $output = '';
        if( $page_posts->have_posts() ): while( $page_posts->have_posts()):
        $output .= self::add_template_part( $page_posts );
        endwhile; endif;
        wp_reset_postdata();
        return $output;
    }
    
    public function post_in_page( $atts ){
        $args = array( 'post_type' => (  $atts['post_type'] ) ? $atts['post_type'] : 'post' );
        if( $atts['id'] ) $args['post_in'] = array( $atts['id'] );
        $args['posts_per_page'] = 1;
        $page_posts = new WP_Query( $args );
        $output = '';
        if( $page_posts->have_posts() ): while( $page_posts->have_posts()):
        $output .= self::add_template_part( $page_posts );
        endwhile; endif;
        wp_reset_postdata();
        return $output;
    }
    
    protected function set_args( $atts ){
        global $wp_query;
        $this->args = array( 'post_type' => (  $atts['post_type'] ) ? $atts['post_type'] : 'post' );
        if($atts['ids'] ){
            $post_ids = explode( ',', $atts['ids'] );
            $this->args['post_in'] = $post_ids;
            $this->args['posts_per_page'] = count( $post_ids );
        }
        if( $atts['orderby'] ){
            $this->args['orderby'] = $atts['orderby'];
            if( $atts['order'] ) $this->args['order'] = $atts['order'];
        }
        if( $atts['template'] ) $this->args['template'] = $atts['template'];
        if( $atts['category'] ){
            $cats = explode( ',', $atts['category'] );
            $this->args['category_name'] = ( count( $cats ) > 1 ) ? $cats : $atts['category'];
        }
        if( $atts['tax'] ){
            if( $atts['term'] ){
                $terms = explode( ',', $atts['term'] );
                $this->args['tax_query'] = array(
                    array( 'taxonomy' => $atts['tax'], 'field' => 'slug', 'terms' => ( count( $terms ) > 1 ) ? $terms : $atts['term'] )
                );
            }
        }
        if( $atts['tag'] ){
            $tags = explode( ',', $atts['category'] );
            $this->args['tag'] = ( count( $tags ) > 1 ) ? $tags : $atts['tag'];
        }
        if( !$this->args['posts_per_page'] ) $this->args[ 'posts_per_page' ] = $atts['showposts'];
        if( $wp_query->query_vars['page'] > 1 ){
            $this->args['paged'] = $wp_query->query_vars['page'];
        }
    }
    
    protected function has_theme_template(){
        $template_file = ( $this->args['template'] ) ? self::current_theme_path()  . '/' . $this->args['template'] : self::current_theme_path() . '/posts_loop_template.php';
        
        return ( file_exists( $template_file ) ) ? $template_file : false;
    }
    
   protected function add_template_part( $ic_posts, $singles=false ){
        if( $singles ){
            setup_postdata( $ic_posts );
        }else{
            $ic_posts->the_post();
        }
        ob_start();
        require ( $file_path = self::has_theme_template() ) ? str_replace( site_url(), '', $file_path ) : 'posts_loop_template.php';
        $output .= ob_get_contents();
        return ob_get_clean();
   }
    
    protected function current_theme_path(){
        $theme_data = explode( '/', get_bloginfo( 'stylesheet_directory' ) );
        $theme_path = get_theme_root();
        return $theme_path . '/' . $theme_data[ count( $theme_data ) -1 ];
    }
    
} new AddPostsToPage();