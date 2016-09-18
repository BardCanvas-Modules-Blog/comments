<?php
namespace hng2_modules\comments;

use hng2_base\account_record;
use hng2_base\accounts_repository;
use hng2_modules\posts\post_record;
use hng2_modules\posts\posts_repository;
use hng2_repository\abstract_record;

class comment_record extends abstract_record
{
    public $id_post            ; # varchar(32) not null default '',
    public $id_comment         ; # varchar(32) not null default '',
    public $parent_comment     ; # varchar(32) not null default '',
    public $indent_level       ; # tinyint unsigned not null default 0,
    
    public $id_author          ; # varchar(32) not null default '',
    public $author_display_name; # varchar(100) not null default '',
    public $author_email       ; # varchar(100) not null default '',
    public $author_url         ; # varchar(100) not null default '',
    
    public $content            ; # longtext,
    public $status             ; # enum('published', 'reviewing', 'hidden', 'trashed') not null default 'published',
    
    public $creation_date      ; # datetime default null,
    public $creation_ip        ; # varchar(15) not null default '',
    public $creation_host      ; # varchar(255) not null default '',
    public $creation_location  ; # varchar(255) not null default '',
    
    public $karma              ; # int not null default 0,
    public $last_update        ; # datetime default null,
    
    # TODO:                                                                                                        :
    # TODO:  IMPORTANT! All dinamically generated members below should be undefined in get_for_database_insertion! :
    # TODO:                                                                                                        :
    
    # Taken with a group_concat from other tables:
    public $tags_list       = array(); # from post_tags
    public $media_list      = array(); # from post_media
    
    private $_author_account;
    public  $_can_be_replied;
    public  $_replies_count;
    
    /**
     * @var accounts_repository|null
     */
    private static $accounts_repository = null;
    
    /**
     * @var posts_repository|null
     */
    private static $posts_repository = null;
    
    public function set_new_id()
    {
        list($sec, $usec) = explode(".", microtime(true));
        $this->id_comment = "1040" . $sec . sprintf("%05.0f", $usec) . mt_rand(1000, 9999);;
    }
    
    public function set_from_object($object_or_array)
    {
        parent::set_from_object($object_or_array);
    
        if( is_string($this->tags_list) )  $this->tags_list = explode(",", $this->tags_list);
        if( is_string($this->media_list) ) $this->media_list = explode(",", $this->media_list);
    }
    
    /**
     * @param null|account_record $prefetched_author_record
     */
    public function set_author($prefetched_author_record = null)
    {
        if( is_null(self::$accounts_repository) ) self::$accounts_repository = new accounts_repository();
        
        if( ! is_null($prefetched_author_record) )
            $this->_author_account = $prefetched_author_record;
        else
            $this->_author_account = self::$accounts_repository->get($this->id_author);
    }
    
    /**
     * @return account_record
     */
    public function get_author()
    {
        if( is_object($this->_author_account) ) return $this->_author_account;
        
        if( is_null(self::$accounts_repository) ) self::$accounts_repository = new accounts_repository();
        
        $this->_author_account = self::$accounts_repository->get($this->id_author);
        if( is_null($this->_author_account) ) $this->_author_account = new account_record();
        
        return $this->_author_account;
    }
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        unset(
            $return["_author_account"] ,
            $return["_can_be_replied"] ,
            $return["_replies_count"]  ,
            $return["tags_list"]       ,
            $return["media_list"]      
        );
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
    }
    
    public function get_processed_content()
    {
        global $config, $modules;
        
        $contents = $this->content;
        $contents = convert_emojis($contents);
        $contents = autolink_hash_tags($contents, "{$config->full_root_path}/tag/", "/comments");
        
        $config->globals["processing_contents"] = $contents;
        $modules["comments"]->load_extensions("comment_record_class", "get_processed_content");
        $contents = $config->globals["processing_contents"];
        
        return $contents;
    }
    
    /**
     * @return post_record
     */
    public function get_post()
    {
        if( is_null(self::$posts_repository) ) self::$posts_repository = new posts_repository();
        
        return self::$posts_repository->get($this->id_post);
    }
    
    public function get_permalink($fully_qualified = false)
    {
        global $config;
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$this->id_post}#comment_{$this->id_comment}";
        
        return "{$config->full_root_path}/{$this->id_post}#comment_{$this->id_comment}";
    }
    
    public function get_filtered_tags_list()
    {
        global $settings;
        
        $list = $this->tags_list;
        if( empty($list) ) return array();
        
        if( is_string($list) ) $list = explode(",", $list);
        
        # TODO: Detach this to an extension!
        $featureds_tag = $settings->get("modules:posts.featured_posts_tag");
        if( empty($featureds_tag) ) return $list;
        if( $settings->get("modules:posts.show_featured_posts_tag_everywhere") == "true" ) return $list;
        $key = array_search($featureds_tag, $list);
        if( $key === false ) return $list;
        unset($list[$key]);
        
        return $list;
    }
}
