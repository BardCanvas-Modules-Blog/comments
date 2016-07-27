<?php
namespace hng2_modules\comments;

use hng2_base\account;
use hng2_base\repository\abstract_record;

class comment_record extends abstract_record
{
    public $id_post            ; # varchar(32) not null default '',
    public $id_comment         ; # varchar(32) not null default '',
    public $parent_comment     ; # varchar(32) not null default '',
    
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
    
    public function set_new_id()
    {
        $this->id_comment = uniqid();
    }
    
    /**
     * @return account
     */
    public function get_author()
    {
        // TODO: Implement accounts repository for caching
        
        return new account($this->id_author);
    }
}
