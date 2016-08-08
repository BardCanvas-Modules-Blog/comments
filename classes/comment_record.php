<?php
namespace hng2_modules\comments;

use hng2_base\account;
use hng2_base\repository\abstract_record;

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
    
    private $_author_account;
    
    public function set_new_id()
    {
        $this->id_comment = uniqid();
    }
    
    /**
     * @param null|account $prefetched_author_record
     */
    public function set_author($prefetched_author_record = null)
    {
        if( ! is_null($prefetched_author_record) )
            $this->_author_account = $prefetched_author_record;
        else
            $this->_author_account = new account($this->id_author);
    }
    
    /**
     * @return account
     */
    public function get_author()
    {
        // TODO: Implement accounts repository for caching
        
        if( is_object($this->_author_account) ) return $this->_author_account;
        
        return new account($this->id_author);
    }
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        unset(
            $return["_author_account"]
        );
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
    }
    
    public function get_processed_content()
    {
        $contents = $this->content;
        $contents = convert_emojis($contents);
    
        return $contents;
    }
}
